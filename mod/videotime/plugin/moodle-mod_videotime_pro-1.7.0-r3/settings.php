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
 * Plugin administration pages are defined here.
 *
 * @package     videotimeplugin_pro
 * @category    admin
 * @copyright   2022 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videotime\videotime_instance;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/videotime/lib.php');
require_once($CFG->dirroot.'/mod/videotime/plugin/pro/lib.php');

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('defaultsettings', new lang_string('default', 'videotime') . ' ' .
        new lang_string('settings'), ''));

    $settings->add(new admin_setting_configcheckbox('videotimeplugin_pro/preventfastforwarding',
        new lang_string('preventfastforwarding', 'videotime'), new lang_string('preventfastforwarding_help', 'videotime'), 0));

    $settings->add(new admin_setting_configcheckbox('videotimeplugin_pro/resume_playback',
        new lang_string('resume_playback', 'videotime'),
        new lang_string('resume_playback_help', 'videotime'), 1));

    $settings->add(new admin_setting_configcheckbox('videotimeplugin_pro/next_activity_button',
        new lang_string('next_activity_button', 'videotime'),
        new lang_string('next_activity_button_help', 'videotime'), 0));

    $settings->add(new admin_setting_configcheckbox('videotimeplugin_pro/next_activity_auto',
        new lang_string('next_activity_auto', 'videotime'),
        new lang_string('next_activity_auto_help', 'videotime'), 0));

    $settings->add(new admin_setting_configselect('videotimeplugin_pro/label_mode', new lang_string('mode', 'videotime'),
        new lang_string('mode_help', 'videotime'), videotime_instance::NORMAL_MODE, videotime_instance::get_mode_options()));

    $settings->add(new admin_setting_configselect('videotimeplugin_pro/saveinterval', new lang_string('saveinterval', 'videotime'),
        new lang_string('saveinterval_help', 'videotime'), 5, mod_videotime_pro_get_interval_options()));

    $settings->add(new admin_setting_heading('vimeodefaultsettings', new lang_string('vimeodefaultsettings', 'videotime'), ''));

    $settings->add(new admin_setting_configcheckbox('videotimeplugin_pro/dnt', new lang_string('option_dnt', 'videotime'),
        new lang_string('option_dnt_help', 'videotime'), '0'));

    $settings->add(new admin_setting_configcheckbox('videotimeplugin_pro/autopause',
        new lang_string('option_autopause', 'videotime'),
        new lang_string('option_autopause_help', 'videotime'), '1'));

    $settings->add(new admin_setting_configcheckbox('videotimeplugin_pro/background',
        new lang_string('option_background', 'videotime'),
        new lang_string('option_background_help', 'videotime'), '0'));

    $settings->add(new admin_setting_configcheckbox('videotimeplugin_pro/pip', new lang_string('option_pip', 'videotime'),
        new lang_string('option_pip_help', 'videotime'), '1'));

    $settings->add(new admin_setting_heading('forcedhdr', new lang_string('forcedsettings', 'videotime'), ''));

    $options = [
        'autopause' => new lang_string('autopause', 'videotime'),
        'background' => new lang_string('background', 'videotime'),
        'dnt' => new lang_string('dnt', 'videotime'),
        'saveinterval' => new lang_string('saveinterval', 'videotime'),
        'label_mode' => new lang_string('label_mode', 'videotime'),
        'next_activity_button' => new lang_string('next_activity_button', 'videotime'),
        'next_activity_auto' => new lang_string('next_activity_auto', 'videotime'),
        'pip' => new lang_string('pip', 'videotime'),
        'preventfastforwarding' => new lang_string('preventfastforwarding', 'videotime'),
        'resume_playback' => new lang_string('resume_playback', 'videotime'),
    ];

    $settings->add(new admin_setting_configmultiselect(
        'videotimeplugin_pro/forced',
        new lang_string('forcedsettings', 'videotime'),
        new lang_string('forcedsettings_help', 'videotime'),
        [ ],
        $options
    ));

    $settings->add(new admin_setting_heading('advancedhdr', new lang_string('advancedsettings', 'videotime'), ''));

    $settings->add(new admin_setting_configmultiselect(
        'videotimeplugin_pro/advanced',
        new lang_string('advancedsettings', 'videotime'),
        new lang_string('advancedsettings_help', 'videotime'),
        [
            'autopause',
            'background',
            'dnt',
            'pip',
            'saveinterval',
        ],
        $options
    ));
}
