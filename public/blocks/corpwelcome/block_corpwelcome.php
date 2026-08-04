<?php
defined('MOODLE_INTERNAL') || die();

class block_corpwelcome extends block_base {

    public function init(): void {
        $this->title = get_string('pluginname', 'block_corpwelcome');
    }

    public function hide_header(): bool { return true; }

    public function has_config(): bool { return true; }
    public function instance_allow_config(): bool { return true; }
    public function applicable_formats(): array {
        return ['my' => true, 'site-index' => true, 'course-view' => false];
    }

    public function specialization(): void {
        if (!empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        }
    }

    public function get_content(): stdClass {
        global $USER, $DB, $OUTPUT, $PAGE, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content         = new stdClass();
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            $this->content->text = '';
            return $this->content;
        }

        // ── Read block settings ───────────────────────────────────────────────
        $blocktheme = 'dark';
        if (!empty($this->config->blocktheme)) {
            $blocktheme = clean_param($this->config->blocktheme, PARAM_ALPHA);
            if (!in_array($blocktheme, ['dark', 'light', 'hero'])) {
                $blocktheme = 'dark';
            }
        }

        // Selected category IDs (empty = show all up to 5).
        $selectedcatids = [];
        if (!empty($this->config->categories) && is_array($this->config->categories)) {
            $selectedcatids = array_filter(array_map('intval', $this->config->categories));
        }

        $templatemap = [
            'dark'  => 'block_corpwelcome/content',
            'light' => 'block_corpwelcome/content_light',
            'hero'  => 'block_corpwelcome/content_hero',
        ];
        $template = $templatemap[$blocktheme] ?? 'block_corpwelcome/content';

        // ── Accent / background colour ────────────────────────────────────────
        // Dark theme: whole-block background. Light theme: accent colour used
        // for the tagline + primary button (via --cwl-accent). Default differs
        // per style so an unconfigured block keeps its intended look.
        if ($blocktheme === 'light') {
            $bgcolor = '#f97316';
        } else if ($blocktheme === 'hero') {
            $bgcolor = '#4f46e5';
        } else {
            $bgcolor = '#2d1465';
        }
        if (!empty($this->config->bgcolor)) {
            $bgcolor = clean_param($this->config->bgcolor, PARAM_TEXT);
        }

