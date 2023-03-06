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
 * A drawer based layout for the remui theme.
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("{$CFG->dirroot}/theme/{$CFG->theme}/layout/common.php");

$templatecontext['pagelayout_frontpage'] = true;

if (\theme_remui\toolbox::get_setting('frontpagechooser') == 0) {
    $extraclasses[] = 'old-frontpage';
}

// Must be called before rendering the template.
// This will ease us to add body classes directly to the array.
require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');

$templatecontext['bodyattributes'] = str_replace("limitedwidth", "", $templatecontext['bodyattributes']);

if (is_plugin_available("local_remuihomepage") && \theme_remui\toolbox::get_setting('frontpagechooser') == 1) {
    $homepage = new local_remuihomepage_plugin();
    $templatecontext['contextid'] = context_system::instance()->id;
    $templatecontext = $homepage->layout($templatecontext);

    // As we have older plugin we had to do some tweaks to context data.
    $floatingbtns = $templatecontext['floating_buttons'];
    $floatingbtns = str_replace('fa fa-cog', 'edw-icon edw-icon-Setting', $floatingbtns);
    $floatingbtns = str_replace('fa fa-eye', 'edw-icon edw-icon-Show', $floatingbtns);
    $floatingbtns = str_replace('fa fa-cloud-upload', 'edw-icon edw-icon-Upload', $floatingbtns);
    $floatingbtns = str_replace('fa fa-plus', 'rotate-45 edw-icon edw-icon-Cancel', $floatingbtns);
    $templatecontext['floating_buttons'] = $floatingbtns;

} else {
    // Frontpage context.
    // Slider.
    $templatecontext['slider'] = \theme_remui\Sitehomehandler::get_slider_data();

    // Aboutus data.
    if (1 != \theme_remui\toolbox::get_setting('frontpageblockdisplay')) {
        $templatecontext['aboutus'] = \theme_remui\sitehomehandler::get_aboutus_data();
    }

    // Testimonial section data.
    $templatecontext['testimoniallist'] = \theme_remui\sitehomehandler::get_testimonial_data();

    // Blogs data.
    $recentblogs = \theme_remui\sitehomehandler::get_recent_blogs(0, 3);
    if (!empty($CFG->enableblogs) && is_array($recentblogs) && !empty($recentblogs)) {
        $hasblogs = true;
        $templatecontext['blog'] = [
            'blogs' => array_values($recentblogs),
        ];
    }
}

echo $OUTPUT->render_from_template('theme_remui/frontpage', $templatecontext);
