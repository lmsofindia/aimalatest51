<?php
/**
 * Backup steps for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

defined('MOODLE_INTERNAL') || die();

class backup_edztrackvideo_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        $edztrackvideo = new backup_nested_element('edztrackvideo', ['id'], [
            'name', 'intro', 'introformat', 'sourcetype', 'externalurl', 'videoid',
            'videohash', 'serverpath', 'enable_completion_percent', 'completion_threshold',
            'no_forward_seek_first_view', 'allow_speed', 'max_speed',
            'timecreated', 'timemodified',
        ]);

        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'maxwatched', 'last_position', 'duration', 'first_view_done',
            'completed', 'dismissed', 'lastupdate', 'last_viewed_on',
        ]);

        $edztrackvideo->add_child($progresses);
        $progresses->add_child($progress);

        $edztrackvideo->set_source_table('edztrackvideo', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $progress->set_source_table('edztrackvideo_progress', ['edztrackvideoid' => backup::VAR_PARENTID]);
        }

        $progress->annotate_ids('user', 'userid');

        // Uploaded video files travel with the backup.
        $edztrackvideo->annotate_files('mod_edztrackvideo', 'videofile', null);

        return $this->prepare_activity_structure($edztrackvideo);
    }
}
