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
 * Completion Levels external services implementation.
 *
 * @package    block_completion_levels
 * @copyright  2022 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * Completion Levels external services implementation class.
 *
 * @package    block_completion_levels
 * @copyright  2022 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_completion_levels_external extends external_api {

    /**
     * Parameters definition for delete_custom_pix.
     */
    public static function delete_custom_pix_parameters() {
        return new external_function_parameters(
                array(
                        'contextid' => new external_value(PARAM_INT, 'Block context ID', VALUE_REQUIRED),
                        'draftitemid' => new external_value(PARAM_INT, 'Draft file area ID', VALUE_REQUIRED)
                )
        );
    }

    /**
     * Delete custom badges for a block instance.
     *
     * @param int $contextid Block context ID, in which files are stored.
     * @param int $draftitemid Draft area ID.
     * @return boolean true on success
     */
    public static function delete_custom_pix($contextid, $draftitemid) {
        global $USER;
        require_capability('moodle/block:edit', context::instance_by_id($contextid));
        $fs = get_file_storage();
        $success = $fs->delete_area_files($contextid, 'block_completion_levels', 'levels_pix');
        $success = $success && $fs->delete_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $draftitemid);
        return $success;
    }

    /**
     * Return true on success.
     */
    public static function delete_custom_pix_returns() {
        return new external_value(PARAM_BOOL, 'Whether operation was successful', VALUE_REQUIRED);
    }
}
