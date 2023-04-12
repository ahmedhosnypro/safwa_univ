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
 * Information ltool lib test cases defined.
 *
 * @package   ltool_information
 * @copyright bdecent GmbH 2021
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ltool_information;

/**
 * Informaion subplugin for learningtools phpunit test cases defined.
 */
class ltool_information_test extends \advanced_testcase {

    /**
     * Create custom page instance and set admin user as loggedin user.
     *
     * @return void
     */
    public function setup(): void {
        global $CFG, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->summary = "Test course summary";
        $this->courseimg = '';
        $this->course = $this->getDataGenerator()->create_course(array('summary' => $this->summary));
        $this->user = $this->getDataGenerator()->create_user();
    }

    /**
     * Test access_data
     * @covers ::ltool_information_get_coursesummary
     */
    public function test_ltool_information_access_data() {
        $course = new \core_course_list_element($this->course);
        $coursesummary = ltool_information_get_coursesummary($course);
        $coursesummary = format_string($coursesummary);
        $this->assertEquals($coursesummary, $this->course->summary);
    }
}
