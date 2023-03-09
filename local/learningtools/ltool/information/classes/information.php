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
 * The class defines the Information ltool.
 *
 * @package   ltool_information
 * @copyright bdecent GmbH 2021
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ltool_information;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/learningtools/lib.php');

require_once(dirname(__DIR__).'/lib.php');

/**
 *  The class defines the information ltool
 */
class information extends \local_learningtools\learningtools {

    /**
     * Tool shortname.
     *
     * @var string
     */
    public $shortname = 'information';

    /**
     * Tool context level
     * @var string
     */
    public $contextlevel = 'course';

    /**
     * information name
     * @return string name
     *
     */
    public function get_tool_name() {
        return get_string('information', 'local_learningtools');
    }

    /**
     * information icon
     */
    public function get_tool_icon() {

        return 'fa fa-info';
    }

    /**
     * information icon background color
     */
    public function get_tool_iconbackcolor() {

        return '#343a40';
    }

    /**
     * Load the required javascript files for information.
     *
     * @return void
     */
    public function load_js() {
        // Load note tool js configuration.
        ltool_information_load_js_config();
    }

    /**
     * Get the information tool  content.
     *
     * @return string display tool information plugin html.
     */
    public function get_tool_records() {
        $data = [];
        $data['name'] = $this->get_tool_name();
        $data['icon'] = $this->get_tool_icon();
        $data['ltoolinformation'] = true;
        $data['informationhovername'] = get_string('information', 'local_learningtools');
        $data['iconbackcolor'] = get_config("ltool_{$this->shortname}", "{$this->shortname}iconbackcolor");
        $data['iconcolor'] = get_config("ltool_{$this->shortname}", "{$this->shortname}iconcolor");
        return $data;
    }

    /**
     * Return the template of information fab button.
     *
     * @return string information tool fab button html.
     */
    public function render_template() {
        global $PAGE, $SITE;
        if (local_learningtools_can_visible_tool_incourse()) {
            $coursecontext = \context_course::instance($PAGE->course->id);
            if (has_capability("ltool/information:createinformation", $coursecontext)) {
                $data = $this->get_tool_records();
                return ltool_information_render_template($data);
            }
        }
    }
}

