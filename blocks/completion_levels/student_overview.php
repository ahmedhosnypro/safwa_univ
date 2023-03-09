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
 * Completion Levels block student overview function
 *
 * @package    block_completion_levels
 * @copyright  2018 Florent Paccalet, 2021 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $CFG;
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('instanceid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

require_login($courseid);

global $DB, $OUTPUT, $PAGE, $USER;

$course = get_course($courseid);
$context = context_course::instance($courseid);
$PAGE->set_course($course);

// Get specific block config and context.
$blockrecord = $DB->get_record('block_instances', array('id' => $id));
block_completion_levels_check_instance($blockrecord, $context, $course->fullname);
$blockinstance = block_instance('completion_levels', $blockrecord);
$config = $blockinstance->config;

// Set up page parameters.
$PAGE->set_url('/blocks/completion_levels/student_overview.php', array('instanceid' => $id, 'courseid' => $courseid));
$title = $blockinstance->get_title() . ' - ' . get_string('activitiescompletion', 'block_completion_levels');
$PAGE->set_title($title);
$PAGE->set_heading(get_string('pluginname', 'block_completion_levels'));
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('report');

// Start page output.
echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);
echo $OUTPUT->container_start('block_completion_levels');

$table = new html_table();

$table->head = array(
        get_string('type', 'block_completion_levels'),
        get_string('name'),
        get_string('score', 'block_completion_levels'),
        get_string('weight', 'block_completion_levels'),
        get_string('completion', 'block_completion_levels')
);
$table->align = array(
        'right',
        'left',
        'right',
        'right',
        'center'
);
$table->size = array(
        null,
        '50%',
        '10em',
        null,
        null
);

$modinfo = get_fast_modinfo($course, $USER->id);
$totalcomplete = 0;
$progress = block_completion_levels_get_progress($config, $USER->id, $courseid);
if ($progress !== null) {
    $activities = block_completion_levels_get_tracked_activities($courseid, $config);
    foreach ($activities as $activity) {
        $cmid = $activity->id;
        $cminfo = $modinfo->get_cm($cmid);

        if ($cminfo->uservisible) {
            $icon = html_writer::empty_tag('img', array( 'src' => $cminfo->get_icon_url()->out(), 'class' => 'activityicon',
                    'title' => $cminfo->get_module_type_name(), 'value' => $cminfo->modname ));
            $name = html_writer::span($cminfo->get_formatted_name(), 'activity');

            if ($cminfo->url !== null) {
                $linkattributes = array();
                if (!$cminfo->visible) {
                    $linkattributes['class'] = 'dimmed';
                    $linkattributes['title'] = get_string('hiddenfromstudents');
                }
                $name = html_writer::link($cminfo->url, $name, $linkattributes);
            }
        } else {
            $icon = html_writer::span('', '', array('value' => 'zzzzzz'));
            $name = html_writer::span(get_string('hiddenmodule', 'block_completion_levels'));
        }

        $relativescore = $progress->completion_info($cmid);
        $totalcomplete += $relativescore;
        $scoredisplay = '<span value="' . $relativescore . '">' .
                            block_completion_levels_format_user_activity_completion($relativescore, $config) .
                        '</span>';

        $completed = '<span value="' . $relativescore . '">' .
                         block_completion_levels_activity_completion_icon($relativescore) .
                     '</span>';

        $table->data[] = array($icon, $name, $scoredisplay, $activity->weight, $completed);
    }

    $badge = block_completion_levels_get_badge_pix($config, $progress, $blockinstance->context,
            array('class' => 'block_completion_levels-badge-small'));
    $badge = '<div class="position-relative d-inline-block mr-3">' . $badge . '</div>';
    $table->data[] = array(
            '',
            '<b>' . get_string('total') . '</b>',
            $badge . '<b>' . $progress->display() . '</b>',
            '',
            format_float($totalcomplete / count($activities) * 100.0, 2) . '%'
    );

    $table->id = uniqid('block_completion_levels');
    $PAGE->requires->js_call_amd('block_completion_levels/sorttable', 'makeSortable', array($table->id, array(), null, 1));

    echo html_writer::table( $table );
} else {
    echo '<div>' . get_string('nothingtoshow', 'block_completion_levels') . '</div>';
}

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
