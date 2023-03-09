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
 * The main rumbletalkchat configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_rumbletalkchat
 * @copyright  2022 RumbleTalk, LTD {@link https://www.rumbletalk.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_rumbletalkchat
 * @copyright  2022 RumbleTalk, LTD {@link https://www.rumbletalk.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_rumbletalkchat_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Welcome message.
        $mform->addElement('html', get_string('welcome_message', 'rumbletalkchat'));

        // Input field for Chat Name.
        $mform->addElement('text', 'name', get_string('generalchatname', 'rumbletalkchat'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'generalchatname', 'rumbletalkchat');
        $mform->setDefault('name', get_string('default_chat_name', 'rumbletalkchat'));
        // Input field for HashCode.
        $mform->addElement('text', 'code', get_string('embed_code', 'rumbletalkchat'));
        $mform->addRule('code', get_string('error_code_required', 'rumbletalkchat'), 'required', null, 'client');
        $mform->addRule('code', get_string('maximumchars', '', 8), 'maxlength', 8, 'client');
        $mform->addRule('code', get_string('error_code_chars', 'rumbletalkchat'), 'minlength', 8, 'client');
        $mform->addHelpButton('code', 'code', 'rumbletalkchat');
        $mform->setType('code', PARAM_TEXT);

        // Input field for Width.
        $mform->addElement('text', 'width', get_string('embed_width', 'rumbletalkchat'));
        $mform->addRule('width', get_string('error_numbers_only', 'rumbletalkchat'), 'numeric', null, 'client');
        $mform->addRule('width', get_string('error_width_required', 'rumbletalkchat'), 'required', null, 'client');
        // Width Range: 800 - 1000.
        $mform->addRule('width', get_string('error_width_range', 'rumbletalkchat'), 'regex', '/^([6-9][0-9][0-9])?$|^1000$/', 'client');
        $mform->addHelpButton('width', 'width', 'rumbletalkchat');
        $mform->setType('width', PARAM_TEXT);
        $mform->setDefault('width', get_string('default_width', 'rumbletalkchat'));

        // Input field for Height.
        $mform->addElement('text', 'height', get_string('embed_height', 'rumbletalkchat'));
        $mform->addRule('height', get_string('error_numbers_only', 'rumbletalkchat'), 'numeric', null, 'client');
        $mform->addRule('height', get_string('error_height_required', 'rumbletalkchat'), 'required', null, 'client');
        // Height Range: 500 - 800.
        $mform->addRule('height', get_string('error_height_range', 'rumbletalkchat'), 'regex', '/^([4-7][0-9][0-9])?$|^800$/', 'client');
        $mform->addHelpButton('height', 'height', 'rumbletalkchat');
        $mform->setType('height', PARAM_TEXT);
        $mform->setDefault('height', get_string('default_height', 'rumbletalkchat'));

        // Checkbox for Members Only.
        $mform->addElement('advcheckbox', 'members', get_string('login_type', 'rumbletalkchat'), get_string('members_only', 'rumbletalkchat'), array('group' => 1), array(0, 1));
        $mform->addHelpButton('members', 'members', 'rumbletalkchat');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
