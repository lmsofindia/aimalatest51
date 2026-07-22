<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp sidebar utility — builds grouped, role-aware navigation.
 *
 * Navigation is organised into labelled groups (Learning, Analytics, Events,
 * Administration).  Each group is only rendered when it contains at least one
 * visible item for the current user.
 *
 * The user footer strip is now a clickable button that expands a small
 * dropdown with Profile and Logout links.
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_edzcorp\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Sidebar navigation builder.
 */
class sidebar {

    /** @var \moodle_page The current page. */
    protected \moodle_page $page;

    /** @var array Grouped nav structure built in the constructor. */
    protected array $groups = [];

    /**
     * Constructor.
     *
     * @param \moodle_page $page  The current Moodle page object.
     */
    public function __construct(\moodle_page $page) {
        $this->page   = $page;
        $this->groups = $this->build_nav_groups();
        $this->mark_active();
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Returns the full context array consumed by the sidenav mustache partial.
     *
     * @return array
     */
    public function get_context(): array {
        global $USER, $SITE;

        $theme   = \theme_config::load('edzcorp');
        $context = \context_system::instance();

        // ---- Sidebar logo ----
        $sidebarlogourl = '';
        if (!empty($theme->settings->sidebarlogo)) {
            $sidebarlogourl = $theme->setting_file_url('sidebarlogo', 'sidebarlogo');
        }

        // ---- User data (footer strip + dropdown) ----
        $isloggedin  = isloggedin() && !isguestuser();
        $useravatar  = '';
        $fullname    = '';
        $userrole    = '';
        $profileurl  = '';
        $logouturl   = '';

        if ($isloggedin) {
            $userpicture = new \user_picture($USER);
            $userpicture->size = 1; // Small thumbnail.
            $useravatar  = $userpicture->get_url($this->page)->out(false);
            $fullname    = fullname($USER);
            $userrole    = $this->get_primary_role_name();
            $profileurl  = (new \moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false);
            $logouturl   = (new \moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false);
        }

        return [
            'homeurl'        => (new \moodle_url('/', ['redirect' => 0]))->out(false),
            'sitename'       => format_string($SITE->shortname, true, ['context' => $context]),
            'sidebarlogourl' => $sidebarlogourl,
            'navgroups'      => $this->groups_for_template(),
            'isloggedin'     => $isloggedin,
            'useravatar'     => $useravatar,
            'fullname'       => $fullname,
            'userrole'       => $userrole,
            'profileurl'     => $profileurl,
            'logouturl'      => $logouturl,
        ];
    }

    // -------------------------------------------------------------------------
    // Navigation group building
    // -------------------------------------------------------------------------

    /**
     * Assembles the ordered list of nav groups for the current user.
     *
     * Each group is an associative array:
     *   [
     *     'grouplabel' => string,
     *     'items'      => array of item arrays (see make_item()),
     *   ]
     *
     * A group is only added when it contains at least one visible item.
     *
     * @return array
     */
    protected function build_nav_groups(): array {
        $groups = [];

        $loggedin = isloggedin() && !isguestuser();
        $sysctx   = \context_system::instance();

        // ==================================================================
        // GROUP 1 — Learning
        // Home is always visible; dashboard & courses require a real login.
        // ==================================================================
        $learning = [];

        $learning[] = $this->make_item(
            'home',
            get_string('navhome', 'theme_edzcorp'),
            'fa-house',
            // redirect=0 prevents Moodle bouncing logged-in users to /my.
            new \moodle_url('/', ['redirect' => 0])
        );

        if ($loggedin) {
            $learning[] = $this->make_item(
                'dashboard',
                get_string('navdashboard', 'theme_edzcorp'),
                'fa-gauge-high',
                new \moodle_url('/my')
            );

            // Catalogues — local_edzcatalogue plugin (only when installed).
            if (\core_component::get_plugin_directory('local', 'edzcatalogue') !== null) {
                $learning[] = $this->make_item(
                    'catalogues',
                    get_string('navcatalogues', 'theme_edzcorp'),
                    'fa-layer-group',
                    new \moodle_url('/local/edzcatalogue/index.php')
                );
            }

            // Resources — local_resource plugin (only when installed).
            if (\core_component::get_plugin_directory('local', 'resource') !== null) {
                $learning[] = $this->make_item(
                    'resources',
                    get_string('navresources', 'theme_edzcorp'),
                    'fa-folder-open',
                    new \moodle_url('/local/resource/index.php')
                );
            }

            // Webinars — catalogue filtered to the webinar categories.
            if (\core_component::get_plugin_directory('local', 'edzcatalogue') !== null) {
                $learning[] = $this->make_item(
                    'webinars',
                    get_string('navwebinars', 'theme_edzcorp'),
                    'fa-video',
                    new \moodle_url('/local/edzcatalogue/index.php', [
                        'page' => 'program',
                        'course_category' => '2,30',
                    ])
                );
            }

            // Onboarding — local_edzonboard personal onboarding journey.
            $learning[] = $this->make_item(
                'onboarding',
                get_string('navonboarding', 'theme_edzcorp'),
                'fa-rocket',
                new \moodle_url('/local/edzonboard/myonboarding.php')
            );
        }

        // Course catalogue — local_coursecatalogue, visible to ALL users.
        $learning[] = $this->make_item(
            'coursecatalogue',
            get_string('navcoursecatalogue', 'theme_edzcorp'),
            'fa-book-open',
            new \moodle_url('/local/coursecatalogue/')
        );

        // Manage team — local_edzteams, only for managers/admins holding the
        // viewteam capability.
        if ($loggedin && has_capability('local/edzteams:viewteam', $sysctx)) {
            $learning[] = $this->make_item(
                'manageteam',
                get_string('navmanageteam', 'theme_edzcorp'),
                'fa-users-gear',
                new \moodle_url('/local/edzteams/')
            );
        }

        $groups[] = [
            'grouplabel' => get_string('navgrouplearning', 'theme_edzcorp'),
            'items'      => $learning,
        ];

        // ==================================================================
        // GROUP — Authoring
        // Course-authoring tools (AI SCORM builder + AI course generator).
        // Shown to admins, managers, course creators, and teachers. Each link
        // opens a course picker (select.php); the picker + target page enforce
        // the real per-course capability.
        // ==================================================================
        if ($loggedin && (is_siteadmin()
                || $this->user_has_any_role(['manager', 'coursecreator', 'editingteacher', 'teacher']))) {
            $authoring = [
                $this->make_item(
                    'scormbuilder',
                    get_string('navscormbuilder', 'theme_edzcorp'),
                    'fa-cube',
                    new \moodle_url('/local/edzrisebuilder/select.php')
                ),
                $this->make_item(
                    'coursegen',
                    get_string('navcoursegen', 'theme_edzcorp'),
                    'fa-wand-magic-sparkles',
                    new \moodle_url('/local/edzcoursegen/select.php')
                ),
            ];

            $groups[] = [
                'grouplabel' => get_string('navgroupauthoring', 'theme_edzcorp'),
                'items'      => $authoring,
            ];
        }

        // ==================================================================
        // GROUP 2 — Analytics
        // Only shown to authenticated non-admin users and teacher+ roles.
        // ==================================================================
        if ($loggedin) {
            $analytics = [];

            // Performance — local_trackmytime personal performance dashboard.
            $analytics[] = $this->make_item(
                'performance',
                get_string('navperformance', 'theme_edzcorp'),
                'fa-chart-simple',
                new \moodle_url('/local/trackmytime/pages/performance.php')
            );

            // Grades — students and teachers can see their own grade overview.
            if (!has_capability('moodle/site:config', $sysctx)) {
                $analytics[] = $this->make_item(
                    'grades',
                    get_string('navgrades', 'theme_edzcorp'),
                    'fa-star-half-stroke',
                    new \moodle_url('/grade/report/overview/index.php')
                );
            }

            // Achievements — local_edzperformance plugin (only when installed).
            if (\core_component::get_plugin_directory('local', 'edzperformance') !== null) {
                $analytics[] = $this->make_item(
                    'achievements',
                    get_string('navachievements', 'theme_edzcorp'),
                    'fa-trophy',
                    new \moodle_url('/local/edzperformance/index.php')
                );
            }

            // Report panel — local_reportpanel: self-service reports hub,
            // available to ALL logged-in users (replaces the old admin-only
            // Reports link). Only shown when the plugin is installed.
            if (\core_component::get_plugin_directory('local', 'reportpanel') !== null) {
                $analytics[] = $this->make_item(
                    'reports',
                    get_string('navreports', 'theme_edzcorp'),
                    'fa-chart-line',
                    new \moodle_url('/local/reportpanel/index.php')
                );
            }

            if (!empty($analytics)) {
                $groups[] = [
                    'grouplabel' => get_string('navgroupanalytics', 'theme_edzcorp'),
                    'items'      => $analytics,
                ];
            }
        }

        // ==================================================================
        // GROUP 3 — Events
        // Calendar, messages, and (if the plugin is installed) events.
        // ==================================================================
        if ($loggedin) {
            $events = [];

            $events[] = $this->make_item(
                'calendar',
                get_string('navcalendar', 'theme_edzcorp'),
                'fa-calendar-days',
                new \moodle_url('/calendar/view.php', ['view' => 'month'])
            );

            $events[] = $this->make_item(
                'messages',
                get_string('navmessages', 'theme_edzcorp'),
                'fa-comments',
                new \moodle_url('/message/index.php')
            );

            $groups[] = [
                'grouplabel' => get_string('navgroupevents', 'theme_edzcorp'),
                'items'      => $events,
            ];
        }

        // ==================================================================
        // GROUP 4 — Administration
        // Only for users with site configuration view capability.
        // ==================================================================
        // Shown to site admins, and to managers who can open the Admin control
        // panel (local_admincontrol). Links to the curated control panel when
        // that plugin is installed; otherwise falls back to Moodle's admin search.
        $canadmin = $loggedin && (is_siteadmin()
                || has_capability('moodle/site:configview', $sysctx)
                || has_capability('local/admincontrol:view', $sysctx));
        if ($canadmin) {
            $admin = [];

            $adminurl = (\core_component::get_plugin_directory('local', 'admincontrol') !== null)
                ? new \moodle_url('/local/admincontrol/index.php')
                : new \moodle_url('/admin/search.php');

            $admin[] = $this->make_item(
                'siteadmin',
                get_string('navsiteadmin', 'theme_edzcorp'),
                'fa-screwdriver-wrench',
                $adminurl
            );

            $groups[] = [
                'grouplabel' => get_string('navgroupadmin', 'theme_edzcorp'),
                'items'      => $admin,
            ];
        }

        return $groups;
    }

    /**
     * Creates a single nav item array.
     *
     * @param  string       $key       Unique key (used for CSS targeting).
     * @param  string       $label     Display label (always via get_string()).
     * @param  string       $icon      Font Awesome 6 class name (e.g. fa-house).
     * @param  \moodle_url  $url       Target URL.
     * @param  bool         $isactive  Whether this item is the current page.
     * @return array
     */
    protected function make_item(
        string $key,
        string $label,
        string $icon,
        \moodle_url $url,
        bool $isactive = false
    ): array {
        return [
            'key'      => $key,
            'label'    => $label,
            'icon'     => $icon,
            'url'      => $url,     // moodle_url; converted to string in items_for_template().
            'isactive' => $isactive,
        ];
    }

    /**
     * Marks the active item in each group based on the current page URL.
     * Comparison is prefix-based so sub-pages inherit the parent active state.
     *
     * @return void
     */
    protected function mark_active(): void {
        $currenturl  = $this->page->url->out(false);
        $currentpath = parse_url($currenturl, PHP_URL_PATH) ?? '/';
        parse_str(parse_url($currenturl, PHP_URL_QUERY) ?? '', $currentparams);

        // Flatten all items from all groups so we can find the best match.
        $flat = [];
        foreach ($this->groups as $gi => $group) {
            foreach ($group['items'] as $ii => $item) {
                $flat[] = ['gi' => $gi, 'ii' => $ii, 'url' => $item['url']];
            }
        }

        // Sort by specificity descending — longest path first; among equal
        // paths, the item with more query params wins (e.g. Webinars vs
        // Catalogues, which share /local/edzcatalogue/index.php).
        usort($flat, static function ($a, $b) {
            $pa = parse_url($a['url']->out(false), PHP_URL_PATH) ?? '/';
            $pb = parse_url($b['url']->out(false), PHP_URL_PATH) ?? '/';
            if (strlen($pa) !== strlen($pb)) {
                return strlen($pb) - strlen($pa);
            }
            parse_str(parse_url($a['url']->out(false), PHP_URL_QUERY) ?? '', $qa);
            parse_str(parse_url($b['url']->out(false), PHP_URL_QUERY) ?? '', $qb);
            return count($qb) - count($qa);
        });

        $matched = false;
        foreach ($flat as $ref) {
            if ($matched) {
                break;
            }

            $itemurl  = $ref['url']->out(false);
            $itempath = parse_url($itemurl, PHP_URL_PATH) ?? '/';
            parse_str(parse_url($itemurl, PHP_URL_QUERY) ?? '', $itemparams);

            // Path must match (prefix-based so sub-pages inherit active state).
            if ($itempath === '/') {
                $pathok = ($currentpath === '/');
            } else {
                $pathok = (strpos($currentpath, $itempath) === 0);
            }
            if (!$pathok) {
                continue;
            }

            // Every query param the item defines must be present in the
            // current URL (ignore extra current params like sesskey).
            $paramsok = true;
            foreach ($itemparams as $k => $v) {
                if ($k === 'redirect') {
                    continue; // Ignore the home redirect=0 helper param.
                }
                if (!isset($currentparams[$k]) || (string) $currentparams[$k] !== (string) $v) {
                    $paramsok = false;
                    break;
                }
            }
            if (!$paramsok) {
                continue;
            }

            $this->groups[$ref['gi']]['items'][$ref['ii']]['isactive'] = true;
            $matched = true;
        }
    }

    /**
     * Converts the internal groups structure to a template-friendly format
     * (moodle_url objects → plain strings, etc.).
     *
     * @return array
     */
    protected function groups_for_template(): array {
        $result = [];
        foreach ($this->groups as $group) {
            $items = array_map(static function (array $item): array {
                return [
                    'key'      => $item['key'],
                    'label'    => $item['label'],
                    'icon'     => $item['icon'],
                    'url'      => $item['url']->out(false),
                    'isactive' => $item['isactive'],
                ];
            }, $group['items']);

            $result[] = [
                'grouplabel' => $group['grouplabel'],
                'items'      => $items,
            ];
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Checks whether the current user holds any of the given archetype role names.
     *
     * @param  string[] $archetypes  Role archetypes to look for.
     * @return bool
     */
    protected function user_has_any_role(array $archetypes): bool {
        global $DB, $USER;

        if (!isloggedin() || isguestuser()) {
            return false;
        }

        $userroleids = $DB->get_fieldset_sql(
            'SELECT DISTINCT roleid FROM {role_assignments} WHERE userid = :uid',
            ['uid' => $USER->id]
        );

        if (empty($userroleids)) {
            return false;
        }

        foreach ($archetypes as $archetype) {
            $roleids = $DB->get_fieldset_select('role', 'id', 'archetype = :a', ['a' => $archetype]);
            if (!empty(array_intersect($userroleids, $roleids))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a human-readable primary role label for the sidebar footer.
     *
     * @return string
     */
    protected function get_primary_role_name(): string {
        global $DB, $USER;

        if (!isloggedin() || isguestuser()) {
            return '';
        }

        if (is_siteadmin()) {
            return get_string('administrator');
        }

        $priority = ['manager', 'coursecreator', 'editingteacher', 'teacher', 'student'];
        foreach ($priority as $archetype) {
            $role = $DB->get_record('role', ['archetype' => $archetype]);
            if (!$role) {
                continue;
            }
            if ($DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $role->id])) {
                return role_get_name($role);
            }
        }

        return '';
    }
}
