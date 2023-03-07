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

/**
 * Library of interface functions and constants.
 *
 * @package     videotimeplugin_pro
 * @copyright   2022 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videotime\output\next_activity_button;
use mod_videotime\videotime_instance;

/**
 * Updates an instance of the mod_videotime in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_videotime_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 * @throws \dml_exception
 */
function videotimeplugin_pro_update_instance($moduleinstance, $mform = null) {
    global $DB;

    if ($record = $DB->get_record('videotimeplugin_pro', ['videotime' => $moduleinstance->id])) {
        $record = ['id' => $record->id, 'videotime' => $moduleinstance->id] + (array) $moduleinstance + (array) $record;
        $DB->update_record('videotimeplugin_pro', $record);
    } else {
        $record = ['id' => null, 'videotime' => $moduleinstance->id]
            + (array) $moduleinstance + (array) get_config('videotimeplugin_pro');
        $DB->insert_record('videotimeplugin_pro', $record);
    }
}

/**
 * Removes an instance of the mod_videotime from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function videotimeplugin_pro_delete_instance($id) {
    global $DB;

    $DB->delete_records('videotimeplugin_pro', array('videotime' => $id));

    return true;
}

/**
 * Loads plugin settings into module record
 *
 * @param object $instance the module record.
 * @return object
 */
function videotimeplugin_pro_load_settings($instance) {
    global $COURSE, $DB, $USER;

    if (
        videotime_has_pro()
        && $record = $DB->get_record('videotimeplugin_pro', array('videotime' => $instance['id']))
    ) {
        if ($record->resume_playback &&
            $cm = get_coursemodule_from_instance('videotime', $record->videotime)
        ) {
            if (!empty($instance['token']) && $user = $DB->get_record_sql("
                SELECT u.*
                  FROM {user} u
                  JOIN {external_tokens} t ON t.userid = u.id
                  JOIN {external_services} s ON s.id = t.externalserviceid
                 WHERE t.token = :token
                      AND s.shortname = :shortname", [
                'contextid' => context_module::instance($cm->id)->id,
                'shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE,
                'token' => $instance['token'],
            ])) {
                $sessions = \videotimeplugin_pro\module_sessions::get($cm->id, $user->id);
                $record->resume_time = (int)$sessions->get_current_watch_time();
            } else if (!empty($USER->id)) {
                $sessions = \videotimeplugin_pro\module_sessions::get($cm->id, $USER->id);
                $record->resume_time = (int)$sessions->get_current_watch_time();
            }
        }
        unset($record->id);
        unset($record->videotime);
        return ((array) $record) + ((array) $instance);
    }

    return $instance;
}

/**
 * Add additional fields to form
 *
 * @param moodleform $mform Setting form to modify
 * @param string $formclass Class nam of the form
 */
