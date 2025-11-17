<?php

namespace local_edzworkplacerpt\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/externallib.php');

use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_api;

class api extends external_api
{

    public static function get_myteam_report_parameters()
    {
        return new external_function_parameters([
            'startdate' => new external_value(PARAM_INT, 'start ts', VALUE_DEFAULT, null),
            'enddate'   => new external_value(PARAM_INT, 'end ts', VALUE_DEFAULT, null),
            'level'     => new external_value(PARAM_INT, 'levels', VALUE_DEFAULT, 1),
            'category'  => new external_value(PARAM_INT, 'category id', VALUE_DEFAULT, null),
            'course'    => new external_value(PARAM_INT, 'course id', VALUE_DEFAULT, null),
            'department' => new external_value(PARAM_TEXT, 'department', VALUE_DEFAULT, null),
            'showonly'  => new external_value(PARAM_TEXT, 'completed/not/all', VALUE_DEFAULT, 'all'),
            'limit'     => new external_value(PARAM_INT, 'limit', VALUE_DEFAULT, 200),
            'offset'    => new external_value(PARAM_INT, 'offset', VALUE_DEFAULT, 0)
        ]);
    }

    public static function get_myteam_report_returns()
    {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'total rows'),
            'rows' => new external_multiple_structure(
                new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'user id'),
                    'name' => new external_value(PARAM_TEXT, 'fullname'),
                    'email' => new external_value(PARAM_TEXT, 'email'),
                    'courseid' => new external_value(PARAM_INT, 'course id', VALUE_OPTIONAL),
                    'coursename' => new external_value(PARAM_TEXT, 'course name', VALUE_OPTIONAL),
                    'enrolmenttime' => new external_value(PARAM_INT, 'enrol date', VALUE_OPTIONAL),
                    'completiontime' => new external_value(PARAM_INT, 'completion ts', VALUE_OPTIONAL),
                    'badgecount' => new external_value(PARAM_INT, 'badges count', VALUE_OPTIONAL),
                    'hascertificate' => new external_value(PARAM_BOOL, 'has certificate', VALUE_OPTIONAL),
                ])
            )
        ]);
    }

    public static function get_myteam_report($params)
    {
        global $USER;
        $params = self::validate_parameters(self::get_myteam_report_parameters(), $params);

        $context = \context_system::instance();
        self::validate_context($context);
        //require_capability('local/edzworkplacerpt:viewreport', $context);

        $filters = [];
        if (!empty($params['startdate'])) {
            $filters['startdate'] = $params['startdate'];
        }
        if (!empty($params['enddate'])) {
            $filters['enddate'] = $params['enddate'];
        }
        if (!empty($params['course'])) {
            $filters['courseid'] = $params['course'];
        }
        if (!empty($params['category'])) {
            $filters['categoryid'] = $params['category'];
        }
        if (!empty($params['department'])) {
            $filters['department'] = $params['department'];
        }
        if (!empty($params['showonly'])) {
            $filters['showonly'] = $params['showonly'];
        }

        $levels = isset($params['level']) ? intval($params['level']) : 1;
        if ($levels === -1) {
            $levels = 0;
        }

        $sub = \local_edzworkplacerpt\helper::get_subordinates($USER->id, $levels);

        $res = \local_edzworkplacerpt\helper::fetch_report_rows($sub, $filters, $params['limit'], $params['offset']);

        return $res;
    }

    public static function send_message_parameters()
    {
        return new external_function_parameters([
            'touserids' => new external_multiple_structure(new external_value(PARAM_INT, 'touserid')),
            'subject' => new external_value(PARAM_TEXT, 'subject'),
            'messagehtml' => new external_value(PARAM_RAW, 'message html')
        ]);
    }

    public static function send_message_returns()
    {
        return new external_single_structure([
            'sent' => new external_value(PARAM_INT, 'sent count'),
            'failed' => new external_value(PARAM_INT, 'failed count')
        ]);
    }

    // public static function send_message($params)
    // {
       
    //     global $USER;
    //     $params = self::validate_parameters(self::send_message_parameters(), $params);
    //        echo "rashid";die();
    //     $context = \context_system::instance();
    //     self::validate_context($context);
    //     //require_capability('local/edzworkplacerpt:viewreport', $context);
     
    //     $res = \local_edzworkplacerpt\helper::send_message($USER->id, $params['touserids'], $params['subject'], $params['messagehtml']);

    //     return $res;
    // }

    public static function send_message($touserids, $subject, $messagehtml)
    {
        global $USER;

        // Validate parameters
        $params = self::validate_parameters(self::send_message_parameters(), [
            'touserids' => $touserids,
            'subject' => $subject,
            'messagehtml' => $messagehtml
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        // require_capability('local/edzworkplacerpt:viewreport', $context);

        // Call your helper function that actually sends messages
        $res = \local_edzworkplacerpt\helper::send_message(
            $USER->id,
            $params['touserids'],
            $params['subject'],
            $params['messagehtml']
        );

        return $res;
    }

}
