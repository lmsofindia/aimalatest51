<?php

namespace local_coursefilter\local;

defined('MOODLE_INTERNAL') || die();

use core_customfield\field_controller;

class service
{

    /**
     * Return enabled custom field shortnames → metadata (label, id, field_controller).
     * @return array shortname => ['id'=>int, 'label'=>string, 'controller'=>field_controller]
     */
    public static function enabled_fields(): array
    {
        global $DB;
        $result = [];

        // Check if plugin is enabled.
        if (!get_config('local_coursefilter', 'enable')) {
            return $result;
        }

        try {
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            $fields  = $handler ? $handler->get_fields() : [];
        } catch (\Throwable $e) {
            $fields = [];
        }
        foreach ($fields as $f) {
            $sn = $f->get('shortname');
            if (!empty(get_config('local_coursefilter', "field_{$sn}"))) {
                $result[$sn] = [
                    'id' => (int)$f->get('id'),
                    'label' => $f->get_formatted_name(),
                    'controller' => $f,
                ];
            }
        }
         // Hardcode course category filter (not a customfield). added by rashid as on 17-09-25
          $hascategories = $DB->record_exists('course_categories', ['parent' => 0]);
          $hascourses    = $DB->record_exists('course', ['visible' => 1]);

    if ($hascategories && $hascourses) {
        $result['course_category'] = [
            'id' => 0,   // fake ID to avoid customfield query
            'label' => 'Category',
            'controller' => null,
        ];
    }

        return $result;
    }

    /**
     * For each enabled field, compute option values (distinct non-empty values present in courses).
     * @return array shortname => array of ['value'=>string, 'label'=>string]
     */
    // public static function options_for_enabled_fields(): array
    // {
    //     global $DB;
    //     $fields = self::enabled_fields();
    //     $out = [];

    //     if (empty($fields)) {
    //         return $out;
    //     }

    //     list($fieldidsql, $params) = $DB->get_in_or_equal(array_column($fields, 'id'), SQL_PARAMS_NAMED, 'f');
    //     // Using customfield tables.
    //     $sql = "SELECT f.shortname, TRIM(d.value) AS val
    //               FROM {customfield_data} d
    //               JOIN {customfield_field} f ON f.id = d.fieldid
    //              WHERE d.value IS NOT NULL AND d.value <> '' AND f.id $fieldidsql
    //           GROUP BY f.shortname, TRIM(d.value)
    //           ORDER BY f.shortname, TRIM(d.value)";
    //     $rs = $DB->get_recordset_sql($sql, $params);

    //     foreach ($rs as $rec) {
    //         $label = $rec->val;
    //         $sn = $rec->shortname;
    //         if (!isset($out[$sn])) {
    //             $out[$sn] = [];
    //         }
    //         $out[$sn][] = ['value' => $rec->val, 'label' => $rec->val];
    //     }
    //     $rs->close();

    //     // If a field has no data yet, still provide empty array (it will render with just "Any").
    //     foreach ($fields as $sn => $meta) {
    //         $out += [$sn => $out[$sn] ?? []];
    //     }

    //     return $out;
    // }
public static function options_for_enabled_fields(): array {
    global $DB;

    $fields = self::enabled_fields();
    $out = [];
    // echo "<pre>";
    // print_r($fields); die();
    // -------------------------------
    // 1. Custom Field Options
    // -------------------------------
    if (!empty($fields)) {
        // Only include real custom fields (id > 0).
        $fieldids = array_filter(array_column($fields, 'id'), function($id) {
            return $id > 0;
        });

        if (!empty($fieldids)) {
            list($fieldidsql, $params) = $DB->get_in_or_equal($fieldids, SQL_PARAMS_NAMED, 'f');
            $sql = "SELECT f.shortname, TRIM(d.value) AS val
                    FROM {customfield_data} d
                    JOIN {customfield_field} f ON f.id = d.fieldid
                    WHERE d.value IS NOT NULL AND d.value <> '' AND f.id $fieldidsql
                    GROUP BY f.shortname, TRIM(d.value)
                    ORDER BY f.shortname, TRIM(d.value)";
            $rs = $DB->get_recordset_sql($sql, $params);

            foreach ($rs as $rec) {
                $sn = $rec->shortname;

                // Convert 1/0 to Yes/No.
                $label = $rec->val;
                if ($rec->val === '1') {
                    $label = get_string('yes');
                } else if ($rec->val === '0') {
                    $label = get_string('no');
                }

                // Learning level mapping.
                if ($sn === 'learning_level') {
                    $map = [
                        '1' => 'Beginner',
                        '2' => 'Advanced',
                        '3' => 'Expert'
                    ];
                    $label = $map[$rec->val] ?? $rec->val;
                }

                if (!isset($out[$sn])) {
                    $out[$sn] = [];
                }
                $out[$sn][] = [
                    'value' => $rec->val,
                    'label' => $label
                ];
            }

            $rs->close();
        }
    }

    // Ensure empty arrays exist for all fields.
    foreach ($fields as $sn => $meta) {
        $out += [$sn => $out[$sn] ?? []];
    }

    // -------------------------------
    // 2. Course Category Options
    // -------------------------------
    if (isset($fields['course_category'])) {
        $categories = $DB->get_records(
            'course_categories',
            ['parent' => 0],             // Only top-level categories
            'sortorder ASC',
            'id, name'
        );
        $out['course_category'] = [];

        foreach ($categories as $cat) {
            $out['course_category'][] = [
                'value' => $cat->id,
                'label' => $cat->name
            ];
        }
    }
    // echo "<pre>";
    // print_r($out); die();
    return $out;
}


