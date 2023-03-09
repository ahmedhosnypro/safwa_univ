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

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $page = new admin_settingpage('local_credit_xp', get_string('pluginname', 'local_credit_xp'));

    $page->add(new admin_setting_configtext(
        'local_credit_xp/conversion_ratio',
        get_string('conversion_ratio', 'local_credit_xp'),
        get_string('conversion_ratio_desc', 'local_credit_xp'),
        1, PARAM_FLOAT));

    $ADMIN->add('localplugins', $page);
}
