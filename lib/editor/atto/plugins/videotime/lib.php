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
 * Atto text editor integration version file.
 *
 * @package   atto_videotime
 * @copyright 2020 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Set params for this button.
 *
 * @param string $elementid
 * @param stdClass $options - the options for the editor, including the context.
 * @param stdClass $fpoptions - unused.
 */
function atto_videotime_params_for_js($elementid, $options, $fpoptions) {
    $context = $options['context'];
    if (!$context) {
        $context = context_system::instance();
    }

    $params = [
        'instances' => []
    ];

    if ($coursecontext = $context->get_course_context(false)) {
        foreach (get_all_instances_in_course('videotime', get_course($coursecontext->instanceid)) as $instance) {
            $instance = \mod_videotime\videotime_instance::instance_by_id($instance->id);
            $params['instances'][] = [
                'cmid' => $instance->get_cm()->id,
                'name' => $instance->name
            ];
        }
    }

    return $params;
}

/**
 * Initialise the strings required for js
 */
function atto_videotime_strings_for_js() {
    global $PAGE;

    $PAGE->requires->strings_for_js(['pluginname', 'embed'], 'atto_videotime');
}

