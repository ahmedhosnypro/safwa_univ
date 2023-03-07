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
 * Upgrade script for the Video Time Pro.
 *
 * @package     videotimeplugin_pro
 * @copyright   2018 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videotime\plugin_manager;

/**
 * Upgrade script for the Video Time Pro.
 *
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_videotimeplugin_pro_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018080204) {

        // Define table videotime_session to be created.
        $table = new xmldb_table('videotime_session');

        // Adding fields to table videotime_session.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('module_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table videotime_session.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table videotime_session.
        $table->add_index('module_user', XMLDB_INDEX_NOTUNIQUE, array('module_id', 'user_id'));

        // Conditionally launch create table for videotime_session.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2018080204, 'videotimeplugin', 'pro');
    }

    if ($oldversion < 2018080205) {

        // Define field state to be added to videotime_session.
        $table = new xmldb_table('videotime_session');
        $field = new xmldb_field('state', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'timestarted');

        // Conditionally launch add field state.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2018080205, 'videotimeplugin', 'pro');
    }

    if ($oldversion < 2018080209) {

        // Define field percent to be added to videotime_session.
        $table = new xmldb_table('videotime_session');
        $field = new xmldb_field('percent', XMLDB_TYPE_NUMBER, '5, 3', null, null, null, null, 'state');

        // Conditionally launch add field percent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2018080209, 'videotimeplugin', 'pro');
    }

    if ($oldversion < 2019081903) {

        // Define field current_time to be added to videotime_session.
        $table = new xmldb_table('videotime_session');
        $field = new xmldb_field('current_watch_time', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'percent');

        // Conditionally launch add field current_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2019081903, 'videotimeplugin', 'pro');
    }

    if ($oldversion < 2020071200) {

        // Rename field percent on table videotime_session to percent_watch.
        $table = new xmldb_table('videotime_session');
        $field = new xmldb_field('percent', XMLDB_TYPE_NUMBER, '5, 3', null, null, null, null, 'state');

        // Launch rename field percent_watch.
        $dbman->rename_field($table, $field, 'percent_watch');

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2020071200, 'videotimeplugin', 'pro');
    }

    if ($oldversion < 2022042801) {
        $options = [
            'autopause',
            'background',
            'dnt',
            'saveinterval',
            'label_mode',
            'next_activity_button',
            'next_activity_auto',
            'pip',
            'preventfastforwarding',
            'resume_playback',
        ];

        $forced = [];
        $config = (array) get_config('videotime');

        foreach ($options as $option) {
            if (key_exists($option, $config)) {
                set_config($option, $config[$option], 'videotimeplugin_pro');
                set_config($option, null, 'videotime');
                if (!empty($config[$option . '_force'])) {
                    $forced[] = $option;
                }
                set_config($option . '_force', null, 'videotime');
            }
        }
        set_config('forced', implode(',', $forced), 'videotimeplugin_pro');

        // Define table videotimeplugin_pro to be created.
        $table = new xmldb_table('videotimeplugin_pro');

        // Adding fields to table videotimeplugin_pro.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('videotime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('label_mode', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
        $table->add_field('resume_playback', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('next_activity_button', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('next_activity_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('next_activity_auto', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('preventfastforwarding', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('saveinterval', XMLDB_TYPE_INTEGER, '10', null, null, null, '5');
        $table->add_field('dnt', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('autopause', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('background', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('pip', XMLDB_TYPE_INTEGER, '1', null, null, null, null);

        // Adding keys to table videotimeplugin_pro.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('videotime', XMLDB_KEY_FOREIGN_UNIQUE, ['videotime'], 'videotime', ['id']);

        // Conditionally launch create table for videotimeplugin_pro.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);

            // Copy pro settings to new table.
            $rs = $DB->get_recordset_select('videotime', []);
            foreach ($rs as $record) {
                $record->videotime = $record->id;
                unset($record->id);
                $DB->insert_record('videotimeplugin_pro', $record);
            }
            $rs->close();
        }

        // Remove fields from main table.
        // Define field label_mode to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('label_mode');

        // Conditionally launch drop field resume_playback.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field resume_playback to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('resume_playback');

        // Conditionally launch drop field resume_playback.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field next_activity_button to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('next_activity_button');

        // Conditionally launch drop field next_activity_button.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field next_activity_id to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('next_activity_id');

        // Conditionally launch drop field next_activity_id.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field next_activity_auto to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('next_activity_auto');

        // Conditionally launch drop field next_activity_auto.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field preventfastforwarding to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('preventfastforwarding');

        // Conditionally launch drop field preventfastforwarding.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field saveinterval to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('saveinterval');

        // Conditionally launch drop field saveinterval.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field dnt to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('dnt');

        // Conditionally launch drop field dnt.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field autopause to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('autopause');

        // Conditionally launch drop field autopause.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field background to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('background');

        // Conditionally launch drop field background.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field pip to be dropped from videotime.
        $table = new xmldb_table('videotime');
        $field = new xmldb_field('pip');

        // Conditionally launch drop field pip.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define table videotime_session to be renamed to videotimeplugin_pro_session.
        $table = new xmldb_table('videotime_session');

        // Launch rename table for videotime_session.
        $dbman->rename_table($table, 'videotimeplugin_pro_session');

        $manager = new plugin_manager('videotimeplugin');
        $manager->show_plugin('pro');

        // Pro savepoint reached.
        upgrade_plugin_savepoint(true, 2022042801, 'videotimeplugin', 'pro');
    }

    return true;
}
