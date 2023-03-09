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
 * @package    local_credit_xp
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group xp_conversion
 */
class xp_conversion_test extends advanced_testcase
{
    public function test_convering_xp_to_credits()
    {
        global $DB, $SITE;

        $this->resetAfterTest();

        /** @var enrol_credit_plugin $credit_enrol */
        $credit_enrol = enrol_get_plugin('credit');

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $id = $DB->insert_record('block_xp', (object)[
            'courseid' => $course->id,
            'userid' => $user->id,
            'xp' => 50
        ]);

        $fieldid = $DB->insert_record('user_info_field', (object)[
            'shortname' => 'credits',
            'name' => 'credits',
            'datatype' => 'text'
        ]);

        set_config('credit_field', $fieldid, 'enrol_credit');
        set_config('conversion_ratio', 2, 'local_credit_xp');

        $task = new \local_credit_xp\task\convert_xp();
        $task->execute();

        $this->assertEquals(100, $credit_enrol->get_user_credits($user->id), 'Ensure XP was converted to credits');

        $task->execute();

        $this->assertEquals(100, $credit_enrol->get_user_credits($user->id), 'Ensure XP wasn\'t converted again');

        $DB->update_record('block_xp', (object)[
            'id' => $id,
            'courseid' => $course->id,
            'userid' => $user->id,
            'xp' => 75
        ]);
        $DB->insert_record('block_xp', (object)[
            'courseid' => $SITE->id,
            'userid' => $user->id,
            'xp' => 25
        ]);

        $task->execute();

        $this->assertEquals(200, $credit_enrol->get_user_credits($user->id), 'Ensure XP was converted to credits');

        $task->execute();

        $this->assertEquals(200, $credit_enrol->get_user_credits($user->id), 'Ensure XP wasn\'t converted again');

        set_config('conversion_ratio', 0.25, 'local_credit_xp');

        $DB->update_record('block_xp', (object)[
            'id' => $id,
            'courseid' => $course->id,
            'userid' => $user->id,
            'xp' => 100
        ]);

        $task->execute();

        $this->assertEquals(206, $credit_enrol->get_user_credits($user->id), 'Ensure XP was converted to credits');

        $task->execute();

        $this->assertEquals(206, $credit_enrol->get_user_credits($user->id), 'Ensure XP wasn\'t converted again');
    }
}