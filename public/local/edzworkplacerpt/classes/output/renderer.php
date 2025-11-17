<?php

namespace local_edzworkplacerpt\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use html_writer;

class renderer extends plugin_renderer_base
{

    /**
     * Renders the report table and always returns HTML (string).
     *
     * @param array $rows array of associative arrays (report rows)
     * @return string HTML table
     */
    public function render_report_table($rows)
    {



        // Ensure $rows is an array
        if (!is_array($rows)) {
            // Defensive: try to stringify if possible
            if (is_object($rows) && method_exists($rows, '__toString')) {
                return (string)$rows;
            }
            return html_writer::tag('div', get_string('noreports', 'local_edzworkplacerpt'));
        }

        $table = new \html_table();
        $table->head = [
            '<input type="checkbox" id="edz_select_all" />',
            get_string('name'),
            get_string('email'),
            get_string('department'),
            get_string('course'),
            get_string('enrolment', 'local_edzworkplacerpt') ?: 'Enrolment',
            get_string('completed', 'local_edzworkplacerpt') ?: 'Completed',
            'Badges',
            'Certificate'
        ];
        $table->attributes['class'] = 'edzreporttable generaltable';
        $table->data = [];



        foreach ($rows as $r) {
            // Defensive: make sure fields are strings (or at least scalars)
            $userid = isset($r['userid']) ? (int)$r['userid'] : 0;
            $name = isset($r['name']) ? (string)$r['name'] : '';
            $email = isset($r['email']) ? (string)$r['email'] : '';
            $department = isset($r['department']) ? (string)$r['department'] : '';
            $coursename = isset($r['coursename']) ? (string)$r['coursename'] : '';
            $enrolmenttime = !empty($r['enrolmenttime']) ? date('d-M-y', $r['enrolmenttime']) : '-'; // Rashid : Remove default as on 23-10-25
            $completiontime = !empty($r['completiontime']) ? date('d-M-y', $r['completiontime']) : '-'; // Rashid : Remove default as on 23-10-25
            $badgecount = isset($r['badgecount']) ? (int)$r['badgecount'] : 0;
            $hascert = !empty($r['hascertificate']) ? 'Yes' : 'No';

            // checkbox HTML
            //$checkbox = html_writer::checkbox('selected[]', $userid, false, 'sel' . $userid, ['class' => 'edz-row-checkbox']); // Rashid : Remove default as on 22-10-25
            $checkbox = html_writer::checkbox('selected[]', $userid, false, '', ['id' => 'sel' . $userid, 'class' => 'edz-row-checkbox']);


            // Build row cells, ensure scalar/string values only
            $cells = [
                $checkbox,
                s($name),
                s($email),
                s($department),
                s($coursename),
                s($enrolmenttime),
                s($completiontime),
                (string)$badgecount,
                s($hascert)
            ];

            $table->data[] = $cells;
        }

        // Return the HTML table string
        return html_writer::table($table);
    }
}