        // ── Optional background image (hero style) — SITE-LEVEL setting ───────
        // The image is uploaded ONCE by a site admin (Site administration →
        // Plugins → Blocks → Corporate welcome) and stored in the SYSTEM
        // context, so every dashboard — the default, each user's own copy, and
        // any future clone — reads the same file. This is why it must not be a
        // per-block-instance upload: each user's dashboard is a separate block
        // instance with its own (empty) file area, so a per-instance image only
        // ever shows on the exact block the admin uploaded to.
        $bgimageurl = '';
        $syscontext = context_system::instance();
        $fs         = get_file_storage();
        $files      = $fs->get_area_files($syscontext->id, 'block_corpwelcome',
            'backgroundimage', 0, 'itemid, filepath, filename', false);
        if ($files) {
            $file       = reset($files);
            $bgimageurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);
        }

        // Overlay darkness (0–80%) — also a site-level setting. Default 40%.
        $overlay = 0.4;
        $od      = get_config('block_corpwelcome', 'overlaydarkness');
        if ($od !== false && $od !== '') {
            $overlay = max(0, min(90, (int)$od)) / 100;
        }

        // Build the hero background style: image + scrim, or flat colour.
        $hasbgimage = ($blocktheme === 'hero' && $bgimageurl !== '');
        if ($hasbgimage) {
            $herostyle = sprintf(
                'background-color: %s; background-image: linear-gradient(rgba(0,0,0,%s), '
                . 'rgba(0,0,0,%s)), url(\'%s\'); background-size: cover; background-position: center;',
                $bgcolor, $overlay, $overlay, $bgimageurl
            );
        } else {
            $herostyle = 'background: ' . $bgcolor . ';';
        }

        // ── Time label ────────────────────────────────────────────────────────
        $daynames  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $dayofweek = strtoupper($daynames[(int)date('w')]);
        $hour      = (int)userdate(time(), '%H');
        $period    = $hour < 12 ? 'MORNING' : ($hour < 17 ? 'AFTERNOON' : 'EVENING');
        $daylabel  = $dayofweek . ' ' . $period;

        // ── Live data ─────────────────────────────────────────────────────────
        require_once($CFG->dirroot . '/course/lib.php');

        // Enrolled courses.
        $courses       = enrol_get_users_courses($USER->id, true, 'id,fullname,shortname,category,visible');
        $enrolledcount = count($courses);
        $courseids     = array_keys($courses);

        // 30-day window for delta badges.
        $since30 = time() - (30 * DAYSECS);

        // Completed total + delta.
        $completedcount = 0;
        $newcompleted   = 0;
        if (!empty($courseids)) {
            [$insql, $ip] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $ip['userid'] = $USER->id;
            $completedcount = (int)$DB->count_records_sql(
                "SELECT COUNT(cc.id) FROM {course_completions} cc
                  WHERE cc.userid = :userid AND cc.timecompleted IS NOT NULL AND cc.course $insql", $ip);
            $ip2          = $ip;
            $ip2['since'] = $since30;
            $newcompleted = (int)$DB->count_records_sql(
                "SELECT COUNT(cc.id) FROM {course_completions} cc
                  WHERE cc.userid = :userid AND cc.timecompleted >= :since AND cc.course $insql", $ip2);
        }

        // New enrollments delta.
        $newenrolled = (int)$DB->count_records_sql(
            "SELECT COUNT(ue.id) FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE ue.userid = ? AND ue.timecreated >= ?", [$USER->id, $since30]);

        // Badges total + delta.
        $badgecount = (int)$DB->count_records('badge_issued', ['userid' => $USER->id, 'visible' => 1]);
        $newbadges  = (int)$DB->count_records_select('badge_issued',
            'userid = ? AND dateissued >= ? AND visible = 1', [$USER->id, $since30]);

        // Certificates total + delta.
        $certcount = 0;
        $newcerts  = 0;
        if ($DB->get_manager()->table_exists('customcert_issues')) {
            $certcount = (int)$DB->count_records('customcert_issues', ['userid' => $USER->id]);
            $newcerts  = (int)$DB->count_records_select('customcert_issues',
                'userid = ? AND timecreated >= ?', [$USER->id, $since30]);
        }

        // Streak.
        $streak = $this->get_streak($USER->id);

        // Monthly goal progress — completions within the CURRENT calendar month
        // measured against a configurable target (block setting, default 5).
        $goalTarget = 5;
        if (!empty($this->config->monthlygoal)) {
            $goalTarget = max(1, (int)$this->config->monthlygoal);
        }
        $monthstart         = make_timestamp((int)date('Y'), (int)date('n'), 1, 0, 0, 0);
        $completedthismonth = 0;
        if (!empty($courseids)) {
            [$msinsql, $msp] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $msp['userid'] = $USER->id;
            $msp['mstart'] = $monthstart;
            $completedthismonth = (int)$DB->count_records_sql(
                "SELECT COUNT(cc.id) FROM {course_completions} cc
                  WHERE cc.userid = :userid AND cc.timecompleted >= :mstart AND cc.course $msinsql", $msp);
        }
        $goalPct       = min(100, (int)round(($completedthismonth / max(1, $goalTarget)) * 100));
        $goalremaining = max(0, $goalTarget - $completedthismonth);

        // Categories for pills — from enrolled courses, filtered by admin selection.
        $catdata = [];
        if (!empty($courseids)) {
            [$insql2, $ip3] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            // If admin selected specific categories, restrict to those.
            $catcond = '';
            if (!empty($selectedcatids)) {
                [$catinsql, $catparams] = $DB->get_in_or_equal($selectedcatids, SQL_PARAMS_NAMED, 'cat');
                $catcond = " AND cc.id $catinsql";
                $ip3     = array_merge($ip3, $catparams);
            }
            $limit = empty($selectedcatids) ? 5 : count($selectedcatids);
            $cats  = $DB->get_records_sql(
                "SELECT DISTINCT cc.id, cc.name FROM {course_categories} cc
                   JOIN {course} c ON c.category = cc.id
                  WHERE c.id $insql2 $catcond ORDER BY cc.name", $ip3, 0, $limit);
            foreach ($cats as $cat) {
                $catdata[] = ['id' => (int)$cat->id, 'name' => format_string($cat->name)];
            }
        }

        // Motivational sub-line.
        $lastcourse = null;
        if (!empty($courseids)) {
            [$insql3, $ip4] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $ip4['userid']  = $USER->id;
            $row = $DB->get_record_sql(
                "SELECT courseid FROM {logstore_standard_log}
                  WHERE userid = :userid AND courseid > 0 AND courseid $insql3
               ORDER BY timecreated DESC", $ip4, IGNORE_MULTIPLE);
            if ($row && isset($courses[$row->courseid])) {
                $lastcourse = format_string($courses[$row->courseid]->fullname);
            }
        }

        $data = [
            'daylabel'            => $daylabel,
            'username'            => fullname($USER),
            'goalPct'             => $goalPct,
            'goalTarget'          => $goalTarget,
            'goalremaining'       => $goalremaining,
            'goalremainingplural' => $goalremaining !== 1,
            'lastcourse'          => $lastcourse,
            'enrolledcount'       => $enrolledcount,
            'completedcount'      => $completedcount,
            'certcount'           => $certcount,
            'badgecount'          => $badgecount,
            'streak'              => $streak,
            'hasstreak'           => $streak > 0,
            'newenrolled'         => $newenrolled,
            'newcompleted'        => $newcompleted,
            'newcerts'            => $newcerts,
            'newbadges'           => $newbadges,
            'hasnewenrolled'      => $newenrolled > 0,
            'hasnewcompleted'     => $newcompleted > 0,
            'hasnewcerts'         => $newcerts > 0,
            'hasnewbadges'        => $newbadges > 0,
            'categories'          => array_values($catdata),
            'hascategories'       => !empty($catdata),
            'bgcolor'             => $bgcolor,
            'herostyle'           => $herostyle,
            'hasbgimage'          => $hasbgimage,
            'instanceid'          => $this->instance->id,
        ];

        $PAGE->requires->js_call_amd('block_corpwelcome/corpwelcome', 'init',
            [['instanceid' => (int)$this->instance->id]]);

        $this->content->text = $OUTPUT->render_from_template($template, $data);
        return $this->content;
    }

    private function get_streak(int $userid): int {
        global $DB;
        $cachedval  = get_user_preferences('block_corpwelcome_streak', null, $userid);
        $cachedtime = (int)get_user_preferences('block_corpwelcome_streak_time', 0, $userid);
        if ($cachedval !== null && (time() - $cachedtime) < HOURSECS * 6) {
            return (int)$cachedval;
        }
        $since = time() - (60 * DAYSECS);
        $rows  = $DB->get_records_sql(
            "SELECT DISTINCT FLOOR(timecreated / 86400) AS daynum
               FROM {logstore_standard_log}
              WHERE userid = ? AND timecreated >= ?
           ORDER BY daynum DESC",
            [$userid, $since]);
        $days   = array_column(array_values($rows), 'daynum');
        $today  = (int)floor(time() / 86400);
        $streak = 0;
        for ($i = 0; $i < 61; $i++) {
            if (in_array($today - $i, $days)) {
                $streak++;
            } elseif ($i === 0) {
                continue;
            } else {
                break;
            }
        }
        set_user_preference('block_corpwelcome_streak', $streak, $userid);
        set_user_preference('block_corpwelcome_streak_time', time(), $userid);
        return $streak;
    }
}
