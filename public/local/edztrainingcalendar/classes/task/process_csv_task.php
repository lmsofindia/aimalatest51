<?php

namespace local_edztrainingcalendar\task;

defined('MOODLE_INTERNAL') || die();

use core\notification;
use core_user;
use stdClass;

class process_csv_task extends \core\task\scheduled_task
{
    public function get_name(): string
    {
        return get_string('processcsvtask', 'local_edztrainingcalendar');
    }

    public function execute()
    {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot . '/enrol/locallib.php');
        require_once($CFG->dirroot . '/cohort/lib.php');

        // Fetch a limited number of unprocessed rows at a time to avoid long runs.
        $rows = $DB->get_records('local_edztrainingcalendar_journey', ['processed_flag' => 0], 'uploadeddate ASC', '*', 0, 200);
        if (empty($rows)) {
            return;
        }

        // Get a sensible default role id (student or fallback).
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        if (!$roleid) {
            // Fallback to common student id 5 if student role doesn't exist.
            debugging('Role "student" not found; falling back to role id 5', DEBUG_DEVELOPER);
            $roleid = 5;
        }

        // Site noreply user for sending emails.
        $noreply = core_user::get_noreply_user();

        foreach ($rows as $r) {
            $errormsgs = [];
            try {
                // Basic sanity for required fields.
                if (empty($r->useremail)) {
                    throw new \moodle_exception('missinguseremail', 'local_edztrainingcalendar', '', null, 'Missing user email');
                }
                if (empty($r->courseshortname)) {
                    throw new \moodle_exception('missingcourseshortname', 'local_edztrainingcalendar', '', null, 'Missing course shortname');
                }

                // 1) find user by email
                $user = $DB->get_record('user', ['email' => $r->useremail, 'deleted' => 0], '*', IGNORE_MISSING);
                if (!$user) {
                    throw new \moodle_exception('usernotfound', 'local_edztrainingcalendar', '', null, "User not found: {$r->useremail}");
                }

                // 2) find course by shortname
                $course = $DB->get_record('course', ['shortname' => $r->courseshortname], '*', IGNORE_MISSING);
                if (!$course) {
                    throw new \moodle_exception('coursenotfound', 'local_edztrainingcalendar', '', null, "Course not found: {$r->courseshortname}");
                }

                $context = \context_course::instance($course->id);

                // 3) handle cohort identifier if present
                $cohort = null;
                if (!empty($r->cohortidentifier)) {
                    $identifier = trim($r->cohortidentifier);

                    // Try idnumber first (string), then numeric id fallback.
                    $cohort = $DB->get_record('cohort', ['idnumber' => $identifier], '*', IGNORE_MISSING);
                    if (!$cohort && ctype_digit((string)$identifier)) {
                        // numeric id fallback
                        $cohort = $DB->get_record('cohort', ['id' => (int)$identifier], '*', IGNORE_MISSING);
                    }

                    if ($cohort) {
                        // ensure user is member of cohort
                        // cohort_add_member will quietly return if already in cohort
                        try {
                            cohort_add_member($cohort->id, $user->id);
                        } catch (\Exception $ex) {
                            // non-fatal: log, continue
                            $errormsgs[] = "cohort add failed: " . $ex->getMessage();
                        }

                        // Make sure course has a cohort enrol instance for this cohort (so cohort-sync works).
                        $cohortinstance = $DB->get_record_select(
                            'enrol',
                            'courseid = :cid AND enrol = :enrol AND customint1 = :cohortid',
                            ['cid' => $course->id, 'enrol' => 'cohort', 'cohortid' => $cohort->id],
                            '*',
                            IGNORE_MISSING
                        );

                        if (!$cohortinstance) {
                            // Try to add cohort enrol instance via plugin API, falling back to direct DB insert if needed.
                            $cohortplugin = enrol_get_plugin('cohort');
                            if ($cohortplugin) {
                                // prefer plugin API; check for useful methods
                                if (method_exists($cohortplugin, 'add_custom_instance')) {
                                    // pass settings - actual keys may vary by Moodle version; plugin will map them.
                                    $settings = ['cohortid' => $cohort->id, 'roleid' => $roleid, 'status' => 0];
                                    try {
                                        $cohortplugin->add_custom_instance($course, $settings);
                                    } catch (\Throwable $e) {
                                        // fallback: try add_default_instance and then update record
                                        try {
                                            $instanceid = $cohortplugin->add_default_instance($course->id);
                                            if ($instanceid) {
                                                // update the record in DB
                                                $DB->set_field('enrol', 'customint1', $cohort->id, ['id' => $instanceid]);
                                                $DB->set_field('enrol', 'roleid', $roleid, ['id' => $instanceid]);
                                            }
                                        } catch (\Throwable $e2) {
                                            $errormsgs[] = 'failed to add cohort enrol instance: ' . $e2->getMessage();
                                        }
                                    }
                                } else if (method_exists($cohortplugin, 'add_instance')) {
                                    // some Moodle versions expose add_instance
                                    try {
                                        $cohortplugin->add_instance($course, ['cohortid' => $cohort->id, 'roleid' => $roleid]);
                                    } catch (\Throwable $e) {
                                        $errormsgs[] = 'failed to add cohort instance via add_instance: ' . $e->getMessage();
                                    }
                                } else {
                                    // plugin does not expose add helpers — insert a record directly:
                                    $ins = new stdClass();
                                    $ins->courseid = $course->id;
                                    $ins->enrol = 'cohort';
                                    $ins->status = 0;
                                    $ins->roleid = $roleid;
                                    $ins->customint1 = $cohort->id; // cohortid is stored here by convention
                                    $ins->timecreated = time();
                                    try {
                                        $DB->insert_record('enrol', $ins);
                                    } catch (\Exception $e) {
                                        $errormsgs[] = 'failed to create enrol instance record: ' . $e->getMessage();
                                    }
                                }
                            } else {
                                $errormsgs[] = 'cohort enrol plugin not available';
                            }

                            // re-fetch instance if it exists now
                            $cohortinstance = $DB->get_record_select(
                                'enrol',
                                'courseid = :cid AND enrol = :enrol AND customint1 = :cohortid',
                                ['cid' => $course->id, 'enrol' => 'cohort', 'cohortid' => $cohort->id],
                                '*',
                                IGNORE_MISSING
                            );
                        }

                        // If cohort instance exists, the cohort-sync should enrol the user on cron.
                        // But to guarantee immediate enrolment, we'll still ensure the user has a course enrolment.
                    }
                }

                // 4) ensure user is actually enrolled in course (if not, enrol via manual)
                $context = \context_course::instance($course->id);
                if (!is_enrolled($context, $user->id)) {
                    // prefer to use manual enrol plugin for immediate enrolment
                    $manual = enrol_get_plugin('manual');
                    if ($manual) {
                        // find an existing manual enrol instance for the course
                        $instances = enrol_get_instances($course->id, true);
                        $manualinstance = null;
                        foreach ($instances as $inst) {
                            if ($inst->enrol === 'manual') {
                                $manualinstance = $inst;
                                break;
                            }
                        }
                        // if none, attempt to add one
                        if (!$manualinstance) {
                            try {
                                $tmpid = $manual->add_default_instance($course->id);
                                // re-fetch instance record
                                $manualinstance = $DB->get_record('enrol', ['id' => $tmpid]);
                            } catch (\Throwable $e) {
                                // can't add manual instance - report and continue
                                $errormsgs[] = 'failed to add manual enrol instance: ' . $e->getMessage();
                                $manualinstance = null;
                            }
                        }

                        if ($manualinstance) {
                            try {
                                // enrol plugin method: enrol_user(instance, userid, roleid, timestart, timeend, status)
                                $timestart = !empty($r->timestamp_coursestartdate) ? intval($r->timestamp_coursestartdate) : time();
                                $timeend = 0;
                                $manual->enrol_user($manualinstance, $user->id, $roleid, $timestart, $timeend, ENROL_USER_ACTIVE);
                            } catch (\Throwable $e) {
                                $errormsgs[] = 'manual enrol failed: ' . $e->getMessage();
                            }
                        } else {
                            $errormsgs[] = 'no manual enrol instance available and could not create one';
                        }
                    } else {
                        $errormsgs[] = 'manual enrol plugin not available';
                    }
                }

                // 5) Success — mark processed_flag = 1
                $r->processed_flag = 1;
                $r->errormsg = implode('; ', $errormsgs);
                $DB->update_record('local_edztrainingcalendar_journey', $r);

                // 6) Email notification to uploader (if uploader exists)
                if (!empty($r->uploadedby)) {
                    $uploader = $DB->get_record('user', ['id' => $r->uploadedby], '*', IGNORE_MISSING);
                    if ($uploader) {
                        $subject = "Training enrolment processed for {$r->useremail}";
                        $message = "Row ID: {$r->id}\nUser: {$r->useremail}\nCourse: {$r->courseshortname}\nResult: SUCCESS";
                        if (!empty($r->errormsg)) {
                            $message .= "\nNotes: " . $r->errormsg;
                        }
                        email_to_user($uploader, $noreply, $subject, $message);
                    }
                }
            } catch (\moodle_exception $me) {
                // Known validation errors: mark as processed with error
                $r->processed_flag = 2;
                $r->errormsg = $me->getMessage();
                $DB->update_record('local_edztrainingcalendar_journey', $r);

                // notify uploader
                if (!empty($r->uploadedby)) {
                    $uploader = $DB->get_record('user', ['id' => $r->uploadedby], '*', IGNORE_MISSING);
                    if ($uploader) {
                        $subject = "Training enrolment failed for {$r->useremail}";
                        $message = "Row ID: {$r->id}\nUser: {$r->useremail}\nCourse: {$r->courseshortname}\nError: " . $r->errormsg;
                        email_to_user($uploader, $noreply, $subject, $message);
                    }
                }
            } catch (\Throwable $e) {
                // Unexpected exceptions
                $r->processed_flag = 2;
                $r->errormsg = 'Exception: ' . $e->getMessage();
                $DB->update_record('local_edztrainingcalendar_journey', $r);

                if (!empty($r->uploadedby)) {
                    $uploader = $DB->get_record('user', ['id' => $r->uploadedby], '*', IGNORE_MISSING);
                    if ($uploader) {
                        $subject = "Training enrolment error for {$r->useremail}";
                        $message = "Row ID: {$r->id}\nUser: {$r->useremail}\nCourse: {$r->courseshortname}\nException: " . $e->getMessage();
                        email_to_user($uploader, $noreply, $subject, $message);
                    }
                }
                // also log to debugging
                debugging('Error processing edztrainingcalendar row ' . $r->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        } // foreach rows
    } // execute
}
