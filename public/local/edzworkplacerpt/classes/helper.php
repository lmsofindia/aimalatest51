<?php

namespace local_edzworkplacerpt;

defined('MOODLE_INTERNAL') || die();

class helper
{

    public static function get_subordinates($userid, $levels = 1)
    {
        global $DB;

        $cfg = get_config('local_edzworkplacerpt');
        $useemail = !empty($cfg->use_email_mapping);
        $fieldname = $useemail ? $cfg->manager_mapped_field : $cfg->manager_mapped_idfield;

        if (empty($fieldname)) {
            return [];
        }

        $fieldrec = $DB->get_record('user_info_field', ['shortname' => $fieldname], '*', IGNORE_MISSING);

        $visited = [];
        $result = [];
        $currentlevel = [$userid];

        $levelcount = 0;
        while (!empty($currentlevel)) {
            $levelcount++;
            if ($levels > 0 && $levelcount > $levels) {
                break;
            }

            $identifiers = [];
            foreach ($currentlevel as $u) {
                $uobj = $DB->get_record('user', ['id' => $u], 'id, email, username', IGNORE_MISSING);
                if (!$uobj) {
                    continue;
                }
                $identifiers[] = $useemail ? $uobj->email : (string) $uobj->id;
            }
            if (empty($identifiers)) {
                break;
            }

            if ($fieldrec) {
                list($insql, $params) = $DB->get_in_or_equal($identifiers, SQL_PARAMS_NAMED, 'idn');
                $params['fieldid'] = $fieldrec->id;
                $sql = "SELECT uid.userid FROM {user_info_data} uid WHERE uid.fieldid = :fieldid AND uid.data $insql";
                $found = $DB->get_records_sql($sql, $params);
                $nextlevel = [];
                foreach ($found as $f) {
                    if (!in_array($f->userid, $visited)) {
                        $visited[] = $f->userid;
                        $nextlevel[] = $f->userid;
                        $result[] = $f->userid;
                    }
                }
            } else {
                $nextlevel = [];
            }

            $currentlevel = $nextlevel;
        }

        return array_values(array_unique($result));
    }

