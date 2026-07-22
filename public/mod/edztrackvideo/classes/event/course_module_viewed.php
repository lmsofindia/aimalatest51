<?php
/**
 * The mod_edztrackvideo course module viewed event.
 *
 * @package   mod_edztrackvideo
 */

namespace mod_edztrackvideo\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Fired when a learner views the activity.
 */
class course_module_viewed extends \core\event\course_module_viewed {

    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'edztrackvideo';
    }

    public static function get_objectid_mapping() {
        return ['db' => 'edztrackvideo', 'restore' => 'edztrackvideo'];
    }
}
