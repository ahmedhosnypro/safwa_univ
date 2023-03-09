<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     block_edutechpreferences
 * @copyright   2022 EduTech
 * @author      2022 Ricardo Emmanuel Reyes Acosta<ricardo.ra@aguascalientes.tecnm.mx>
 * @author      2022 Ricardo Mendoza Gonzalez<mendozagric@aguascalientes.tecnm.mx>
 * @author      2022 Mario Alberto Rodriguez Diaz<mario.rd@aguascalientes.tecnm.mx>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/edutechpreferences/classes/form/edit.php');
defined('MOODLE_INTERNAL') || die();
$PAGE->set_url(new moodle_url('/blocks/edutechpreferences/preferences.php'));
require_login();
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string("preferencesreport", "block_edutechpreferences"));
$PAGE->set_heading(get_string("preferencesreport", "block_edutechpreferences"));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('editpreferences', "block_edutechpreferences"));
global $DB;

$mform = new edit();
// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . "/", get_string("databasediscarted", "block_edutechpreferences"));
} else if ($fromform = $mform->get_data()) {
    // In this case you process validated data. $mform->get_data() returns data posted in form.
    $recordtoinsert = new stdClass();
    $recordtoinsert->userid = $USER->id;
    $answers = $fromform;
    unset($answers->submitbutton);
    $recordtoinsert->preferences = json_encode($answers);
    $query = $DB->get_record_sql('SELECT id AS id FROM {block_edutechpreferences} WHERE userid = ? LIMIT 1', [$USER->id]);
    $rowid = 0;
    if (isset($query->id)) {
        $rowid = $query->id;
    }
    if ($rowid == 0) {
        try {
            $DB->insert_record('block_edutechpreferences', $recordtoinsert);
            \core\notification::success(get_string("databasesaved", "block_edutechpreferences"));
        } catch (\Exception $e) {
            \core\notification::error(get_string("databaseerror", "block_edutechpreferences"));
        }
    } else if ($rowid >= 1) {
        $dataobject = array(
            'id'            => $rowid,
            'userid'        => $USER->id,
            'preferences'   => json_encode($answers)
        );
        try {
            $DB->update_record('block_edutechpreferences', $dataobject, $bulk = false);
            \core\notification::success(get_string("databasesaved", "block_edutechpreferences"));
        } catch (\Exception $e) {
            \core\notification::error(get_string("databaseerror", "block_edutechpreferences"));
        }
    } else {
        \core\notification::error(get_string("databaseerror", "block_edutechpreferences"));
    }
    // Display form with the information previously given and stored in the database.
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
} else {
      // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed.
      // Or on the first display of the form.
      echo $OUTPUT->header();
      $mform->display();
      echo $OUTPUT->footer();
}