    public static function fetch_report_rows_no($subordinateids, $filters = [], $limit = 200, $offset = 0)
    {
        global $DB;

        if (empty($subordinateids)) {
            return ['total' => 0, 'rows' => []];
        }

        list($inusersql, $userparams) = $DB->get_in_or_equal($subordinateids, SQL_PARAMS_NAMED, 'usr');
        $params = $userparams;

        $sql = "SELECT u.id as userid, u.firstname, u.lastname, u.email, u.department,
                       c.id AS courseid, c.fullname AS coursename,
                       ue.timecreated as enrolmenttime,
                       cc.timecompleted,
                       COALESCE(bi.badgecount, 0) as badgecount,
                       CASE WHEN cert.userid IS NOT NULL THEN 1 ELSE 0 END AS hascertificate
                  FROM {user} u
             LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
             LEFT JOIN {enrol} e ON e.id = ue.enrolid
             LEFT JOIN {course} c ON c.id = e.courseid
             LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = c.id
             LEFT JOIN (
                 SELECT userid, COUNT(*) AS badgecount FROM {badge_issued} GROUP BY userid
             ) bi ON bi.userid = u.id
             LEFT JOIN (
                 SELECT userid, course FROM {customcert_issues}
             ) cert ON cert.userid = u.id AND cert.course = c.id
                 WHERE u.id $inusersql";

        if (!empty($filters['startdate'])) {
            $sql .= " AND ue.timecreated >= :startdate";
            $params['startdate'] = $filters['startdate'];
        }
        if (!empty($filters['enddate'])) {
            $sql .= " AND ue.timecreated <= :enddate";
            $params['enddate'] = $filters['enddate'];
        }
        if (!empty($filters['courseid'])) {
            $sql .= " AND c.id = :courseid";
            $params['courseid'] = $filters['courseid'];
        }
        if (!empty($filters['categoryid'])) {
            $sql .= " AND c.category = :categoryid";
            $params['categoryid'] = $filters['categoryid'];
        }
        if (!empty($filters['department'])) {
            $sql .= " AND u.department = :department";
            $params['department'] = $filters['department'];
        }
        if (!empty($filters['showonly']) && $filters['showonly'] === 'completed') {
            $sql .= " AND cc.timecompleted IS NOT NULL";
        } else if (!empty($filters['showonly']) && $filters['showonly'] === 'notcompleted') {
            $sql .= " AND cc.timecompleted IS NULL";
        }

        $countsql = "SELECT COUNT(1) FROM ({$sql}) t";
        $total = $DB->count_records_sql($countsql, $params);

        $sql .= " ORDER BY u.lastname, u.firstname, c.fullname";
        if ($limit > 0) {
            $rows = $DB->get_records_sql($sql, $params, $offset, $limit);
        } else {
            // no limit, return all
            $rows = $DB->get_records_sql($sql, $params);
        }

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'userid' => $r->userid,
                'name' => fullname((object)['firstname' => $r->firstname, 'lastname' => $r->lastname]),
                'email' => $r->email,
                'department' => $r->department,
                'courseid' => $r->courseid,
                'coursename' => $r->coursename,
                'enrolmenttime' => $r->enrolmenttime,
                'completiontime' => $r->timecompleted,
                'badgecount' => intval($r->badgecount),
                'hascertificate' => boolval($r->hascertificate)
            ];
        }

        return ['total' => $total, 'rows' => $out];
    }

    /**
     * Fetch report rows for an array of subordinate user ids with filters.
     * Returns array of rows (simple associative arrays) and total count.
     *
     * @param int[] $subordinateids
     * @param array $filters
     * @param int $limit  if 0 => no limit (return all)
     * @param int $offset
     * @return array ['total' => int, 'rows' => array]
     */
    public static function fetch_report_rows($subordinateids, $filters = [], $limit = 200, $offset = 0)
    {
        global $DB, $USER;

       // print_r($subordinateids);die();

        // Rashid :If admin logon 
       if (is_siteadmin($USER)) {
            $subordinateids = $DB->get_fieldset_select(
                'user',
                'id',
                "deleted = 0 AND confirmed = 1 AND id NOT IN (1, 2)"
            );
        }

        if (empty($subordinateids)) {
            return ['total' => 0, 'rows' => []];
        }

        list($inusersql, $userparams) = $DB->get_in_or_equal($subordinateids, SQL_PARAMS_NAMED, 'usr');
        $params = $userparams;

        $sql = "SELECT CONCAT(u.id, '_', c.id) AS uniqueid,u.id AS userid,
                   u.firstname,
                   u.lastname,
                   u.email,
                   u.department,
                   c.id AS courseid,
                   c.fullname AS coursename,
                   ue.timecreated AS enrolmenttime,
                   cc.timecompleted,
                   COALESCE(bi.badgecount, 0) AS badgecount,
                   -- hascertificate determined by EXISTS join to customcert_issues -> customcert -> course
                   CASE WHEN EXISTS (
                       SELECT 1
                         FROM {customcert_issues} ci
                         JOIN {customcert} cc ON cc.id = ci.customcertid
                        WHERE ci.userid = u.id AND cc.course = c.id
                   ) THEN 1 ELSE 0 END AS hascertificate
              FROM {user} u
         LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
         LEFT JOIN {enrol} e ON e.id = ue.enrolid
         LEFT JOIN {course} c ON c.id = e.courseid
         LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = c.id
         LEFT JOIN (
             SELECT userid, COUNT(*) AS badgecount
               FROM {badge_issued}
           GROUP BY userid
         ) bi ON bi.userid = u.id
             WHERE u.id $inusersql";

        // filters
        if (!empty($filters['startdate'])) {
            $sql .= " AND ue.timecreated >= :startdate";
            $params['startdate'] = $filters['startdate'];
        }
        if (!empty($filters['enddate'])) {
            $sql .= " AND ue.timecreated <= :enddate";
            $params['enddate'] = $filters['enddate'];
        }
        if (!empty($filters['courseid'])) {
            $sql .= " AND c.id = :courseid";
            $params['courseid'] = $filters['courseid'];
        }
        if (!empty($filters['categoryid'])) {
            $sql .= " AND c.category = :categoryid";
            $params['categoryid'] = $filters['categoryid'];
        }
        if (!empty($filters['department'])) {
            $sql .= " AND u.department = :department";
            $params['department'] = $filters['department'];
        }
        if (!empty($filters['showonly']) && $filters['showonly'] === 'completed') {
            $sql .= " AND cc.timecompleted IS NOT NULL";
        } else if (!empty($filters['showonly']) && $filters['showonly'] === 'notcompleted') {
            $sql .= " AND cc.timecompleted IS NULL";
        }

        // total count (wrap as subselect)
        $countsql = "SELECT COUNT(1) FROM ({$sql}) t";
        $total = $DB->count_records_sql($countsql, $params);

        // ordering + pagination
        $sql .= " ORDER BY u.lastname, u.firstname, c.fullname";
        if ($limit > 0) {
            $rows = $DB->get_records_sql($sql, $params, $offset, $limit);
        } else {
            // no limit - return all matching rows
            $rows = $DB->get_records_sql($sql, $params);
        }

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'userid' => $r->userid,
                'name' => fullname((object)[
                    'firstname' => $r->firstname,
                    'lastname' => $r->lastname,
                    'firstnamephonetic' => '',
                    'lastnamephonetic' => '',
                    'middlename' => '',
                    'alternatename' => ''
                ]),
                'email' => $r->email,
                'department' => $r->department,
                'courseid' => $r->courseid,
                'coursename' => $r->coursename,
                'enrolmenttime' => $r->enrolmenttime,
                'completiontime' => $r->timecompleted,
                'badgecount' => intval($r->badgecount),
                'hascertificate' => boolval($r->hascertificate)
            ];
        }

        return ['total' => $total, 'rows' => $out];
    }


    // public static function send_message($fromuserid, $touserids, $subject, $messagehtml)
    // {
    //     global $DB;

    //     $sent = 0;
    //     $failed = 0;
    //     $fromuser = $DB->get_record('user', ['id' => $fromuserid], '*', MUST_EXIST);

    //     foreach ($touserids as $tu) {
    //         $touser = $DB->get_record('user', ['id' => $tu], '*', IGNORE_MISSING);
    //         if (!$touser) {
    //             $failed++;
    //             continue;
    //         }

    //         $message = new \core\message\message();
    //         $message->component = 'local_edzworkplacerpt';
    //         $message->name = 'managermessage';
    //         $message->userfrom = $fromuser;
    //         $message->userto = $touser;
    //         $message->subject = $subject;
    //         $message->fullmessage = strip_tags($messagehtml);
    //         $message->fullmessageformat = FORMAT_HTML;
    //         $message->fullmessagehtml = $messagehtml;
    //         $message->smallmessage = $subject;
    //         $message->notification = 1; 

    //         try {
    //             message_send($message);
    //             $sent++;
    //         } catch (\Exception $e) {
    //             debugging('Failed to send message: ' . $e->getMessage());
    //             $failed++;
    //         }
    //     }

    //     return ['sent' => $sent, 'failed' => $failed];
    // }

  public static function send_message($fromuserid, $touserids, $subject, $messagehtml) {
    global $DB;

    $sent = 0;
    $failed = 0;

    $fromuser = $DB->get_record('user', ['id' => $fromuserid, 'deleted' => 0], '*', MUST_EXIST);

    foreach ($touserids as $tu) {
        $touser = $DB->get_record('user', ['id' => $tu, 'deleted' => 0], '*', IGNORE_MISSING);
        // print_r($touserids); die();
        if (!$touser) {
            debugging("❌ Recipient not found: id={$tu}");
            $failed++;
            continue;
        }

        $message = new \core\message\message();
        $message->component = 'local_edzworkplacerpt';
        $message->name = 'manager_message';
        $message->userfrom = $fromuser;
        $message->userto = $touser;
        $message->subject = $subject;
        $message->fullmessage = strip_tags($messagehtml);
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $messagehtml;
        $message->smallmessage = $subject;
        $message->notification = 1;

        try {
            $messageid = message_send($message);
            if ($messageid) {
                debugging("✅ Message sent to user id={$touser->id}, messageid={$messageid}");
                $sent++;
            } else {
                debugging("⚠️ Message_send returned false for user id={$touser->id}");
                $failed++;
            }
        } catch (\Exception $e) {
            debugging("❌ Exception sending to user id={$touser->id}: " . $e->getMessage());
            $failed++;
        }
    }

    return ['sent' => $sent, 'failed' => $failed];
}


    /**
     * Return structured chart data for the provided subordinate ids and optional filters.
     *
     * Returns an array with keys:
     *  - levels: [ level => count, ... ]  (1..5 and 'all' bucket)
     *  - totals: ['enrolled' => int, 'completed' => int, 'badges' => int, 'certificates' => int]
     *  - timeseries: ['labels' => [...], 'enrolled' => [...], 'completed' => [...]]  (labels in yyyy-mm-dd or week)
     *
     * @param array $subordinateids
     * @param int|null $startdate unix timestamp or null
     * @param int|null $enddate unix timestamp or null
     * @param string $timescale 'day'|'week'|'month' - used for timeseries grouping
     * @return array
     */
    public static function get_chart_data_no($subordinateids, $startdate = null, $enddate = null, $timescale = 'day')
    {
        global $DB;

        $out = [
            'levels' => [], // counts per level (1..5 and 'all' key)
            'totals' => ['enrolled' => 0, 'completed' => 0, 'badges' => 0, 'certificates' => 0],
            'timeseries' => ['labels' => [], 'enrolled' => [], 'completed' => []]
        ];

        if (empty($subordinateids)) {
            // Return zeros
            for ($i = 1; $i <= 5; $i++) {
                $out['levels'][$i] = 0;
            }
            $out['levels']['all'] = 0;
            return $out;
        }

        // 1) Levels: compute count per level (1..5) and overall
        // We'll compute by iteratively expanding subordinates per level using existing get_subordinates logic.
        $levels = [];
        $visited = [];
        $current = [$subordinateids[0]]; // placeholder — we'll need to compute levels relative to manager, but we want counts grouped by distance from the manager
        // Instead, caller should pass subordinateids that are all subordinates of a manager;
        // We'll compute levels by re-running BFS from each subordinate? Simpler: we will request levels by running get_subordinates for manager per level.
        // To keep this function generic, we expect $subordinateids to be all subordinates; so here we'll compute levels by proximity if possible.
        // However easiest approach is to compute levels counts by checking for each subordinate what minimum level they are at.
        // We'll derive minimum level by starting from manager(s). But we don't have manager id here.
        // To avoid changing caller signature, we will compute levels by re-invoking get_subordinates for each subordinate as root and measure reverse mapping is hard.
        // Simpler practical approach: map levels by using helper::get_subordinates per level from the manager(s) who called this function.
        // Therefore, this method expects subordinateids is the full subordinate list AND caller will pass $levelroots param (manager user id).
        // To avoid breaking signatures, we'll fallback to grouping all subordinates into 'all' and leave per-level zeros if we cannot determine distance.
        // So produce 'all' bucket and zeros for levels (the UI will show 'All' if levels zero). This can be improved later if you prefer.
        for ($i = 1; $i <= 5; $i++) {
            $out['levels'][$i] = 0;
        }
        $out['levels']['all'] = count($subordinateids);

        // 2) Totals & timeseries
        // We'll query enrolments, completions, badge counts and certificate issues for these users.
        list($inusersql, $userparams) = $DB->get_in_or_equal($subordinateids, SQL_PARAMS_NAMED, 'usr');

        $params = $userparams;

        // Enrolments: count unique user-course enrolments within date window (use ue.timecreated)
        $sqlenrol = "SELECT COUNT(DISTINCT ue.id) AS cnt
                       FROM {user_enrolments} ue
                       JOIN {enrol} e ON e.id = ue.enrolid
                       JOIN {course} c ON c.id = e.courseid
                      WHERE ue.userid $inusersql";
        if (!empty($startdate)) {
            $sqlenrol .= " AND ue.timecreated >= :startdate";
            $params['startdate'] = $startdate;
        }
        if (!empty($enddate)) {
            $sqlenrol .= " AND ue.timecreated <= :enddate";
            $params['enddate'] = $enddate;
        }
        $enrolled = (int)$DB->get_field_sql($sqlenrol, $params);

        // Completed: count unique course_completions rows with timecompleted inside filter
        $paramsc = $params;
        $sqlcomp = "SELECT COUNT(1) AS cnt FROM {course_completions} cc WHERE cc.userid $inusersql";
        if (!empty($startdate)) {
            $sqlcomp .= " AND cc.timecompleted >= :cstart";
            $paramsc['cstart'] = $startdate;
        }
        if (!empty($enddate)) {
            $sqlcomp .= " AND cc.timecompleted <= :cend";
            $paramsc['cend'] = $enddate;
        }
        $completed = (int)$DB->get_field_sql($sqlcomp, $paramsc);

        // Badges: sum badge_issued rows for the users in period
        $paramsb = $params;
        $sqlbadges = "SELECT COUNT(1) AS cnt FROM {badge_issued} bi WHERE bi.userid $inusersql";
        if (!empty($startdate)) {
            $sqlbadges .= " AND bi.dateissued >= :bstart";
            $paramsb['bstart'] = $startdate;
        }
        if (!empty($enddate)) {
            $sqlbadges .= " AND bi.dateissued <= :bend";
            $paramsb['bend'] = $enddate;
        }
        $badges = (int)$DB->get_field_sql($sqlbadges, $paramsb);

        // Certificates: count customcert_issues joined to customcert (ensure course match)
        $paramscert = $params;
        $sqlcert = "SELECT COUNT(1) AS cnt
                      FROM {customcert_issues} ci
                      JOIN {customcert} cc ON cc.id = ci.customcertid
                     WHERE ci.userid $inusersql";
        if (!empty($startdate)) {
            $sqlcert .= " AND ci.timecreated >= :certstart";
            $paramscert['certstart'] = $startdate;
        }
        if (!empty($enddate)) {
            $sqlcert .= " AND ci.timecreated <= :certend";
            $paramscert['certend'] = $enddate;
        }
        $certs = (int)$DB->get_field_sql($sqlcert, $paramscert);

        $out['totals']['enrolled'] = $enrolled;
        $out['totals']['completed'] = $completed;
        $out['totals']['badges'] = $badges;
        $out['totals']['certificates'] = $certs;

        // 3) Timeseries - produce labels and two series: enrolled and completed.
        // We'll fetch individual timestamps and bucket in PHP according to timescale.
        // Get enrolment timestamps
        $params_ts = $userparams;
        $sql_ts_enrol = "SELECT ue.timecreated AS ts FROM {user_enrolments} ue WHERE ue.userid $inusersql";
        if (!empty($startdate)) {
            $sql_ts_enrol .= " AND ue.timecreated >= :startdate";
            $params_ts['startdate'] = $startdate;
        }
        if (!empty($enddate)) {
            $sql_ts_enrol .= " AND ue.timecreated <= :enddate";
            $params_ts['enddate'] = $enddate;
        }
        $enrolrows = $DB->get_records_sql($sql_ts_enrol, $params_ts);

        // Get completion timestamps
        $params_ts_c = $userparams;
        $sql_ts_comp = "SELECT cc.timecompleted AS ts FROM {course_completions} cc WHERE cc.userid $inusersql AND cc.timecompleted IS NOT NULL";
        if (!empty($startdate)) {
            $sql_ts_comp .= " AND cc.timecompleted >= :startdate";
            $params_ts_c['startdate'] = $startdate;
        }
        if (!empty($enddate)) {
            $sql_ts_comp .= " AND cc.timecompleted <= :enddate";
            $params_ts_c['enddate'] = $enddate;
        }
        $comprows = $DB->get_records_sql($sql_ts_comp, $params_ts_c);

        // bucket function
        $buckets = [];
        $labels = [];

        // determine label format and bucket key generator depending on timescale
        if ($timescale === 'month') {
            // group by month YYYY-MM
            $getkey = function ($ts) {
                return date('Y-m', $ts);
            };
            $formatlabel = function ($k) {
                $d = DateTime::createFromFormat('Y-m', $k);
                return $d ? $d->format('M Y') : $k;
            };
        } else if ($timescale === 'week') {
            // group by ISO week: YYYY-Www
            $getkey = function ($ts) {
                $year = date('o', $ts);
                $week = date('W', $ts);
                return $year . '-W' . $week;
            };
            $formatlabel = function ($k) {
                // convert YYYY-Www to readable 'YYYY-ww'
                return $k;
            };
        } else {
            // default 'day'
            $getkey = function ($ts) {
                return date('Y-m-d', $ts);
            };
            $formatlabel = function ($k) {
                return $k;
            };
        }

        $enrolled_buckets = [];
        foreach ($enrolrows as $er) {
            $key = $getkey((int)$er->ts);
            if (!isset($enrolled_buckets[$key])) {
                $enrolled_buckets[$key] = 0;
            }
            $enrolled_buckets[$key]++;
        }
        $completed_buckets = [];
        foreach ($comprows as $cr) {
            $key = $getkey((int)$cr->ts);
            if (!isset($completed_buckets[$key])) {
                $completed_buckets[$key] = 0;
            }
            $completed_buckets[$key]++;
        }

        // union keys sorted ascending
        $allkeys = array_unique(array_merge(array_keys($enrolled_buckets), array_keys($completed_buckets)));
        sort($allkeys);

        foreach ($allkeys as $k) {
            $labels[] = $formatlabel($k);
            $out['timeseries']['enrolled'][] = isset($enrolled_buckets[$k]) ? (int)$enrolled_buckets[$k] : 0;
            $out['timeseries']['completed'][] = isset($completed_buckets[$k]) ? (int)$completed_buckets[$k] : 0;
        }
        $out['timeseries']['labels'] = $labels;

        return $out;
    }

    /**
     * get_chart_data - returns structured chart data including timeseries for enrollments, completions,
     * badges and certificates, bucketed by day (YYYY-MM-DD).
     *
     * @param array $subordinateids
     * @param int|null $startdate unix timestamp or null
     * @param int|null $enddate unix timestamp or null
     * @param string $timescale not heavily used currently ('day' default)
     * @param int $use_startdate 0/1
     * @param int $use_enddate 0/1
     * @return array
     */
    public static function get_chart_data($subordinateids, $startdate = null, $enddate = null, $timescale = 'day', $use_startdate = 0, $use_enddate = 0)
    {
        global $DB,$USER;

         //Rashid:ONLY ADMIN LOGON
        if (is_siteadmin($USER)) {
            $subordinateids = $DB->get_fieldset_select(
                'user',
                'id',
                "deleted = 0 AND confirmed = 1 AND id NOT IN (1, 2)"
            );
        }


        $out = [
            'levels' => [],
            'totals' => ['enrolled' => 0, 'completed' => 0, 'badges' => 0, 'certificates' => 0],
            'timeseries' => ['labels' => [], 'enrolled' => [], 'completed' => []],
            'timeseries_badges' => ['labels' => [], 'badges' => []],
            'timeseries_certs' => ['labels' => [], 'certs' => []],
        ];

        if (empty($subordinateids)) {
            for ($i = 1; $i <= 5; $i++) {
                $out['levels'][$i] = 0;
            }
            $out['levels']['all'] = 0;
            return $out;
        }

        list($inusersql, $userparams) = $DB->get_in_or_equal($subordinateids, SQL_PARAMS_NAMED, 'usr');

        // Levels (placeholder)
        for ($i = 1; $i <= 5; $i++) {
            $out['levels'][$i] = 0;
        }
        $out['levels']['all'] = count($subordinateids);

        // date conditions
        $params = $userparams;
        $dateconds_enrol = '';
        $dateconds_comp = '';
        $dateconds_badge = '';
        $dateconds_cert = '';

        if (!empty($use_startdate) && !empty($startdate)) {
            $params['startdate'] = $startdate;
            $dateconds_enrol .= " AND ue.timecreated >= :startdate";
            $dateconds_comp  .= " AND cc.timecompleted >= :startdate";
            $dateconds_badge .= " AND bi.dateissued >= :startdate";
            $dateconds_cert  .= " AND ci.timecreated >= :startdate";
        }
        if (!empty($use_enddate) && !empty($enddate)) {
            $params['enddate'] = $enddate;
            $dateconds_enrol .= " AND ue.timecreated <= :enddate";
            $dateconds_comp  .= " AND cc.timecompleted <= :enddate";
            $dateconds_badge .= " AND bi.dateissued <= :enddate";
            $dateconds_cert  .= " AND ci.timecreated <= :enddate";
        }

        // totals
        $sqlenrol = "SELECT COUNT(DISTINCT ue.id) AS cnt
                       FROM {user_enrolments} ue
                       JOIN {enrol} e ON e.id = ue.enrolid
                      WHERE ue.userid $inusersql $dateconds_enrol";
        $enrolled = (int)$DB->get_field_sql($sqlenrol, $params);

        $sqlcomp = "SELECT COUNT(1) AS cnt FROM {course_completions} cc WHERE cc.userid $inusersql AND cc.timecompleted IS NOT NULL $dateconds_comp";
        $completed = (int)$DB->get_field_sql($sqlcomp, $params);

        $sqlbadges = "SELECT COUNT(1) AS cnt FROM {badge_issued} bi WHERE bi.userid $inusersql $dateconds_badge";
        $badges = (int)$DB->get_field_sql($sqlbadges, $params);

        $sqlcert = "SELECT COUNT(1) AS cnt
                      FROM {customcert_issues} ci
                      JOIN {customcert} c ON c.id = ci.customcertid
                     WHERE ci.userid $inusersql $dateconds_cert";
        $certs = (int)$DB->get_field_sql($sqlcert, $params);

        $out['totals']['enrolled'] = $enrolled;
        $out['totals']['completed'] = $completed;
        $out['totals']['badges'] = $badges;
        $out['totals']['certificates'] = $certs;

        // timeseries queries (raw timestamps)
        $params_ts = $userparams;
        if (!empty($use_startdate) && !empty($startdate)) {
            $params_ts['startdate'] = $startdate;
        }
        if (!empty($use_enddate) && !empty($enddate)) {
            $params_ts['enddate'] = $enddate;
        }

        // enrolments
        $sql_ts_enrol = "SELECT ue.timecreated AS ts FROM {user_enrolments} ue WHERE ue.userid $inusersql";

        $sql_ts_enrol = "SELECT ue.id AS recid, ue.timecreated AS ts
                   FROM {user_enrolments} ue
                  WHERE ue.userid $inusersql";

        if (!empty($use_startdate) && !empty($startdate)) {
            $sql_ts_enrol .= " AND ue.timecreated >= :startdate";
        }
        if (!empty($use_enddate) && !empty($enddate)) {
            $sql_ts_enrol .= " AND ue.timecreated <= :enddate";
        }

        // $sql_ts_enrol = "SELECT ue.id AS recid, ue.timecreated AS ts
        //            FROM {user_enrolments} ue
        //           WHERE ue.userid $inusersql";
        // if (!empty($use_startdate) && !empty($startdate)) {
        //     $sql_ts_enrol .= " AND ue.timecreated >= :startdate";
        // }
        // if (!empty($use_enddate) && !empty($enddate)) {
        //     $sql_ts_enrol .= " AND ue.timecreated <= :enddate";
        // }

        
        $enrolrows = $DB->get_records_sql($sql_ts_enrol, $params_ts);

        // completions
        $sql_ts_comp = "SELECT  cc.timecompleted AS ts FROM {course_completions} cc WHERE cc.userid $inusersql AND cc.timecompleted IS NOT NULL";
        if (!empty($use_startdate) && !empty($startdate)) {
            $sql_ts_comp .= " AND cc.timecompleted >= :startdate";
        }
        if (!empty($use_enddate) && !empty($enddate)) {
            $sql_ts_comp .= " AND cc.timecompleted <= :enddate";
        }
        $comprows = $DB->get_records_sql($sql_ts_comp, $params_ts);

        // badges
        $sql_ts_badge = "SELECT bi.dateissued AS ts FROM {badge_issued} bi WHERE bi.userid $inusersql";
        if (!empty($use_startdate) && !empty($startdate)) {
            $sql_ts_badge .= " AND bi.dateissued >= :startdate";
        }
        if (!empty($use_enddate) && !empty($enddate)) {
            $sql_ts_badge .= " AND bi.dateissued <= :enddate";
        }
        $badgerows = $DB->get_records_sql($sql_ts_badge, $params_ts);

        // customcert issues
        $sql_ts_cert = "SELECT ci.timecreated AS ts FROM {customcert_issues} ci WHERE ci.userid $inusersql";
        if (!empty($use_startdate) && !empty($startdate)) {
            $sql_ts_cert .= " AND ci.timecreated >= :startdate";
        }
        if (!empty($use_enddate) && !empty($enddate)) {
            $sql_ts_cert .= " AND ci.timecreated <= :enddate";
        }
        $certrows = $DB->get_records_sql($sql_ts_cert, $params_ts);

        // bucket by day
        $enrolled_buckets = [];
        foreach ($enrolrows as $er) {
            $key = date('Y-m-d', (int)$er->ts);
            if (!isset($enrolled_buckets[$key])) $enrolled_buckets[$key] = 0;
            $enrolled_buckets[$key]++;
        }
        $completed_buckets = [];
        foreach ($comprows as $cr) {
            $key = date('Y-m-d', (int)$cr->ts);
            if (!isset($completed_buckets[$key])) $completed_buckets[$key] = 0;
            $completed_buckets[$key]++;
        }
        $badge_buckets = [];
        foreach ($badgerows as $br) {
            $key = date('Y-m-d', (int)$br->ts);
            if (!isset($badge_buckets[$key])) $badge_buckets[$key] = 0;
            $badge_buckets[$key]++;
        }
        $cert_buckets = [];
        foreach ($certrows as $crr) {
            $key = date('Y-m-d', (int)$crr->ts);
            if (!isset($cert_buckets[$key])) $cert_buckets[$key] = 0;
            $cert_buckets[$key]++;
        }

        $allkeys = array_unique(array_merge(array_keys($enrolled_buckets), array_keys($completed_buckets), array_keys($badge_buckets), array_keys($cert_buckets)));
        sort($allkeys);

        foreach ($allkeys as $k) {
            $out['timeseries']['labels'][] = $k;
            $out['timeseries']['enrolled'][] = isset($enrolled_buckets[$k]) ? (int)$enrolled_buckets[$k] : 0;
            $out['timeseries']['completed'][] = isset($completed_buckets[$k]) ? (int)$completed_buckets[$k] : 0;

            $out['timeseries_badges']['labels'][] = $k;
            $out['timeseries_badges']['badges'][] = isset($badge_buckets[$k]) ? (int)$badge_buckets[$k] : 0;

            $out['timeseries_certs']['labels'][] = $k;
            $out['timeseries_certs']['certs'][] = isset($cert_buckets[$k]) ? (int)$cert_buckets[$k] : 0;
        }

        return $out;
    }
}
