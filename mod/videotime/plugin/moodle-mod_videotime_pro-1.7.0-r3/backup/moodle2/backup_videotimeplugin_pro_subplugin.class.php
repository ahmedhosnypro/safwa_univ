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
 * Defines backup_videotimeplugin_pro_subplugin class
 *
 * @package     videotimeplugin_pro
 * @copyright   2021 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines backup_videotimeplugin_pro_subplugin class
 *
 * Provides the step to perform back up of sublugin data
 */
class backup_videotimeplugin_pro_subplugin extends backup_subplugin {

    /**
     * Defined suplugin structure step
     */
    protected function define_videotime_subplugin_structure() {

        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $sessions = new backup_nested_element('sessions');

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        $subplugintablesettings = new backup_nested_element('pro_settings', null, [
            'autopause',
            'background',
            'dnt',
            'saveinterval',
            'label_mode',
            'next_activity_button',
            'next_activity_auto',
            'next_activity_id',
            'pip',
            'preventfastforwarding',
            'resume_playback',
        ]);

        if ($userinfo) {
            $session = new backup_nested_element('session', null, [
                'module_id',
                'user_id',
                'time',
                'timestarted',
                'state',
                'percent_watch',
                'current_watch_time',
            ]);
            $sessions->add_child($session);
            $subplugin->add_child($sessions);
            $session->annotate_ids('user', 'user_id');
            $session->set_source_table('videotimeplugin_pro_session',
                    array('module_id' => backup::VAR_MODID));
        }

        // Connect XML elements into the tree.
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subplugintablesettings);

        // Set source to populate the data.
        $subplugintablesettings->set_source_table('videotimeplugin_pro',
                array('videotime' => backup::VAR_ACTIVITYID));

        $subplugintablesettings->annotate_ids('course_module', 'next_activity_id');

        return $subplugin;
    }
}
