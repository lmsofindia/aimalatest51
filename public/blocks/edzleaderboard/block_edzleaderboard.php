<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * EdzLeaderboard block.
 *
 * A site-wide leaderboard driven by Level Up! (block_xp), with two selectable
 * layouts: a vertical podium + list, or a horizontal top-3 / top-4-10 split.
 *
 * @package    block_edzleaderboard
 * @copyright  2026 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_edzleaderboard\provider\xp_provider;

/**
 * The leaderboard block class.
 */
class block_edzleaderboard extends block_base {

    /**
     * Initialise the block title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_edzleaderboard');
    }

    /**
     * Allow the block on general pages, the dashboard, and inside courses.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'all'        => true,
            'my'         => true,
            'site-index' => true,
        ];
    }

    /**
     * Multiple instances are allowed (e.g. a compact one on the dashboard and
     * a full one on a dedicated page).
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * This block has site-level settings (settings.php).
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Apply the admin-chosen instance title.
     */
    public function specialization() {
        if (!empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        } else {
            $this->title = get_string('defaulttitle', 'block_edzleaderboard');
        }
    }

    /**
     * Build the block body.
     *
     * @return stdClass|null
     */
    public function get_content() {
        global $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text   = '';

        // ---- Instance config ------------------------------------------------
        $layout = (!empty($this->config->layout) && $this->config->layout === 'horizontal')
            ? 'horizontal' : 'vertical';
        $count = !empty($this->config->count) ? (int) $this->config->count : 10;
        $count = max(3, min(50, $count));

        // ---- Source availability -------------------------------------------
        if (!xp_provider::is_available()) {
            $this->content->text = html_writer::div(
                get_string('xpnotavailable', 'block_edzleaderboard'),
                'edzlb-empty'
            );
            return $this->content;
        }

        // Site-wide XP is stored by Level Up! under this course id (default 1).
        $xpcourseid = (int) (get_config('block_edzleaderboard', 'xpcourseid') ?: 1);

        $records = xp_provider::get_ladder($xpcourseid, $count);
        if (empty($records)) {
            $this->content->text = html_writer::div(
                get_string('noentries', 'block_edzleaderboard'),
                'edzlb-empty'
            );
            return $this->content;
        }

        $rows = $this->build_rows($records, (int) $USER->id);

        $top3 = array_slice($rows, 0, 3);
        $rest = array_slice($rows, 3);

        // If the viewer is not in the shown list, surface their own standing.
        $showmyrow = false;
        $myrow = null;
        $inlist = false;
        foreach ($rows as $r) {
            if (!empty($r['isme'])) {
                $inlist = true;
                break;
            }
        }
        if (!$inlist && isloggedin() && !isguestuser()) {
            [$myrank, $myxp, $mylvl] = xp_provider::get_user_rank($xpcourseid, (int) $USER->id);
            if ($myrank > 0) {
                $myrow = $this->build_single_row($USER, $myrank, $myxp, $mylvl, true);
                $showmyrow = true;
            }
        }

        $data = [
            'p1'        => $top3[0] ?? null,
            'p2'        => $top3[1] ?? null,
            'p3'        => $top3[2] ?? null,
            'rest'      => array_values($rest),
            'hasrest'   => !empty($rest),
            'showmyrow' => $showmyrow,
            'myrow'     => $myrow,
        ];

        $template = ($layout === 'horizontal')
            ? 'block_edzleaderboard/horizontal'
            : 'block_edzleaderboard/vertical';

        $this->content->text = $OUTPUT->render_from_template($template, $data);

        return $this->content;
    }

    /**
     * Map raw ladder records to template rows.
     *
     * @param  array $records From xp_provider::get_ladder().
     * @param  int   $meid    Current user id (for highlighting).
     * @return array
     */
    protected function build_rows(array $records, int $meid): array {
        $out  = [];
        $rank = 0;
        foreach ($records as $rec) {
            $rank++;
            $out[] = $this->build_single_row($rec, $rank, (int) $rec->xp, (int) $rec->lvl,
                ((int) $rec->id === $meid));
        }
        return $out;
    }

    /**
     * Build one presentational row from a user-bearing record.
     *
     * @param  object $rec  Object with userpic fields (id, picture, names, …).
     * @param  int    $rank 1-based rank.
     * @param  int    $xp   XP points.
     * @param  int    $lvl  Level.
     * @param  bool   $isme Whether this is the current user.
     * @return array
     */
    protected function build_single_row($rec, int $rank, int $xp, int $lvl, bool $isme): array {
        $user = (object) [
            'id'                => $rec->id,
            'picture'           => $rec->picture ?? 0,
            'firstname'         => $rec->firstname ?? '',
            'lastname'          => $rec->lastname ?? '',
            'firstnamephonetic' => $rec->firstnamephonetic ?? '',
            'lastnamephonetic'  => $rec->lastnamephonetic ?? '',
            'middlename'        => $rec->middlename ?? '',
            'alternatename'     => $rec->alternatename ?? '',
            'imagealt'          => $rec->imagealt ?? '',
            'email'             => $rec->email ?? '',
        ];

        $fullname = fullname($user);

        $haspic = ((int) $user->picture > 0);
        $picurl = '';
        if ($haspic) {
            $up = new \user_picture($user);
            $up->size = 100;
            $picurl = $up->get_url($this->page)->out(false);
        }

        return [
            'rank'        => $rank,
            'name'        => $fullname,
            'xp'          => $xp,
            'xpformatted' => number_format($xp),
            'lvl'         => $lvl,
            'haspic'      => $haspic,
            'picurl'      => $picurl,
            'initials'    => $this->initials($fullname),
            'levellabel'  => get_string('levellabel', 'block_edzleaderboard', $lvl),
            'coloridx'    => ($rank - 1) % 6,
            'ismedal'     => ($rank <= 3),
            'medalclass'  => 'edzlb-m' . $rank,
            'isme'        => $isme,
            'profileurl'  => (new \moodle_url('/user/profile.php', ['id' => $user->id]))->out(false),
        ];
    }

    /**
     * Up-to-two-letter uppercase initials from a display name.
     *
     * @param  string $name
     * @return string
     */
    protected function initials(string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        $ini = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            if ($p !== '') {
                $ini .= \core_text::strtoupper(\core_text::substr($p, 0, 1));
            }
        }
        return $ini !== '' ? $ini : '?';
    }
}
