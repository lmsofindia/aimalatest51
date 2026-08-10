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
namespace local_cohortmanager\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_format_value;
use local_cohortmanager\external\members;
use local_cohortmanager\external\enrols;
use core_cohort_external;
use core_course_category;
use context_system;
use context;
use moodle_exception;
use editor_tiny\manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/cohort/externallib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/course/classes/category.php');

/**
 * External API for Cohort Manager
 *
 * @package    local_cohortmanager
 * @category   external
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class app extends external_api
{

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_all_strings_parameters()
    {
        return new external_function_parameters([]);
    }

    /**
     * Returns all strings for the local_cohortmanager component
     *
     * @return array
     */
    public static function get_all_strings()
    {
        global $CFG;

        $params = self::validate_parameters(self::get_all_strings_parameters(), []);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/cohort:manage', $context);

        $currentlang = current_language();

        $lang_strings = [];
        $langfile = $CFG->dirroot . '/local/cohortmanager/lang/' . $currentlang . '/local_cohortmanager.php';
        if (file_exists($langfile)) {
            $string = [];
            include($langfile);
            $lang_strings[$currentlang] = $string;
        }

        if ($currentlang !== 'en') {
            $enlangfile = $CFG->dirroot . '/local/cohortmanager/lang/en/local_cohortmanager.php';
            if (file_exists($enlangfile)) {
                $string = [];
                include($enlangfile);
                $lang_strings['en'] = $string;
            }
        }

        $result = [];
        foreach ($lang_strings as $lang => $stringlist) {
            $langstrings = [];
            foreach ($stringlist as $identifier => $value) {
                $langstrings[] = [
                    'identifier' => $identifier,
                    'value' => $value
                ];
            }
            $result[] = [
                'language' => $lang,
                'strings' => $langstrings
            ];
        }

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_multiple_structure
     */
    public static function get_all_strings_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'language' => new external_value(PARAM_LANG, 'Language code'),
                    'strings' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'identifier' => new external_value(PARAM_STRINGID, 'String identifier'),
                                'value' => new external_value(PARAM_RAW, 'String value'),
                            )
                        ),
                        'Strings for this language'
                    )
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function search_cohorts_with_total_parameters()
    {
        return new external_function_parameters(array(
            'query' => new external_value(PARAM_RAW, 'Query string'),
            'page' => new external_value(PARAM_INT, 'page we are fetching the records from', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Number of records to fetch', VALUE_DEFAULT, 25)
        ));
    }

    /**
     * Search cohorts with total count.
     *
     * @param string $query
     * @param array $context
     * @param string $includes
     * @param int $limitfrom
     * @param int $limitnum
     * @return array
     */
    public static function search_cohorts_with_total($query, $limitfrom = 0, $limitnum = 25)
    {
        global $CFG;

        $params = self::validate_parameters(self::search_cohorts_with_total_parameters(), array(
            'query' => $query,
            'page' => $limitfrom,
            'perpage' => $limitnum,
        ));

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/cohort:view', $context);

        $context = ["contextlevel" => 'system'];

        $return = core_cohort_external::search_cohorts($params['query'], $context, 'all', $params['page'], $params['perpage']);

        foreach($return['cohorts'] as $cohort) {
            $cohort->members = members::count_cohort_members_raw($cohort->id);
            $cohort->enrols = enrols::count_cohort_enrol_instances_raw($cohort->id);
        }

        $total_return = core_cohort_external::search_cohorts($params['query'], $context, 'all', 0, 0);
        $return['total'] = count($total_return['cohorts']);

        return $return;
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function search_cohorts_with_total_returns()
    {
        return new external_single_structure(array(
            'cohorts' => new external_multiple_structure(
                new external_single_structure(array(
                    'id' => new external_value(PARAM_INT, 'ID of the cohort'),
                    'name' => new external_value(PARAM_RAW, 'cohort name'),
                    'idnumber' => new external_value(PARAM_RAW, 'cohort idnumber'),
                    'description' => new external_value(PARAM_RAW, 'cohort description'),
                    'descriptionformat' => new external_format_value('description'),
                    'visible' => new external_value(PARAM_BOOL, 'cohort visible'),
                    'theme' => new external_value(PARAM_THEME, 'cohort theme', VALUE_OPTIONAL),
                    'customfields' => self::build_custom_fields_returns_structure(),
                    'members' => new external_value(PARAM_INT, 'cohort members counting'),
                    'enrols' => new external_value(PARAM_INT, 'cohort enrols counting'),
                ))
            ),
            'total' => new external_value(PARAM_INT, 'Total number of cohorts matching the query')
        ));
    }

    /**
     * Get custom fields data for cohorts
     *
     * @param array $cohortids
     * @return array
     */
    protected static function get_custom_fields_data($cohortids)
    {
        global $DB;

        $customfieldsdata = array();

        if (empty($cohortids)) {
            return $customfieldsdata;
        }

        [$instanceinsql, $instanceparams] = $DB->get_in_or_equal($cohortids, SQL_PARAMS_NAMED, 'coh');

        $sql = "SELECT cfi.id, cfi.shortname, cfv.value, cfv.instanceid
                  FROM {customfield_field} cfi
                  JOIN {customfield_category} cfc ON cfi.categoryid = cfc.id
                  JOIN {customfield_field} cf ON cf.id = cfi.id
                  JOIN {customfield_data} cfv ON cfv.fieldid = cfi.id
                 WHERE cfc.component = 'core_cohort'
                   AND cfv.instanceid {$instanceinsql}";

        $records = $DB->get_recordset_sql($sql, $instanceparams);

        foreach ($records as $record) {
            if (!isset($customfieldsdata[$record->instanceid])) {
                $customfieldsdata[$record->instanceid] = array();
            }
            $customfieldsdata[$record->instanceid][] = array(
                'id' => $record->id,
                'shortname' => $record->shortname,
                'value' => $record->value
            );
        }
        $records->close();

        return $customfieldsdata;
    }

    /**
     * Builds a structure for custom fields returns.
     *
     * @return \core_external\external_multiple_structure
     */
    protected static function build_custom_fields_returns_structure(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'name' => new external_value(PARAM_RAW, 'The name of the custom field'),
                    'shortname' => new external_value(PARAM_RAW,
                        'The shortname of the custom field - to be able to build the field class in the code'),
                    'type' => new external_value(PARAM_ALPHANUMEXT,
                        'The type of the custom field - text field, checkbox...'),
                    'valueraw' => new external_value(PARAM_RAW, 'The raw value of the custom field'),
                    'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                )
            ), 'Custom fields', VALUE_OPTIONAL
        );
    }

    /**
     * Returns description of method result value
     *
     * @return external_multiple_structure
     */
    public static function get_theme_list_returns()
    {
        return;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_app_config_parameters()
    {
        return new external_function_parameters([]);
    }

    /**
     * Returns the value of allowcohortthemes configuration setting
     *
     * @return array
     */
    public static function get_app_config()
    {
        global $PAGE;

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/cohort:manage', $context);
        $PAGE->set_context($context);
        $configs = get_config('local_cohortmanager');
        $configs->allowcohortthemes = get_config('core', 'allowcohortthemes');
        $configs->themelist = self::get_theme_list();
        $configs->contextlist = self::get_context_list($context);
        $configs->tinymceconfig = self::get_tinymce_config($context);

        return $configs;
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function get_app_config_returns()
    {
        return new external_single_structure(array(
            'allowcohortthemes' => new external_value(PARAM_BOOL, 'Whether cohort themes are allowed'),
            'themelist' => new external_multiple_structure(
                new external_single_structure(array(
                    'value' => new external_value(PARAM_ALPHANUMEXT, 'Theme value'),
                    'label' => new external_value(PARAM_RAW, 'Theme label'),
                ))
            ),
            'contextlist' => new external_multiple_structure(
                new external_single_structure(array(
                    'type' => new external_value(PARAM_ALPHANUMEXT, 'Context type'),
                    'value' => new external_value(PARAM_ALPHANUMEXT, 'Context value'),
                    'label' => new external_value(PARAM_RAW, 'Context label'),
                ))
            ),
            'tinymceconfig' => new external_value(PARAM_RAW, 'Json string with TinyMCE configs')
        ));
    }

    /**
     * Returns a list of valid themes using core get_list_of_themes method
     *
     * @return array
     */
    protected static function get_theme_list()
    {
        $themes = get_list_of_themes();

        $choices = array();
        $choices[] = array(
            'value' => '',
            'label' => get_string('default')
        );

        foreach ($themes as $key => $theme) {
            if (empty($theme->hidefromselector)) {
                $choices[] = array(
                    'value' => $key,
                    'label' => get_string('pluginname', 'theme_' . $theme->name)
                );
            }
        }

        return $choices;
    }

    protected static function get_context_list()
    {
        $displaylist = core_course_category::make_categories_list('moodle/cohort:manage');
        $options = array();
        $syscontext = context_system::instance();
        if (has_capability('moodle/cohort:manage', $syscontext)) {
            $options[] = [
                'type' => 'system',
                'value' => '',
                'label' => $syscontext->get_context_name()
            ];
        }
        foreach ($displaylist as $cid => $name) {
            $options[] = [
                'type' => 'id',
                'value' => $cid,
                'label' => $name
            ];
        }
        return $options;
    }

    protected static function get_tinymce_config(context $context)
    {
        global $PAGE;

        $editormanager = new manager;
        $siteconfig = get_config('editor_tiny');
        $config = (object) [

            'css' => $PAGE->theme->editor_css_url()->out(false),

            'context' => $context->id,

            'filepicker' => (object) [],

            'draftitemid' => 0,

            'currentLanguage' => current_language(),

            'branding' => property_exists($siteconfig, 'branding') ? !empty($siteconfig->branding) : true,
            'extended_valid_elements' => $siteconfig->extended_valid_elements ?? 'script[*],p[*],i[*]',

            'language' => [
                'currentlang' => current_language(),
                'installed' => get_string_manager()->get_list_of_translations(true),
                'available' => get_string_manager()->get_list_of_languages()
            ],

            'placeholderSelectors' => [],

            'plugins' => $editormanager->get_plugin_configuration($context),
        ];

        if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
            // Add sample selectors for Behat test.
            $config->placeholderSelectors = ['.behat-tinymce-placeholder'];
        }

        return json_encode(convert_to_array($config));
    }

    protected static function get_customfield_list_context()
    {
        $management = new \core_customfield\output\management;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_cohort_context_info_parameters()
    {
        return new external_function_parameters(array(
            'cohortid' => new external_value(PARAM_INT, 'ID of the cohort')
        ));
    }

    /**
     * Get cohort context information based on contextid
     *
     * @param int $cohortid ID of the cohort
     * @return array Object with type and value keys
     */
    public static function get_cohort_context_info($cohortid)
    {
        global $DB;

        $params = self::validate_parameters(self::get_cohort_context_info_parameters(), array(
            'cohortid' => $cohortid
        ));

        $cohort_contextid = $DB->get_field(
            'cohort',
            'contextid',
            array('id' => $params['cohortid']),
        );

        if (!$cohort_contextid) {
            throw new moodle_exception('bulknocohort', 'cohort');
        }

        $context = context::instance_by_id($cohort_contextid);

        self::validate_context($context);
        require_capability('moodle/cohort:manage', $context);

        if ($context->contextid == 1) {
            $result = array(
                'type' => 'system',
                'value' => ''
            );
        } else {
            $result = array(
                'type' => 'id',
                'value' => $context->instanceid
            );
        }

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function get_cohort_context_info_returns()
    {
        return new external_single_structure(array(
            'type' => new external_value(PARAM_ALPHANUMEXT, 'Type of context (system or id)'),
            'value' => new external_value(PARAM_RAW, 'Value based on context type')
        ));
    }
}
