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
namespace block_edutechpreferences\report;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/edutechpreferences/classes/api/api.php');
require_once($CFG->dirroot . '/blocks/edutechpreferences/classes/translate/translate.php');
use block_edutechpreferences\translate\translate;
use block_edutechpreferences\api\api;
class getreport {

    /**
     * Checks if the course that is trying to access exists before generating the report.
     * @param int $course  Moodle's course id
     * @return int $id Moodle's course id (verified)
     */
    public function block_edutechpreferences_course_exists($courseid) {
        global $DB;
        $id = 0;
        $query = $DB->get_record_sql('SELECT id
                                        FROM {course}
                                        WHERE id = ? ',
                                        [$courseid]);
        if ((int)$query->id > 1) {
            $id = (int)$query->id;
        }
        return $id;
    }

    /**
     * Calculates the summary stats of the students group.
     * @param int $courseid  Moodle's course id
     * @param int $id Moodle's course context
     * @return array $array with the summary stats of the course
     */
    public function block_edutechpreferences_summary_stats($courseid, $context) {
        $totalstudents = $this->block_edutechpreferences_total_students($context->id);
        $totalresponses = $this->block_edutechpreferences_total_responses($context->id);
        $avg = 0;
        if ($totalstudents > 0) {
            $avg = round(($totalresponses * 100) / $totalstudents);
        }
        $array = array('summarystats' => [
            ['name' => get_string("totalstudents", "block_edutechpreferences"), 'number' => $totalstudents],
            ['name' => get_string("totalresponses", "block_edutechpreferences"), 'number' => $totalresponses],
            ['name' => get_string("responserate", "block_edutechpreferences"), 'number' => $avg]
        ]);
        return $array;
    }

    /**
     * Calculates the full report stats of the students group.
     * @param int $courseid  Moodle's course id
     * @param int $context Moodle's course context
     * @return array $array with the full report stats of the course
     */
    public function block_edutechpreferences_report_data($courseid, $context) {
        $apis = new api();
        $translate = new translate();
        $preferenceareas = $apis->block_edutechpreferences_get_list();
        $preferenceareas = json_decode($preferenceareas);
        $totalstudents = $this->block_edutechpreferences_total_students($context->id);
        $array = array();
        if ($preferenceareas != 0) {
            $categoryarray = [];
            foreach ($preferenceareas as $key) {
                $preferencesarea = $translate->block_edutechpreferences_translator($key->preferences_are);
                $areaarray2 = [];
                $responsecount = 0;
                foreach ($key->preferences as $data) {
                    $id = json_encode("id$data->id");
                    $description = $translate->block_edutechpreferences_translator($data->description);
                    if ($totalstudents > 0) {
                        $responsecount = round(
                            ($this->block_edutechpreferences_response_stats($context->id, $id) * 100)
                            /
                            $totalstudents
                        );
                    }
                    array_push($areaarray2, ['name' => $description, 'count' => $responsecount ]);
                }
                array_push($categoryarray, ['category' => $preferencesarea, 'areas' => $areaarray2]);
            }
            $array['stats'] = $categoryarray;
            return $array;
        } else {
            \core\notification::error(get_string("apierror", "block_edutechpreferences"));
            return '';
        }
        return $array;
    }

    /**
     * Creates a 'back' button with the course url to be properly redirected
     * @param int $courseid  Moodle's course id
     * @return array $array the button text and href url
     */
    public function block_edutechpreferences_button_info($courseid) {
        global $CFG;
        $array = array('button' => ["name" => get_string("goback", "block_edutechpreferences"), "url"
         => "$CFG->wwwroot/course/view.php?id=$courseid"]);
        return $array;
    }

    /**
     * Gets the number of students enrolled in a specifict course
     * @param int $context Moodle's course context
     * @return int $totalstudents
     */
    public function block_edutechpreferences_total_students($context) {
        global $DB;
        $sql = "SELECT count(ra.userid) as total
                  FROM {role_assignments} ra
                  JOIN {user} u ON ra.userid = u.id
                  JOIN {role_capabilities} rc ON ra.roleid = rc.roleid
                 WHERE ra.contextid = :context AND rc.capability = :capability";
        $query = $DB->get_record_sql($sql, ['context' => $context, 'capability' => 'block/edutechpreferences:view']);
        $totalstudents = (int)$query->total;
        return $totalstudents;
    }

    /**
     * Gets the number of students enrolled in a specifict course that have filled the EduTech form
     * @param int $context Moodle's course context
     * @return int $totalresponses
     */
    public function block_edutechpreferences_total_responses($context) {
        global $DB;
        $sql = "SELECT count(ra.userid) as total
                  FROM {role_assignments} ra
                  JOIN {user} u ON ra.userid = u.id
                  JOIN {role_capabilities} rc ON ra.roleid = rc.roleid
                  JOIN {block_edutechpreferences} bl ON ra.userid = bl.userid
                 WHERE ra.contextid = :context AND rc.capability = :capability";
        $query = $DB->get_record_sql($sql, ['context' => $context, 'capability' => 'block/edutechpreferences:view']);
        $totalresponses = (int)$query->total;
        return $totalresponses;
    }

    /**
     * Calculates the rate of responses for each student and preferences area.
     * @param int $context Moodle's course context
     * @param int $id preference area id
     * @return int $responsestats number of responses for each preference area
     */
    public function block_edutechpreferences_response_stats($context, $id) {
        global $DB;
        $id = "%$id%";
        $sql = "SELECT COUNT(bl.id) as total
                  FROM {role_assignments} ra
                  JOIN {user} u ON ra.userid = u.id
                  JOIN {role_capabilities} rc ON ra.roleid = rc.roleid
                  JOIN {block_edutechpreferences} bl ON ra.userid = bl.userid
                 WHERE ra.contextid = :context AND rc.capability = :capability AND bl.preferences LIKE :id";
        $query = $DB->get_record_sql($sql, ['context' => $context, 'capability' => 'block/edutechpreferences:view', 'id' => $id]);
        $responsestats = (int)$query->total;
        return $responsestats;
    }
}
