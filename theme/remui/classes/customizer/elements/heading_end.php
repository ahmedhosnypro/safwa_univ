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
 * Theme customizer heading_end element class
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

namespace theme_remui\customizer\elements;

/**
 * Heading end setting element.
 */
class heading_end extends base {

    /**
     * Get css classes for list element
     *
     * @return string
     */
    public function get_css_classes() {
        return 'py-0';
    }

    /**
     * Do not wrap this with list item.
     *
     * @return bool
     */
    public function wrap_item() {
        return false;
    }

    /**
     * Prepare the output for the setting
     *
     * @return string element output
     */
    public function output() {
        return '</div></div>';
    }
}
