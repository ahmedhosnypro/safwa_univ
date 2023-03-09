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
defined('MOODLE_INTERNAL') || die();
// Moodleform is defined in formslib.php.
require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/blocks/edutechpreferences/classes/api/api.php');
require_once($CFG->dirroot . '/blocks/edutechpreferences/classes/translate/translate.php');
use block_edutechpreferences\api\api;
use block_edutechpreferences\translate\translate;
class edit extends moodleform {
    /**
     * Generates the preferences form with the data obtained of the API/block_edutechpreferences_get_api()
     * if the form was previously filled by the current student, it will be prefilled with the stored data
     * in case of failure prints a notification with the error
     * in case of success, the form will be shown.
     * @return void
     */
    public function definition() {
        global $USER;
        $apis = new api();
        $translate = new translate();
        $mform = $this->_form; // Don't forget the underscore!
        $apiresponse = $apis->block_edutechpreferences_get_list();
        if ($apiresponse != '0') {
            $decapiresponse = json_decode($apiresponse);
            foreach ($decapiresponse as $key) {
                $preferencesarea = $translate->block_edutechpreferences_translator($key->preferences_are);
                $mform->addElement('static', 'description', "<b>$preferencesarea</b>");
                foreach ($key->preferences as $data) {
                    $id = json_encode("id$data->id");
                    $description = $translate->block_edutechpreferences_translator($data->description);
                    $answered = $this->block_edutechpreferences_check_data($USER->id, $id);
                    $mform->addElement('checkbox', "id$data->id", "$description");
                    if ($answered == 1) {
                        $mform->setDefault("id$data->id", array('checked' => '1'));
                    }
                }
            }
            $this->add_action_buttons($cancel = true, $submitlabel = null);
        } else {
              \core\notification::error(get_string("apierror", "block_edutechpreferences"));
        }
    }
    /**
     * Checks in the database if the current student previously filled the form
     * in case of failure returns a zero
     * in case of success, will return the numer of rows prefilled by the user.
     * @param int $userid  Moodle user's id
     * @param int $id edutech_preferences row id
     * @return int $query->count number of student's answers
     */
    private function block_edutechpreferences_check_data($userid, $id) {
        global $DB;
        $id = "%$id%";
        $query = $DB->get_record_sql('SELECT COUNT(id) as count FROM {block_edutechpreferences} WHERE  userid = ?
        AND preferences LIKE ? ', [$userid, $id] );
        if (isset($query->count)) {
            return $query->count;
        } else {
            return 0;
        }
    }
}
