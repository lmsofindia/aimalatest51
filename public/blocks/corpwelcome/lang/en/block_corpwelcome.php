<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname']          = 'Corporate welcome';
$string['corpwelcome:addinstance']   = 'Add a corporate welcome block';
$string['corpwelcome:myaddinstance'] = 'Add a corporate welcome block to My Moodle';
$string['goodmorning']         = 'Good morning';
$string['goodafternoon']       = 'Good afternoon';
$string['goodevening']         = 'Good evening';
$string['enrolledcourses']     = 'Enrolled courses';
$string['completed']           = 'Completed';
$string['certificates']        = 'Certificates earned';
$string['badges']              = 'Badges collected';
$string['streakdays']          = '{$a}-day streak';
$string['searchplaceholder']   = 'Search courses, topics, instructors...';
$string['search']              = 'Search';
$string['allcategories']       = 'All';
$string['noresults']           = 'No courses found.';
$string['welcomemessage']      = 'Welcome back! Pick up where you left off.';
$string['motivationmessage']   = 'You\'re making great progress. Continue with "{$a}" today.';
$string['configbgcolor']       = 'Background colour';
$string['configbgcolordesc']   = 'HEX or CSS colour value for the block background (e.g. #4f46e5).';
$string['privacy:metadata']    = 'The corporate welcome block stores streak data in user preferences.';
$string['configblocktheme']     = 'Block style';
$string['configblockthemedesc'] = 'Choose the visual style for this block. Dark uses the premium glassmorphism look; Light uses a clean split-panel layout.';
$string['themeopt_dark']        = 'Dark (glassmorphism)';
$string['themeopt_light']       = 'Light (split layout)';
$string['themeopt_hero']        = 'Hero banner (accent header)';
$string['configcategories']     = 'Category filter pills';
$string['configcategoriesdesc'] = 'Choose which course categories appear as filter pills. Leave empty to show all (up to 5).';
$string['configmonthlygoal']      = 'Monthly completion goal';
$string['configmonthlygoal_help'] = 'Number of course completions that counts as reaching this month\'s goal. The progress bar fills based on completions made in the current calendar month.';
// Help strings for the existing config fields (addHelpButton requires a *_help key).
$string['configbgcolor_help']     = 'HEX or CSS colour value for the block background (e.g. #4f46e5).';
$string['configblocktheme_help']  = 'Choose the visual style for this block. Dark uses the premium glassmorphism look; Light uses a clean split-panel layout.';
$string['configcategories_help']  = 'Choose which course categories appear as filter pills. Leave empty to show all (up to 5).';

// Background image (Hero style).
$string['configbackgroundimage']      = 'Background image (Hero style)';
$string['configbackgroundimage_help'] = 'Optional. Upload an image to use as the background of the Hero banner header instead of the flat accent colour. Recommended size roughly 1600×500px (landscape). The background colour above is used as a fallback and shows behind the image while it loads. Leave empty to keep the plain colour.';
$string['configoverlaydarkness']      = 'Image overlay darkness';
$string['configoverlaydarkness_help'] = 'How dark a scrim to place over the background image so the white heading and progress text stay readable. Increase this for bright or busy images; decrease it for images that are already dark. Only applies when a background image is set.';
$string['overlay0']  = 'None (0%) — image only';
$string['overlay20'] = 'Light (20%)';
$string['overlay40'] = 'Medium (40%) — recommended';
$string['overlay60'] = 'Strong (60%)';
$string['overlay80'] = 'Very strong (80%)';
