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
 * Completion Levels block definition.
 *
 * Inspired from Michael de Raadt's block_completion_progress.
 *
 * @package    block_completion_levels
 * @copyright  2022 Astor Bizard, 2016 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/locallib.php');

/**
 * Completion Levels block class.
 *
 * Inspired from Michael de Raadt's block_completion_progress.
 *
 * @package   block_completion_levels
 * @copyright 2022 Astor Bizard, 2016 Michael de Raadt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_completion_levels extends block_base {
    /**
     * Sets the block title.
     */
    public function init() {
        $this->title = get_string('defaultblocktitle', 'block_completion_levels');
    }

    /**
     * We have admin settings for this block plugin.
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Controls the block title based on instance configuration.
     *
     * @return bool
     */
    public function specialization() {
        if (isset($this->config->blocktitle) && trim($this->config->blocktitle) > '') {
            $this->title = format_string($this->config->blocktitle);
        }
    }

    /**
     * Controls whether multiple instances of the block are allowed on a page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Defines where the block can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'course-view' => true,
            'site'        => true,
            'mod'         => false,
            'my'          => true,
        ];
    }

    /**
     * Creates the blocks main content.
     *
     * @return object
     */
    public function get_content() {
        global $USER, $COURSE, $OUTPUT, $DB;

        // If content has already been generated, don't waste time generating it again.
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content         = new stdClass();
        $this->content->text   = '';
        $this->content->footer = '';

        // Guests do not have any progress. Don't show them the block.
        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        // Draw the multi-bar content for the Dashboard and Front page.
        if ($COURSE->id == 1) {

            // Show a message when the user is not enrolled in any courses.
            $mycourses = enrol_get_my_courses();
            if (empty($mycourses)) {
                $this->content->text = get_string('notenrolled', 'grades');
                return $this->content;
            }

            $sql = "SELECT bi.*,
                           c.id AS courseid,
                           COALESCE(bp.region, bi.defaultregion) AS region,
                           COALESCE(bp.weight, bi.defaultweight) AS weight
                      FROM {block_instances} bi
                      JOIN {context} ctx ON ctx.id = bi.parentcontextid
                      JOIN {course} c ON c.id = ctx.instanceid
                 LEFT JOIN {block_positions} bp ON bp.blockinstanceid = bi.id
                     WHERE bi.blockname = :blockname
                       AND ctx.contextlevel = :contextcourse
                       AND COALESCE(bp.visible, 1) = 1
                  ORDER BY c.sortorder ASC, region DESC, weight ASC, bi.id";

            $params = array(
                    'blockname' => 'completion_levels',
                    'contextcourse' => CONTEXT_COURSE
            );
            $rawrecords = $DB->get_records_sql($sql, $params);

            $courseblockrecords = array();
            foreach ($rawrecords as $record) {
                if (!isset($mycourses[$record->courseid])) {
                    // This is not a course the user is enrolled in.
                    continue;
                }
                if (!isset($courseblockrecords[$record->courseid])) {
                    $courseblockrecords[$record->courseid] = array();
                }
                $courseblockrecords[$record->courseid][] = $record;
            }
            foreach ($courseblockrecords as $courseid => $blockrecords) {
                $coursebadges = '';

                foreach ($blockrecords as $blockrecord) {
                    $blockinstance = block_instance('completion_levels', $blockrecord);
                    $config = $blockinstance->config;
                    $blockcontext = context_block::instance($blockrecord->id);

                    if (!empty($config->group)
                        && !has_capability('moodle/site:accessallgroups', $blockcontext)
                        && !groups_is_member($config->group, $USER->id)) {
                        continue;
                    }

                    $progress = block_completion_levels_get_progress($config, $USER->id, $courseid);

                    if ($progress === null) {
                        continue;
                    }

                    if ($coursebadges > '') {
                        $coursebadges .= '<hr>';
                    }

                    $link = html_writer::link(
                            new moodle_url('/blocks/completion_levels/student_overview.php',
                                    array('instanceid' => $blockrecord->id, 'courseid' => $courseid)),
                            get_string('viewprogress', 'block_completion_levels'),
                            array('class' => 'text-nowrap')
                            );
                    $coursebadges .= '<h5 class="d-inline-block mr-3">' . $blockinstance->get_title() . '</h5>' . $link;

                    // Display badge and progress bar.
                    $coursebadges .= '<div class="badge-progress-compact">';
                    $coursebadges .= block_completion_levels_get_badge_pix($config, $progress, $blockcontext);
                    $coursebadges .= $progress->display_bar();
                    $coursebadges .= '</div>';

                }

                if ($coursebadges > '') {
                    $this->content->text .= '<hr>';
                    $course = $mycourses[$courseid];
                    $coursetitle = get_string('coursetitle', 'moodle', array('course' => format_string($course->fullname)));
                    $linktext = html_writer::tag('h4', $coursetitle, array('class' => 'mb-3'));
                    $courselink = new moodle_url('/course/view.php', array('id' => $courseid));
                    $this->content->text .= html_writer::link($courselink, $linktext);
                    $this->content->text .= $coursebadges;
                }

            }

            if ($this->content->text === '') {
                $this->content->text = get_string('no_blocks', 'block_completion_levels');
            }
        } else {
            // Gather content for block on regular course.

            // Check if user is in group for block.
            if (!empty($this->config->group)
                && !has_capability('moodle/site:accessallgroups', $this->context)
                && !groups_is_member($this->config->group, $USER->id)) {
                return $this->content;
            }

            $progress = block_completion_levels_get_progress($this->config, $USER->id, $COURSE->id);

            if ($progress === null) {
                if (has_capability('moodle/block:edit', $this->context)) {
                    $this->content->text .= html_writer::div(
                            $OUTPUT->pix_icon('info', get_string('info'), 'block_completion_levels') .
                            get_string('noactivitiestracked', 'block_completion_levels'));
                }
                return $this->content;
            }

            // Display badge and progress bar.
            $this->content->text .= block_completion_levels_get_badge_pix($this->config, $progress, $this->context);
            $this->content->text .= '<div class="mb-2">' . $progress->display_bar() . '</div>';

            // Allow teachers to access the overview page.
            if (has_capability('block/completion_levels:overview', $this->context)) {
                $this->content->text .= html_writer::start_div('overview-buttons text-center mb-2');
                foreach (array('overview', 'details') as $link) {
                    $url = new moodle_url('/blocks/completion_levels/' . $link . '.php',
                            array('instanceid' => $this->instance->id, 'courseid' => $COURSE->id));
                    $icon = block_completion_levels_image($link, array('class' => 'align-top mr-1'));
                    $this->content->text .= html_writer::link(
                            $url,
                            $icon . get_string($link, 'block_completion_levels'),
                            array('class' => 'font-weight-bold text-decoration-none')
                    );
                }
                $this->content->text .= html_writer::end_div();
            }

            $url = new moodle_url('/blocks/completion_levels/student_overview.php',
                    array('instanceid' => $this->instance->id, 'courseid' => $COURSE->id));
            $this->content->text .= '<div class="d-flex justify-content-center">
                                        <a href="' . $url->out() . '" class="btn btn-secondary">' .
                                            get_string('viewprogress', 'block_completion_levels') .
                                        '</a>
                                    </div>';

            // Display wall of fame.
            $this->content->text .= block_completion_levels_wall_of_fame($this);
        }

        return $this->content;
    }

    /**
     * {@inheritDoc}
     * @see block_base::get_required_javascript()
     */
    public function get_required_javascript() {
        parent::get_required_javascript();

        global $COURSE;
        if (!$this->page->user_is_editing() && isset($this->config->markactivities) && $this->config->markactivities) {

            $this->page->requires->string_for_js('completionrequiredforblockinstance', 'block_completion_levels');
            $this->page->requires->js_call_amd('block_completion_levels/activitiesmarker', 'markActivities',
                    array(
                        array_keys(block_completion_levels_get_tracked_activities($COURSE->id, $this->config)),
                        $this->instance->id,
                        $this->title
                    ));
        }
    }

    /**
     * {@inheritDoc}
     * @see block_base::instance_config_save()
     * @param mixed $data
     * @param mixed $nolongerused
     */
    public function instance_config_save($data, $nolongerused = false) {
        $config = clone($data);
        if ($config->pixselect == 'custom') {
            $config->levels_pix = file_save_draft_area_files(
                    $data->levels_pix, $this->context->id, 'block_completion_levels', 'levels_pix', 0);
        }
        parent::instance_config_save($config, $nolongerused);
    }

    /**
     * {@inheritDoc}
     * @see block_base::instance_delete()
     */
    public function instance_delete() {
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_completion_levels');
        return true;
    }

    /**
     * {@inheritDoc}
     * @see block_base::instance_copy()
     * @param int $fromid the id number of the block instance to copy from
     */
    public function instance_copy($fromid) {
        $fromcontext = context_block::instance($fromid);
        $fs = get_file_storage();
        // This extra check if file area is empty adds one query if it is not empty but saves several if it is.
        if (!$fs->is_area_empty($fromcontext->id, 'block_completion_levels', 'levels_pix', 0, false)) {
            $draftitemid = 0;
            file_prepare_draft_area($draftitemid, $fromcontext->id, 'block_completion_levels', 'levels_pix', 0);
            file_save_draft_area_files($draftitemid, $this->context->id, 'block_completion_levels', 'levels_pix', 0);
        }

        return true;
    }
}