function videotimeplugin_pro_add_form_fields($mform, $formclass) {
    global $COURSE, $OUTPUT, $PAGE;

    if (!videotime_has_pro()) {
        return;
    }

    if ($formclass === 'mod_videotime_mod_form') {
        $group = [];
        $group[] = $mform->createElement('radio', 'label_mode', '', get_string('normal_mode', 'videotime'),
            videotime_instance::NORMAL_MODE);
        $group[] = $mform->createElement('radio', 'label_mode', '', get_string('label_mode', 'videotime'),
            videotime_instance::LABEL_MODE);
        if (videotime_has_repository()) {
            $group[] = $mform->createElement('radio', 'label_mode', '', get_string('preview_mode', 'videotime'),
                videotime_instance::PREVIEW_MODE);
        }

        $mform->insertElementBefore(
            $mform->createElement('group', 'modegroup', get_string('mode', 'videotime'), $group, array('<br>'), false),
            'introeditor'
        );
        $mform->addHelpButton('modegroup', 'mode', 'videotime');
        $mform->setDefault('label_mode', get_config('videotimeplugin_pro', 'label_mode'));
        if (in_array('label_mode', (array) explode(',', get_config('videotimeplugin_pro', 'forced')))) {
            $mform->insertElementBefore(
                $mform->createElement('static', 'label_mode_forced', '', get_string('option_forced', 'videotime', [
                    'option' => get_string('label_mode', 'videotime'),
                    'value' => videotime_instance::get_mode_options()[get_config('videotimeplugin_pro', 'label_mode')]
                ])),
                'introeditor'
            );
            $mform->removeElement('modegroup');
        }

        $mform->insertElementBefore(
            $mform->createElement('advcheckbox', 'resume_playback', get_string('resume_playback', 'videotime')),
            'tabs'
        );
        $mform->addHelpButton('resume_playback', 'resume_playback', 'videotime');
        $mform->setType('resume_playback', PARAM_BOOL);
        $mform->setDefault('resume_playback', get_config('videotimeplugin_pro', 'resume_playback'));
        videotime_instance::create_additional_field_form_elements('resume_playback', $mform);

        $mform->insertElementBefore(
            $mform->createElement('advcheckbox', 'next_activity_button', get_string('next_activity_button', 'videotime')),
            'tabs'
        );
        $mform->addHelpButton('next_activity_button', 'next_activity_button', 'videotime');
        $mform->setType('next_activity_button', PARAM_BOOL);
        $mform->setDefault('next_activity_button', get_config('videotimeplugin_pro', 'next_activity_button'));
        videotime_instance::create_additional_field_form_elements('next_activity_button', $mform);

        $modoptions = [-1 => get_string('next_activity_in_course', 'videotime')];
        foreach (next_activity_button::get_available_cms($COURSE->id) as $cm) {
            // Do not include current module in select list.
            if (empty($PAGE->cm) || $PAGE->cm->id != $cm->id) {
                $modoptions[$cm->id] = $cm->name;
            }
        }

        $mform->insertElementBefore(
            $mform->createElement('select', 'next_activity_id', get_string('next_activity', 'videotime'), $modoptions),
            'tabs'
        );
        $mform->setType('next_activity_id', PARAM_INT);
        if (method_exists($mform, 'hideIf')) {
            $mform->hideIf('next_activity_id', 'next_activity_button');
        } else {
            $mform->disabledIf('next_activity_id', 'next_activity_button');
        }

        $mform->insertElementBefore(
             $mform->createElement('advcheckbox', 'next_activity_auto', get_string('next_activity_auto', 'videotime')),
            'tabs'
        );
        $mform->addHelpButton('next_activity_auto', 'next_activity_auto', 'videotime');
        $mform->setType('next_activity_auto', PARAM_BOOL);
        if (method_exists($mform, 'hideIf')) {
            $mform->hideIf('next_activity_auto', 'next_activity_button');
        } else {
            $mform->disabledIf('next_activity_auto', 'next_activity_button');
        }
        $mform->setDefault('next_activity_auto', get_config('videotimeplugin_pro', 'next_activity_auto'));
        videotime_instance::create_additional_field_form_elements('next_activity_auto', $mform);

        $mform->insertElementBefore(
            $mform->createElement(
                'select',
                'saveinterval',
                get_string('saveinterval', 'videotime'),
                mod_videotime_pro_get_interval_options()
            ),
            'resume_playback'
        );
        $mform->addHelpButton('saveinterval', 'saveinterval', 'videotime');
        $mform->setType('saveinterval', PARAM_INT);
        $mform->setDefault('saveinterval', get_config('videotimeplugin_pro', 'saveinterval'));
        videotime_instance::create_additional_field_form_elements('saveinterval', $mform);

        $mform->insertElementBefore(
            $mform->createElement('advcheckbox', 'preventfastforwarding', get_string('preventfastforwarding', 'videotime')),
            'resume_playback'
        );
        $mform->addHelpButton('preventfastforwarding', 'preventfastforwarding', 'videotime');
        $mform->setType('preventfastforwarding', PARAM_BOOL);
        $mform->setDefault('responsive', get_config('videotimeplugin_pro', 'preventfastforwarding'));
        videotime_instance::create_additional_field_form_elements('preventfastforwarding', $mform);
        $mform->disabledIf('preventfastforwarding', 'saveinterval', 'eq', 0);
        $mform->disabledIf('resume_playback', 'saveinterval', 'eq', 0);
        $mform->disabledIf('completion_on_view', 'saveinterval', 'eq', 0);
        $mform->disabledIf('completion_on_percent', 'saveinterval', 'eq', 0);

        // -------------------------------------------------------------------------------
        // Grade settings.
        $mform->insertElementBefore(
            $mform->createElement('header', 'modstandardgrade', get_string('modgrade', 'grades')),
            'modstandardelshdr'
        );

        $mform->insertElementBefore(
            $mform->createElement('checkbox', 'viewpercentgrade', get_string('viewpercentgrade', 'videotime')),
            'modstandardelshdr'
        );
        $mform->setType('viewpercentgrade', PARAM_BOOL);
        $mform->addHelpButton('viewpercentgrade', 'viewpercentgrade', 'videotime');

        $mform->insertElementBefore(
            $mform->createElement(
                'select',
                'gradecat',
                get_string('gradecategoryonmodform', 'grades'),
                grade_get_categories_menu($COURSE->id, false)
            ),
            'modstandardelshdr'
        );
        $mform->addHelpButton('gradecat', 'gradecategoryonmodform', 'grades');
        $mform->disabledIf('gradecat', 'viewpercentgrade');

        // Grade to pass.
        $mform->insertElementBefore(
            $mform->createElement('text', 'gradepass', get_string('gradepass', 'grades')),
            'modstandardelshdr'
        );
        $mform->addHelpButton('gradepass', 'gradepass', 'grades');
        $mform->setDefault('gradepass', '');
        $mform->setType('gradepass', PARAM_RAW);
        $mform->disabledIf('gradepass', 'viewpercentgrade');

        if ($PAGE->cm) {
            if (!grade_item::fetch(array('itemtype' => 'mod',
                'itemmodule' => $PAGE->cm->modname,
                'iteminstance' => $PAGE->cm->instance,
                'itemnumber' => 0,
                'courseid' => $COURSE->id))) {

                $mform->insertElementBefore(
                    $mform->createElement('static', 'gradewarning', '', $OUTPUT->notification(
                        get_string('gradeitemnotcreatedyet', 'videotime'), 'warning'
                    ), null, ['id' => 'id_gradewarning']),
                    'modstandardelshdr'
                );
                if (method_exists($mform, 'hideIf')) {
                    $mform->hideIf('gradewarning', 'viewpercentgrade', 'checked');
                } else {
                    $mform->disabledIf('gradewarning', 'viewpercentgrade', 'checked');
                }
            }
        }
    } else if ($formclass === 'videotimeplugin_vimeo\\form\\options') {
        $mform->addElement('advcheckbox', 'dnt', get_string('option_dnt', 'videotime'));
        $mform->setType('dnt', PARAM_BOOL);
        $mform->addHelpButton('dnt', 'option_dnt', 'videotime');
        $mform->setDefault('dnt', get_config('videotimeplugin_pro', 'dnt'));
        videotime_instance::create_additional_field_form_elements('dnt', $mform);

        $mform->addElement('advcheckbox', 'autopause', get_string('option_autopause', 'videotime'));
        $mform->setType('autopause', PARAM_BOOL);
        $mform->addHelpButton('autopause', 'option_autopause', 'videotime');
        $mform->setDefault('autopause', get_config('videotimeplugin_pro', 'autopause'));
        videotime_instance::create_additional_field_form_elements('autopause', $mform);

        $mform->addElement('advcheckbox', 'background', get_string('option_background', 'videotime'));
        $mform->setType('background', PARAM_BOOL);
        $mform->addHelpButton('background', 'option_background', 'videotime');
        $mform->setDefault('background', get_config('videotimeplugin_pro', 'background'));
        videotime_instance::create_additional_field_form_elements('background', $mform);

        $mform->addElement('advcheckbox', 'pip', get_string('option_pip', 'videotime'));
        $mform->setType('pip', PARAM_BOOL);
        $mform->addHelpButton('pip', 'option_pip', 'videotime');
        $mform->setDefault('pip', get_config('videotimeplugin_pro', 'pip'));
        videotime_instance::create_additional_field_form_elements('pip', $mform);
    }
}

