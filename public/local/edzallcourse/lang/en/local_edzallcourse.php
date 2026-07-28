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
 * English language pack for local_edzallcourse.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'EDZ Catalogue';

// Capabilities.
$string['edzallcourse:view'] = 'View the course catalogue';
$string['edzallcourse:manage'] = 'Manage the course catalogue';

// Privacy.
$string['privacy:metadata'] = 'The EDZ Catalogue plugin does not store any personal data. It only displays existing courses and categories.';

// Page.
$string['viewpage'] = 'Course Catalogue';
$string['herotitle_default'] = 'Course Catalogue';
$string['herotext_default'] = 'Browse programmes by school, then drill into departments and modules to find the right course.';
$string['browseheading'] = 'Programmes';

// Drilldown.
$string['allof'] = 'All {$a}';
$string['levellabel'] = 'Level {$a}';
$string['inclsub'] = '(incl. sub-categories)';

// Search / sort.
$string['searchplaceholder'] = 'Search courses…';
$string['searchplaceholder_scoped'] = 'Search within this category…';
$string['sortlabel'] = 'Sort by';
$string['sort_popular'] = 'Most popular';
$string['sort_new'] = 'Newest';
$string['sort_az'] = 'Name A–Z';
$string['sort_start'] = 'Start date';

// Cards.
$string['teacherlabel'] = 'Faculty';
$string['startdatelabel'] = 'Starts';
$string['notyetassigned'] = 'Not yet assigned';
$string['enrolnow'] = 'Enrol now';
$string['gotocourse'] = 'Go to course';
$string['viewcourse'] = 'View course';
$string['editcourse'] = 'Edit course';
$string['moreteachers'] = '+{$a}';

// Results / pagination.
$string['showingcount'] = 'Showing {$a->from}–{$a->to} of {$a->total} courses';
$string['noresults'] = 'No courses found.';
$string['nocoursesin'] = 'No courses in this category yet.';
$string['prev'] = 'Prev';
$string['next'] = 'Next';

// States.
$string['loadingcourses'] = 'Loading courses…';
$string['errorloading'] = 'Could not load the catalogue. Please try again.';
$string['selectcategory'] = 'Select a programme on the left to begin browsing.';
$string['disabledpage'] = 'The course catalogue is currently disabled.';

// Settings.
$string['enable'] = 'Enable catalogue';
$string['enable_desc'] = 'Turn the EDZ Catalogue on or off. When off, the page and course-index redirect are inactive.';
$string['perpage'] = 'Courses per page';
$string['perpage_desc'] = 'How many course cards to show per page.';
$string['defaultsort'] = 'Default sort';
$string['defaultsort_desc'] = 'The sort order applied when the catalogue first loads.';
$string['scope'] = 'Category scope';
$string['scope_desc'] = 'Recursive shows a category\'s courses plus everything in its sub-categories. Direct shows only courses placed directly in the selected category.';
$string['scope_recursive'] = 'Recursive (include sub-categories)';
$string['scope_direct'] = 'Direct only';
$string['showteacher'] = 'Show teacher';
$string['showteacher_desc'] = 'Show the primary course contact on each card.';
$string['showstartdate'] = 'Show start date';
$string['showstartdate_desc'] = 'Show the course start date on each card.';
$string['showsummary'] = 'Show summary';
$string['showsummary_desc'] = 'Show a short course summary on each card.';
$string['takeovercourseindex'] = 'Take over course index';
$string['takeovercourseindex_desc'] = 'Redirect /course/index.php to the EDZ Catalogue.';
$string['herotitle'] = 'Hero title';
$string['herotitle_desc'] = 'Override the catalogue hero heading. Leave blank to use the default.';
$string['herotext'] = 'Hero description';
$string['herotext_desc'] = 'Override the catalogue hero description. Leave blank to use the default.';

// Terminology overrides (optional; leave blank to use defaults above).
$string['term_teacher'] = 'Faculty';
$string['term_category'] = 'Category';
