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
 * Theme customizer range element class
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

namespace theme_remui\customizer\elements;

/**
 * Range setting element.
 */
class range extends base {

    /**
     * Prepare the output for the setting
     *
     * @return string element output
     */
    public function output() {
        global $OUTPUT;
        $options = $this->options;
        $label = isset($options['label']) ? $options['label'] : get_string($this->name, $this->component);
        $default = $this->get_config();

        $templatecontext = [];
        $templatecontext[] = [
            'name' => $this->name,
            'label' => $label,
            'help' => $this->get_help(),
            'default' => $default,
            'options' => $this->process_options()
        ];

        if (isset($options['responsive']) && $options['responsive'] == true) {
            $templatecontext[0]['classes'] = 'setting-desktop';

            $tablet = $this->name . '-tablet';
            $tabletlabel = $label . ' - ' . get_string('tablet', 'theme_remui');
            $default = $this->get_config($tablet);

            $templatecontext[] = [
                'name' => $tablet,
                'label' => $tabletlabel,
                'help' => $this->get_help(false),
                'default' => $default,
                'classes' => 'setting-tablet',
                'options' => $this->process_options()
            ];

            $mobile = $this->name . '-mobile';
            $mobilelabel = $label . ' - ' . get_string('mobile', 'theme_remui');
            $default = $this->get_config($tablet);

            $templatecontext[] = [
                'name' => $mobile,
                'label' => $mobilelabel,
                'help' => $this->get_help(false),
                'default' => $default,
                'classes' => 'setting-mobile',
                'options' => $this->process_options()
            ];
        }

        return $OUTPUT->render_from_template($this->component . '/customizer/elements/range', ['inputs' => $templatecontext]);
    }
}
