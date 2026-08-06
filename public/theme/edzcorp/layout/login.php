<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp — login layout.
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$bodyattributes = $OUTPUT->body_attributes(['theme-edzcorp', 'layout-login']);

$theme = theme_config::load('edzcorp');
$s     = $theme->settings;

// ---- Logo ----------------------------------------------------------------
$logourl = '';
if (!empty($s->logo)) {
    $logourl = $theme->setting_file_url('logo', 'logo');
} elseif (!empty($s->sidebarlogo)) {
    $logourl = $theme->setting_file_url('sidebarlogo', 'sidebarlogo');
}

// ---- Slider captions -----------------------------------------------------
$caption1 = !empty($s->loginslidercaption1)
    ? format_string($s->loginslidercaption1)
    : '';
$caption2 = !empty($s->loginslidercaption2)
    ? format_string($s->loginslidercaption2)
    : '';

// True when a second image has been uploaded (drives dot navigation and JS).
$hasslide2 = !empty($s->loginsliderimage2);

// True when ANY slider image is uploaded. When false we render the designed
// hero fallback panel (branding + icon + heading + stat cards) instead.
$hasslideany = !empty($s->loginsliderimage1) || !empty($s->loginsliderimage2);

// ---- Hero fallback content (only used when $hasslideany is false) ----------
$herotitle    = !empty($s->loginherotitle)
    ? format_string($s->loginherotitle)
    : get_string('loginhero_title_default', 'theme_edzcorp');
// Subtitle keeps the admin's line breaks (Enter or <br>) so it can be multi-paragraph.
$herosubtitle = !empty($s->loginherosubtitle)
    ? format_text($s->loginherosubtitle, FORMAT_MOODLE, ['para' => false, 'newlines' => true])
    : get_string('loginhero_subtitle_default', 'theme_edzcorp');
// Accent-coloured highlight line under the subtitle (optional).
$herohighlight = !empty($s->loginherohighlight) ? format_string($s->loginherohighlight) : '';

$herostatsenabled = !isset($s->loginherostatsenabled) || !empty($s->loginherostatsenabled);
$herostats = [];
if ($herostatsenabled) {
    for ($i = 1; $i <= 3; $i++) {
        $valkey = "loginherostat{$i}value";
        $lblkey = "loginherostat{$i}label";
        $icokey = "loginherostat{$i}icon";
        $val = !empty($s->$valkey) ? format_string($s->$valkey) : '';
        $lbl = !empty($s->$lblkey) ? format_string($s->$lblkey) : '';
        $ico = !empty($s->$icokey) ? clean_param($s->$icokey, PARAM_TEXT) : '';
        // Bare icon names default to the solid style.
        if ($ico !== '' && !preg_match('/\b(fa-solid|fa-regular|fa-brands|fas|far|fab)\b/', $ico)) {
            $ico = 'fa-solid ' . $ico;
        }
        if ($val !== '' || $lbl !== '') {
            $herostats[] = ['value' => $val, 'label' => $lbl, 'icon' => $ico, 'hasicon' => ($ico !== '')];
        }
    }
}
$hasherostats = !empty($herostats);

// ---- Right panel: support prompt + technology-partner line -----------------
$supporttext  = !empty($s->loginsupporttext)  ? format_string($s->loginsupporttext)  : '';
$supportlabel = !empty($s->loginsupportlabel) ? format_string($s->loginsupportlabel) : '';
$supporturl   = !empty($s->loginsupporturl)   ? clean_param($s->loginsupporturl, PARAM_URL) : '';
$techpartner  = !empty($s->logintechpartner)  ? format_string($s->logintechpartner)  : '';
$hassupport   = ($supporttext !== '' || $supportlabel !== '');

// JSON array of captions for the slider JS (auto-advance changes the caption).
// json_encode with flags to safely embed in a Mustache {{{triple}}} context.
$captionsjson = json_encode(
    [$caption1, $caption2],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
);

// ---- Heading on left panel -----------------------------------------------
$sliderheading = !empty($s->loginsliderheading)
    ? format_string($s->loginsliderheading)
    : '';

// ---- Login layout --------------------------------------------------------
// 'split'  = full-screen side-by-side (default)
// 'card'   = floating card centered on a gradient background
$loginlayout      = !empty($s->loginlayout) ? $s->loginlayout : 'split';
$loginlayoutclass = 'edz-layout-' . $loginlayout;

// ---- Home URL (for logo link) --------------------------------------------
$homeurl = (new moodle_url('/'))->out(false);

// ---- Language menu -------------------------------------------------------
$primary     = new core\navigation\output\primary($PAGE);
$renderer    = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);

$PAGE->requires->js_call_amd('theme_edzcorp/theme', 'init');

$templatecontext = [
    'output'          => $OUTPUT,
    'bodyattributes'  => $bodyattributes,
    'homeurl'         => $homeurl,
    'logourl'         => $logourl,
    'haslogo'         => !empty($logourl),
    'sitename'        => format_string($SITE->fullname, true,
                             ['context' => context_system::instance()]),
    'loginlayoutclass'   => $loginlayoutclass,
    'sliderheading'      => $sliderheading,
    'slidercaption1'     => $caption1,
    'slidercaption2'     => $caption2,
    'slidercaptions_json'=> $captionsjson,
    'hasslide2'          => $hasslide2,
    'hasslideany'        => $hasslideany,
    'herotitle'          => $herotitle,
    'herosubtitle'       => $herosubtitle,
    'herohighlight'      => $herohighlight,
    'hasherostats'       => $hasherostats,
    'herostats'          => $herostats,
    'supporttext'        => $supporttext,
    'supportlabel'       => $supportlabel,
    'supporturl'         => $supporturl,
    'hassupport'         => $hassupport,
    'techpartner'        => $techpartner,
    'langmenu'           => $primarymenu['lang'] ?? false,
];

echo $OUTPUT->render_from_template('theme_edzcorp/login', $templatecontext);