/**
 * Prepares the form before data are set
 *
 * @param  array $defaultvalues
 * @param  int $instance
 */
function videotimeplugin_pro_data_preprocessing(array &$defaultvalues, int $instance) {
    global $DB;

    if (empty($instance)) {
        $settings = (array) get_config('videotimeplugin_pro');
    } else {
        $settings = (array) $DB->get_record('videotimeplugin_pro', array('videotime' => $instance));
        unset($settings['id']);
        unset($settings['videotime']);
    }

    foreach ($settings as $key => $value) {
        $defaultvalues[$key] = $value;
    }
}

/**
 * Loads plugin settings into module record
 *
 * @param object $instance the module record.
 * @param array $forcedsettings current forced settings array
 * @return array
 */
function videotimeplugin_pro_forced_settings($instance, $forcedsettings) {
    global $DB;

    if (empty(get_config('videotimeplugin_pro', 'enabled')) || !get_config('videotimeplugin_pro', 'forced')) {
        return $forcedsettings;
    }

    $instance = (array) $instance;
    if (
        mod_videotime_get_vimeo_id_from_link($instance['vimeo_url'])
    ) {
        return array_fill_keys(explode(',', get_config('videotimeplugin_pro', 'forced')), true) + (array) $forcedsettings;
    }
    return array_fill_keys(
        array_intersect(explode(',', get_config('videotimeplugin_pro', 'forced')), [
            'saveinterval',
            'label_mode',
            'next_activity_button',
            'next_activity_auto',
            'preventfastforwarding',
            'resume_playback',
        ]),
        true
    ) + (array) $forcedsettings;
}

/**
 * Get options to offer for interval to save position of video
 *
 * @return array Options
 */
function mod_videotime_pro_get_interval_options() {
    return [
        5 => get_string('normal'),
        60 => get_string('long', 'videotime'),
        300 => get_string('verylong', 'videotime'),
        0 => get_string('disable'),
    ];
}
