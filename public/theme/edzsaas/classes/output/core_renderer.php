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
namespace theme_edzsaas\output;

use moodle_url;
use html_writer;
use get_string;
use theme_config; // for font

/**
 * Parent theme: boost
 *
 * @package   theme_edzsaas
 * @copyright 2025 ThemesEdzsaas  - https://edzlms.com/
 * @author    ThemesEDzsaas - Developer Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \core_renderer
{

    /**
     * See if this is the first view of the current cm in the session if it has fake blocks.
     *
     * (We track up to 100 cms so as not to overflow the session.)
     * This is done for drawer regions containing fake blocks so we can show blocks automatically.
     *
     * @return boolean true if the page has fakeblocks and this is the first visit.
     */
    public function firstview_fakeblocks(): bool
    {
        global $SESSION;

        $firstview = false;
        if ($this->page->cm) {
            if (!$this->page->blocks->region_has_fakeblocks('side-pre')) {
                return false;
            }
            if (!property_exists($SESSION, 'firstview_fakeblocks')) {
                $SESSION->firstview_fakeblocks = [];
            }
            if (array_key_exists($this->page->cm->id, $SESSION->firstview_fakeblocks)) {
                $firstview = false;
            } else {
                $SESSION->firstview_fakeblocks[$this->page->cm->id] = true;
                $firstview = true;
                if (count($SESSION->firstview_fakeblocks) > 100) {
                    array_shift($SESSION->firstview_fakeblocks);
                }
            }
        }
        return $firstview;
    }
    /**
     * Override get_logo_url to use a custom logo or logic.
     *
     * @param int $maxwidth
     * @param int $maxheight
     * @return \moodle_url|false
     */
    public function get_logo_url($maxwidth = 200, $maxheight = 200)
    {
        global $OUTPUT, $PAGE;
        $logo = get_config('core_admin', 'logo');
        if (empty($logo)) {
            $theme_name = 'theme' . '_' . $PAGE->theme->name;
            $logo_url = $OUTPUT->image_url('logo', $theme_name);
            return $logo_url;
        }

        // 200px high is the default image size which should be displayed at 100px in the page to account for retina displays.
        // It's not worth the overhead of detecting and serving 2 different images based on the device.

        // Hide the requested size in the file path.
        $filepath = ((int) $maxwidth . 'x' . (int) $maxheight) . '/';

        // Use $CFG->themerev to prevent browser caching when the file changes.
        return moodle_url::make_pluginfile_url(
            \context_system::instance()->id,
            'core_admin',
            'logo',
            $filepath,
            theme_get_revision(),
            $logo
        );
    }

    /** Mihir standard head html for fonts to work */
    public function standard_head_html()
    {
        $output = parent::standard_head_html();

        $theme = theme_config::load('edzsaas');
        $customcss = '';

        // Import site fonts.
        $fontimport = \theme_edzsaas\fonts\font_util::css();
        $customcss = "@import url('{$fontimport}');\n";


        if (!empty($theme->settings->sitefonts)) {
            $customcss .= $theme->settings->sitefonts . "\n";
        }
        if (!empty($theme->settings->fontfamily)) {
            $customcss .= "body { font-family: '{$theme->settings->fontfamily}', sans-serif; }\n";
        }
        if (!empty($theme->settings->fontfamily_title)) {
            $customcss .= "h1, h2, h3, h4, h5, h6 { font-family: '{$theme->settings->fontfamily_title}', sans-serif; }\n";
        }
        if (!empty($theme->settings->fontfamily_menus)) {
            $customcss .= ".navbar, .nav { font-family: '{$theme->settings->fontfamily_menus}', sans-serif; }\n";
        }
        if (!empty($theme->settings->fontfamily_sitename)) {
            $customcss .= ".sitename { font-family: '{$theme->settings->fontfamily_sitename}', sans-serif; }\n";
        }

        if ($customcss) {
            $output .= "<style>\n" . $customcss . "\n</style>";
        }

        return $output;
    }
}