    /**
     * Search/filter courses and return array ready for templates.
     * @param string $q search text
     * @param array $filters ['shortname'=>'value', ...] (empty values ignored)
     * @param int $limit
     * @param int $offset
     * @return array of courses (id, fullname, shortname, summary, summaryformat, courseimage, url)
     */
    public static function search_courses(string $q = '', array $filters = [], int $limit = 24, int $offset = 0, string $sort = 'popular'): array {
    global $DB, $CFG, $OUTPUT;

    $wheres = ["c.visible = 1", "c.id <> 1"];
    $params = [];

    if ($q !== '') {
        $wheres[] = "(c.fullname LIKE :q1 OR c.shortname LIKE :q2)";
        $params['q1'] = "%{$q}%";
        $params['q2'] = "%{$q}%";
    }

    // 🔹 Handle customfield filters (already implemented)
    $i = 0;
    foreach ($filters as $shortname => $value) {
        if (empty($value)) {
            continue;
        }
        $values = is_array($value) ? $value : [$value];
        $i++;

            // Added by Rashid for category filter as on 17-09-25
            if ($shortname === 'course_category') {
                list($insql, $inparams) = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED, "cat{$i}");
                $wheres[] = "c.category {$insql}";
                $params += $inparams;
                continue;
            }


        list($insql, $inparams) = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED, "val{$i}");

        $wheres[] = "EXISTS (
            SELECT 1
              FROM {customfield_data} d{$i}
              JOIN {customfield_field} f{$i} ON f{$i}.id = d{$i}.fieldid
             WHERE d{$i}.instanceid = c.id
               AND f{$i}.shortname = :sn{$i}
               AND d{$i}.value {$insql}
        )";

        $params["sn{$i}"] = $shortname;
        $params += $inparams;
    }

    $where = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

    // 🔹 Decide ORDER BY based on $sort
    switch ($sort) {
        case 'created':
            $orderby = 'c.timecreated DESC';
            break;

        case 'duration':
            // if courses have startdate & enddate
            $orderby = '(c.enddate - c.startdate) ASC';
            break;

        case 'popular':
        default:
            // Use enrolments count as popularity
            $orderby = 'enrolledusers DESC';
            break;
    }

    // 🔹 Build SQL
    $sql = "SELECT c.id,
                   c.fullname,
                   c.shortname,
                   c.summary,
                   c.summaryformat,
                   c.timecreated,
                   c.startdate,
                   c.enddate,
                   COUNT(ue.id) AS enrolledusers
              FROM {course} c
         LEFT JOIN {enrol} e ON e.courseid = c.id
         LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                $where
          GROUP BY c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.timecreated, c.startdate, c.enddate
          ORDER BY $orderby";

    $courses = $DB->get_records_sql($sql, $params, $offset, $limit);

    // Attach image + URL
    $out = [];
    foreach ($courses as $c) {
        $context = \context_course::instance($c->id, IGNORE_MISSING);
        $imgurl = '';
        if ($context) {
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);
            if ($files) {
                $file = reset($files);
                $imgurl = file_encode_url(
                    "$CFG->wwwroot/pluginfile.php",
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . '/' . $file->get_itemid() . $file->get_filepath() . $file->get_filename(),
                    false
                );
            }
        }
        $out[] = [
            'id' => $c->id,
            'fullname' => format_string($c->fullname),
            'shortname' => format_string($c->shortname),
            'summary' => format_text($c->summary, $c->summaryformat),
            'courseimage' => $imgurl,
            'url' => (new \moodle_url('/course/view.php', ['id' => $c->id]))->out(false),
        ];
    }
    return $out;
}

}
