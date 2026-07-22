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
 * Renderer for quizaccess_edproctoring.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_edproctoring\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer class — builds the pre-flight consent screen HTML.
 */
class renderer extends \plugin_renderer_base {

    /**
     * Render the student consent / camera-check screen shown before a quiz attempt.
     *
     * @param string $quizname   Quiz name shown in the intro sentence.
     * @param int    $retention  Days snapshots are retained (shown in bullet).
     * @param string $privacyurl Optional URL to the institution privacy policy.
     * @return string HTML fragment.
     */
    public function render_consent_screen(string $quizname, int $retention, string $privacyurl): string {

        $title   = get_string('consenttitle',   'quizaccess_edproctoring');
        $intro   = get_string('consentintro',   'quizaccess_edproctoring', s($quizname));
        $deny    = get_string('consentdenymessage', 'quizaccess_edproctoring');

        $bullets = [
            get_string('consentbullet_camera',     'quizaccess_edproctoring'),
            get_string('consentbullet_storage',    'quizaccess_edproctoring', $retention),
            get_string('consentbullet_access',     'quizaccess_edproctoring'),
            get_string('consentbullet_violations', 'quizaccess_edproctoring'),
        ];

        $lihtml = '';
        foreach ($bullets as $bullet) {
            $lihtml .= \html_writer::tag('li', $bullet);
        }
        $ulhtml = \html_writer::tag('ul', $lihtml);

        $privacylink = '';
        if (!empty($privacyurl)) {
            $privacylink = \html_writer::link(
                $privacyurl,
                get_string('privacypolicy', 'quizaccess_edproctoring'),
                ['target' => '_blank', 'rel' => 'noopener']
            );
            $privacylink = \html_writer::tag('p', $privacylink);
        }

        $denyp = \html_writer::tag('p', $deny, ['class' => 'text-muted small']);

        $inner = \html_writer::tag('h5', $title)
               . \html_writer::tag('p', $intro)
               . $ulhtml
               . $privacylink
               . $denyp;

        return \html_writer::div($inner, 'edp-consent-screen alert alert-info');
    }
    /**
     * Render the live monitor wall chrome (tiles are filled by AMD/JS).
     *
     * @param int $cmid    Quiz course module id (0 = site-wide).
     * @param int $refresh Auto-refresh interval in seconds.
     * @return string HTML
     */
    public function render_live_monitor(int $cmid, int $refresh): string {
        return $this->render_from_template('quizaccess_edproctoring/live_monitor', [
            'cmid'    => $cmid,
            'refresh' => $refresh,
        ]);
    }
}

