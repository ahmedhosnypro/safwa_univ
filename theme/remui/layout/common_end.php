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
 * A two column_end layout for the remui theme.
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$bodyattributes = $OUTPUT->body_attributes($extraclasses);

if (get_config('theme_remui', 'pagewidth') == 'fullwidth') {
    $bodyattributes = str_replace("limitedwidth", "", $bodyattributes);
}

// Main content Top Region.
if (in_array("side-bottom", $this->page->blocks->get_regions())) {
    $addblockbuttonbottom = $OUTPUT->addblockbutton('side-bottom');
    $sidebottomblocks = $OUTPUT->blocks('side-bottom');
    // Strlen Calculation is total jugad.
    if (trim($addblockbuttonbottom) != '' || (trim($sidebottomblocks) != '' && strlen($sidebottomblocks) > 117)) {
        $templatecontext['addblockbuttonbottom'] = $addblockbuttonbottom;
        $templatecontext['sidebottomblocks'] = $sidebottomblocks;
        $templatecontext['canaddbottomblocks'] = true;
    }
}

// Main content Bottom Region.
if (in_array("side-top", $this->page->blocks->get_regions())) {
    $addblockbuttontop = $OUTPUT->addblockbutton('side-top');
    $sidetopblocks = $OUTPUT->blocks('side-top');
    // Strlen Calculation is total jugad.
    if (trim($addblockbuttontop) != '' || (trim($sidetopblocks) != '' && strlen($sidetopblocks) > 117)) {
        $templatecontext['addblockbuttontop'] = $addblockbuttontop;
        $templatecontext['sidetopblocks'] = $sidetopblocks;
        $templatecontext['canaddtopblocks'] = true;
    }
}

// Edwiser Quick Menu.
if (\theme_remui\toolbox::get_setting('enablequickmenu')) {
    $templatecontext['edw_quick_menu']['collapsed'] = get_user_preferences('edw-quick-menu', true);
    $templatecontext['edw_quick_menu']['menus'] = \theme_remui\utility::edw_quick_menu();
}

// Edwiser navbar layout.
$templatecontext['navlayout'] = \theme_remui\toolbox::get_setting('header-primary-layout-desktop');

$templatecontext['bodyattributes'] = $bodyattributes;
