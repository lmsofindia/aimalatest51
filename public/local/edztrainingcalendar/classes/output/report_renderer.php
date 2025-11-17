<?php

namespace local_edztrainingcalendar\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

class report_page implements renderable, templatable
{

    protected $records;
    protected $stats;
    protected $filters;

    public function __construct($records, $stats, $filters)
    {
        $this->records = $records;
        $this->stats = $stats;
        $this->filters = $filters;
    }

    public function export_for_template(renderer_base $output)
    {
        $rows = [];
        $sl = 1;
        foreach ($this->records as $r) {
            $rows[] = [
                'sl' => $sl++,
                'course' => $r->coursefullname,
                'student' => fullname($r),
                'email' => $r->useremail,
                'allocated' => userdate($r->uploadeddate),
                'start' => $r->coursestartdate,
                'expected' => $r->expectedcmpldate,
                'actual' => $r->actualcompletiondate,
                'status' => $r->completionstatus,
                'grade' => isset($r->grade) ? round($r->grade, 2) : '-',
                'userid' => $r->userid
            ];
        }

        return [
            'stats' => [
                'allocated' => $this->stats['allocated'] ?? 0,
                'inprogress' => $this->stats['inprogress'] ?? 0,
                'completed' => $this->stats['completed'] ?? 0,
                'notstarted' => ($this->stats['allocated'] - $this->stats['inprogress'] - $this->stats['completed'])
            ],
            'filters' => $this->filters,
            'records' => $rows
        ];
    }
}
