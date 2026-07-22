<?php
namespace block_corpwelcome\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use context_system;
use moodle_url;

class search_courses extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query'      => new external_value(PARAM_TEXT, 'Search query',               VALUE_REQUIRED),
            'categoryid' => new external_value(PARAM_INT,  'Filter category id (0=all)', VALUE_DEFAULT, 0),
            'limit'      => new external_value(PARAM_INT,  'Max results',                VALUE_DEFAULT, 10),
        ]);
    }

    public static function execute(string $query, int $categoryid = 0, int $limit = 10): array {
        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        ['query' => $query, 'categoryid' => $categoryid, 'limit' => $limit] =
            self::validate_parameters(self::execute_parameters(),
                ['query' => $query, 'categoryid' => $categoryid, 'limit' => $limit]);

        self::validate_context(context_system::instance());

        if (strlen(trim($query)) < 3 && $categoryid === 0) {
            return ['courses' => [], 'categories' => []];
        }

        $limit     = min(max((int)$limit, 1), 20);
        $querylow  = strtolower(trim($query));
        $enrolled  = enrol_get_users_courses($USER->id, true, 'id,fullname,shortname,category,summary');
        $results   = [];

        foreach ($enrolled as $course) {
            if ($categoryid > 0 && (int)$course->category !== $categoryid) { continue; }
            if ($querylow !== '' &&
                strpos(strtolower($course->fullname), $querylow)  === false &&
                strpos(strtolower($course->shortname), $querylow) === false) {
                continue;
            }
            $catname = '';
            if ($cat = $DB->get_record('course_categories', ['id' => $course->category], 'name', IGNORE_MISSING)) {
                $catname = format_string($cat->name);
            }
            $imgurl  = '';
            $context = \context_course::instance($course->id, IGNORE_MISSING);
            if ($context) {
                $fs    = get_file_storage();
                $files = $fs->get_area_files($context->id, 'course', 'overviewfiles',
                    0, 'filename', false);
                if ($files) {
                    $file   = reset($files);
                    $imgurl = \moodle_url::make_pluginfile_url(
                        $context->id, 'course', 'overviewfiles', null,
                        $file->get_filepath(), $file->get_filename())->out(false);
                }
            }
            $results[] = [
                'id'      => (int)$course->id,
                'name'    => format_string($course->fullname),
                'category'=> $catname,
                'url'     => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                'imgurl'  => $imgurl,
            ];
            if (count($results) >= $limit) { break; }
        }

        // Supplement with site-wide search if under limit.
        if (count($results) < $limit && $querylow !== '') {
            $params   = ['q' => "%{$querylow}%", 'vis' => 1];
            $where    = 'fullname LIKE :q AND visible = :vis';
            if ($categoryid > 0) { $where .= ' AND category = :catid'; $params['catid'] = $categoryid; }
            $extras   = $DB->get_records_select('course', $where, $params, 'fullname ASC',
                'id,fullname,shortname,category', 0, $limit * 2);
            $existing = array_column($results, 'id');
            foreach ($extras as $course) {
                if (in_array((int)$course->id, $existing)) { continue; }
                $catname = '';
                if ($cat = $DB->get_record('course_categories', ['id' => $course->category], 'name', IGNORE_MISSING)) {
                    $catname = format_string($cat->name);
                }
                $results[] = [
                    'id'      => (int)$course->id,
                    'name'    => format_string($course->fullname),
                    'category'=> $catname,
                    'url'     => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                    'imgurl'  => '',
                ];
                if (count($results) >= $limit) { break; }
            }
        }

        // Categories matching query.
        $catresults = [];
        if ($querylow !== '') {
            $catrows = $DB->get_records_select('course_categories',
                'LOWER(name) LIKE :q AND visible = 1', ['q' => "%{$querylow}%"],
                'name ASC', 'id,name', 0, 5);
            foreach ($catrows as $cat) {
                $catresults[] = ['id' => (int)$cat->id, 'name' => format_string($cat->name)];
            }
        }

        return ['courses' => $results, 'categories' => $catresults];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courses'    => new external_multiple_structure(new external_single_structure([
                'id'      => new external_value(PARAM_INT,  'Course id'),
                'name'    => new external_value(PARAM_TEXT, 'Course name'),
                'category'=> new external_value(PARAM_TEXT, 'Category name'),
                'url'     => new external_value(PARAM_URL,  'Course URL'),
                'imgurl'  => new external_value(PARAM_URL,  'Thumbnail URL', VALUE_DEFAULT, ''),
            ])),
            'categories' => new external_multiple_structure(new external_single_structure([
                'id'   => new external_value(PARAM_INT,  'Category id'),
                'name' => new external_value(PARAM_TEXT, 'Category name'),
            ])),
        ]);
    }
}
