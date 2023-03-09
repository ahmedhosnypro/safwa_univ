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
 * Completion Levels block helper functions
 *
 * @package    block_completion_levels
 * @copyright  2021 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_completion_levels\progress;

/**
 * Check consistency of given block instance with given parent context. Throws a moodle_exception on failed check.
 *
 * @param object|int $instanceorid Block instance or id.
 * @param context $context Parent context of the block.
 * @param string $errorcontext Information about the context in which a failed check occured.
 * @param string $errorurl URL to redirect to on a failed check.
 * @throws moodle_exception
 */
function block_completion_levels_check_instance($instanceorid, $context, $errorcontext = '', $errorurl = '') {
    global $DB;
    if (is_object($instanceorid)) {
        $blockrecord = $instanceorid;
    } else {
        $blockrecord = $DB->get_record('block_instances', array('id' => $instanceorid));
    }
    if ($blockrecord === false || $blockrecord->parentcontextid != $context->id || $blockrecord->blockname != 'completion_levels') {
        throw new moodle_exception('invalidblockinstance', 'error', $errorurl,
                $errorcontext . ' / ' . get_string('pluginname', 'block_completion_levels'));
    }
}

/**
 * Build an image related with this block.
 * @param string $imagename
 * @param array $attributes
 * @return string
 */
function block_completion_levels_image($imagename, $attributes = array()) {
    global $OUTPUT;
    $attributes['src'] = $OUTPUT->image_url($imagename, 'block_completion_levels');
    return html_writer::empty_tag('img', $attributes);
}

/**
 * Determine what is the maximum badge level, from stored images.
 * This proceeds by efficient dichotomic search of the lowest non-existing image.
 * @param mixed $fileareacontextid File area context ID where images are stored.
 * @param string $filearea File area name where images are stored.
 * @return null|number Returns highest badge level found, null if none found.
 */
function block_completion_levels_find_highest_badge($fileareacontextid, $filearea) {
    $fs = get_file_storage();
    if (!$fs->file_exists($fileareacontextid, 'block_completion_levels', $filearea, 0, '/', '0.png')) {
        return null;
    }
    $lowbound = 0;
    $highbound = null;
    $i = 1;
    while ($highbound === null || $lowbound < $highbound - 1) {
        if ($fs->file_exists($fileareacontextid, 'block_completion_levels', $filearea, 0, '/', $i . '.png')) {
            $lowbound = $i;
            $i = $highbound === null ? $i * 2 : (int)round(($i + $highbound) / 2);
        } else {
            $highbound = $i;
            $i = (int)round(($i + $lowbound) / 2);
        }
    }
    return $lowbound;
}

/**
 * Build an image of the badge associated with a progress for a block instance.
 * @param object $blockconfig The block instance configuration object.
 * @param progress $progress The progress we want the badge of.
 * @param context_block $context The context of the block.
 * @param array $attributes Extra attributes for the badge.
 * @return string HTML fragment of the badge.
 */
function block_completion_levels_get_badge_pix($blockconfig, $progress, $context, $attributes = array()) {
    if (!isset($attributes['class'])) {
        $attributes['class'] = '';
    }
    $attributes['class'] .= ' block_completion_levels-badge d-block mx-auto';

    // Proceed by try-fallback in order custom -> admin -> default.

    $pixselect = isset($blockconfig->pixselect) ? $blockconfig->pixselect : 'admin';
    if ($pixselect == 'custom') {
        // Try with custom pix.
        $highestlevel = block_completion_levels_find_highest_badge($context->id, 'levels_pix');
        if ($highestlevel === null) {
            // No custom pix found, fallback to admin pix.
            $pixselect = 'admin';
        } else {
            // Custom pix are set, success!
            $userlevel = (int)floor($progress->percentage * $highestlevel / 100);
            $attributes['src'] = moodle_url::make_pluginfile_url($context->id, 'block_completion_levels',
                    'levels_pix', 0, '/', $userlevel);
            $attributes['alt'] = $attributes['title'] = get_string('levela', 'block_completion_levels', $userlevel);
            return html_writer::empty_tag('img', $attributes);
        }
    }

    if ($pixselect == 'admin') {
        // Try with admin pix.
        $highestlevel = block_completion_levels_find_highest_badge(1, 'preset');
        if ($highestlevel === null || !get_config('block_completion_levels', 'enablecustomlevelpix')) {
            // No admin pix found or admin pix disabled, fallback to default pix.
            $pixselect = 'default';
        } else {
            // Admin pix are set, success!
            $userlevel = (int)floor($progress->percentage * $highestlevel / 100);
            $attributes['src'] = moodle_url::make_pluginfile_url(1, 'block_completion_levels',
                    'preset', 0, '/', $userlevel);
            $attributes['alt'] = $attributes['title'] = get_string('levela', 'block_completion_levels', $userlevel);
            return html_writer::empty_tag('img', $attributes);
        }
    }

    // Default pix.
    $attributes['class'] .= ' block_completion_levels-badge-default text-white text-center';
    $highestlevel = isset($blockconfig->maxlevel) ? $blockconfig->maxlevel : 10;
    $userlevel = (int)floor($progress->percentage * $highestlevel / 100);
    $pix = (int)floor($userlevel / $highestlevel * 10);
    if ($userlevel >= 100) {
        $attributes['class'] .= ' block_completion_levels-badge-3digits';
    } else if ($userlevel >= 10) {
        $attributes['class'] .= ' block_completion_levels-badge-2digits';
    }
    if (!isset($attributes['style'])) {
        $attributes['style'] = '';
    }
    $attributes['style'] .= ';background-image: url(/blocks/completion_levels/pix/' . $pix . '.png)';
    $attributes['title'] = 'Level ' . $userlevel;
    return html_writer::tag('div', '<span>' . $userlevel . '</span>', $attributes);
}

/**
 * Build the wall of fame of a block instance.
 * @param block_completion_levels $block
 * @return string
 */
function block_completion_levels_wall_of_fame($block) {
    global $USER, $OUTPUT, $COURSE;
    $limit = isset($block->config->walloffamesize) ? $block->config->walloffamesize : 0;
    $group = isset($block->config->group) ? $block->config->group : 0;

    if ($limit != 0) {

        $coursecontext = context_course::instance($COURSE->id);
        $users = block_completion_levels_get_users($COURSE->id, $block->config, $group, 'student', $coursecontext->id);

        // Filter by user groups if needed.
        if ($block->config->showonlycogroups
                && (!has_capability('block/completion_levels:overview', $block->context)
                    || !has_capability('moodle/site:accessallgroups', $coursecontext))) {
            // TODO consider only groups in grouping defined at course level.
            // TODO if user has no groups, show a general ranking of all users.
            $usergroups = groups_get_all_groups($COURSE->id, $USER->id);
            if (empty($usergroups)) {
                // User has no groups, do not bother filtering.
                $users = array($USER->id => $users[$USER->id]);
            } else {
                $cogroupusers = groups_get_groups_members(array_keys($usergroups));
                $users = array_uintersect($users, $cogroupusers, function($u1, $u2) {
                    return $u1->id - $u2->id;
                });
            }
        }

        $usersprogress = block_completion_levels_get_progress($block->config, array_keys($users), $COURSE->id);

        // TODO Sort by completion date (if possible and not too costly).
        // See for example completion_info::get_user_completion()->timecompleted.
        uasort($usersprogress, 'block_completion_levels\progress::compare');

    } else {
        $usersprogress = array();
    }

    if (!empty($usersprogress)) {
        $i = 1;
        $idisplay = 1;
        $lastidisplayed = 1;
        $previouscompletion = -1;
        $currentuserdone = false;
        $templatecontext = new stdClass();
        $templatecontext->wofpixsrc = $OUTPUT->image_url('winner', 'block_completion_levels');
        $templatecontext->groupname = ($group > 0) ? '(' . groups_get_group_name($group) . ')' : '';
        $templatecontext->users = array();

        foreach ($usersprogress as $userid => $progress) {

            // Manage ranking equality: increase rank only if there is a strict difference in progress.
            if ($progress->value != $previouscompletion) {
                $idisplay = $i;
            }
            $previouscompletion = $progress->value;

            $user = new stdClass();
            $user->i = $idisplay . ')';
            $user->currentuser = ($userid == $USER->id);
            if (!isset($block->config->anonymous) || !$block->config->anonymous || $user->currentuser) {
                if (isset($block->config->usealternatenames) && $block->config->usealternatenames
                        && isset($users[$userid]->alternatename) && trim($users[$userid]->alternatename) > '') {
                    $user->name = trim($users[$userid]->alternatename);
                } else {
                    $user->name = fullname($users[$userid]);
                }
            } else {
                $user->name = '';
            }

            if ($i > $limit && $limit != -1) {
                if ($currentuserdone) {
                    break;
                } else if ($user->currentuser) {
                    if ($lastidisplayed != $idisplay) {
                        $ellipsisrow = new stdClass();
                        $ellipsisrow->i = '<span class="ranking-ellipsis">&bull;&bull;&bull;</span>';
                        $ellipsisrow->progress = '';
                        $ellipsisrow->currentuser = false;
                        $ellipsisrow->name = '';
                        $templatecontext->users[] = $ellipsisrow;
                    }
                    $user->progress = $progress->display();
                    $templatecontext->users[] = $user;
                    break;
                } else {
                    $i++;
                    continue;
                }
            }

            $currentuserdone = $currentuserdone || $user->currentuser;

            $user->progress = $progress->display();

            $lastidisplayed = $idisplay;
            $templatecontext->users[] = $user;

            $i++;
        }

        $html = $OUTPUT->render_from_template('block_completion_levels/walloffame', $templatecontext);
    } else {
        $html = '';
    }

    return $html;
}

/**
 * Retrieve and return progress for given users, related to a block instance.
 * @param object $blockconfig The block instance configuration object.
 * @param int|array $userids An array of user IDs, or a single user ID.
 * @param int $courseid Course ID.
 * @return progress|progress[]|null
 *      If $userids is a single value, returns a progress object, or null if no activities are tracked.
 *      If $userids is an array, returns an array of [userid => progress object], or an empty array if no activities are tracked.
 */
function block_completion_levels_get_progress($blockconfig, $userids, $courseid) {
    global $DB;
    $activities = block_completion_levels_get_tracked_activities($courseid, $blockconfig);

    if (!is_array($userids)) {
        $singlevalue = true;
        $userids = array($userids);
    } else {
        $singlevalue = false;
    }

    if (empty($activities)) {
        return $singlevalue ? null : array();
    }

    // TODO find a way to cache completion data (/!\ cache it per block instance (or trackingmethod), as trackingmethod may differ).
    $completioninfo = array();
    foreach ($userids as $userid) {
        $completioninfo[$userid] = array();
    }

    $nusers = count($userids);
    // Query by slices of at most 1000 users, to avoid too large IN statement.
    for ($i = 0; $i < $nusers; $i += 1000) {
        list($inuserssql, $params) = $DB->get_in_or_equal(
                array_slice($userids, $i, min([$nusers - $i, 1000])),
                SQL_PARAMS_NAMED);
        if ($blockconfig->trackingmethod == 0) {
            // Completion mode.
            $sql = "SELECT cmc.id, cmc.userid, cmc.coursemoduleid as cmid, cmc.completionstate
                    FROM {course_modules} cm
                    JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                    WHERE cm.course = :courseid AND cmc.userid $inuserssql";
            $params['courseid'] = $courseid;

            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $completiondata) {
                $completioninfo[$completiondata->userid][$completiondata->cmid] =
                        in_array($completiondata->completionstate, array(COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS)) ? 1 : 0;
            }
            $rs->close();
        } else {
            // Gradebook mode.
            $sql = "SELECT gg.id, gg.userid, cm.id as cmid, gg.finalgrade, gg.rawgrade, gg.rawgrademax, gg.rawgrademin
                    FROM {grade_grades} gg
                    JOIN {grade_items} gi ON gi.id = gg.itemid
                    JOIN {modules} m ON m.name = gi.itemmodule
                    JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
                    WHERE gi.courseid = :courseid AND gg.userid $inuserssql";
            $params['courseid'] = $courseid;

            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $gradedata) {
                $grade = $gradedata->finalgrade !== null ? $gradedata->finalgrade : $gradedata->rawgrade;
                if ($grade !== null) {
                    $relativegrade = ($grade - $gradedata->rawgrademin) / ($gradedata->rawgrademax - $gradedata->rawgrademin);
                    $completioninfo[$gradedata->userid][$gradedata->cmid] = $relativegrade;
                }
            }
            $rs->close();
        }
    }

    $progresses = array();

    foreach ($userids as $userid) {

        $progress = new progress($blockconfig->progressover);

        $progressvalue = 0;
        $totalweight = 0;
        foreach ($activities as $activity) {
            // TODO manage activities with weight 0 (?).
            $weight = $activity->weight ?: 1;
            $totalweight += $weight;
            if (isset($completioninfo[$userid][$activity->id])) {
                $progressvalue += $completioninfo[$userid][$activity->id] * $weight;
                $progress->set_completion_info($activity->id, $completioninfo[$userid][$activity->id]);
            }
        }

        $progress->set($progressvalue / $totalweight);

        $progresses[$userid] = $progress;
    }

    if ($singlevalue) {
        return reset($progresses);
    } else {
        return $progresses;
    }
}

/**
 * Format completion info of a course module, as to be displayed.
 * @param number $relativecompletion Completion info for a course module, as returned by progress->completion_info()*;
 * @param object $blockconfig The block instance configuration object.
 * @return string
 */
function block_completion_levels_format_user_activity_completion($relativecompletion, $blockconfig) {
    if ($relativecompletion !== null) {
        if (!isset($blockconfig->trackingmethod) || $blockconfig->trackingmethod == 0) {
            return $relativecompletion;
        } else {
            return ((int)round($relativecompletion * 100)) . '%';
        }
    } else {
        return '-';
    }
}

/**
 * Build an icon from a completion info of a course module.
 * @param number $relativecompletion Completion info for a course module, as returned by progress->completion_info()*;
 * @param string $titlecontext Extra context to add to the icon title.
 * @return string
 */
function block_completion_levels_activity_completion_icon($relativecompletion, $titlecontext = null) {
    // TODO Use the i/completion-xxx icons (as on course page), and manage manual completion from our pages.
    global $OUTPUT;
    if ($relativecompletion == 1) {
        $cicon = 'completed';
        $title = get_string('completed', 'completion');
    } else if ($relativecompletion > 0) {
        $cicon = 'partiallycompleted';
        $title = get_string('partiallycompleted', 'block_completion_levels', ((int)round($relativecompletion * 100)) . '%');
    } else if ($relativecompletion === null) {
        $cicon = 'nocompletiondata';
        $title = get_string('notcompletedyet', 'block_completion_levels');
    } else {
        $cicon = 'notcompleted';
        $title = get_string('notcompleted', 'completion');
    }
    if ($titlecontext !== null) {
        $title = get_string('contextualizedstring', 'block_completion_levels',
                array('context' => $titlecontext, 'content' => $title));
    }
    return $OUTPUT->pix_icon($cicon, $title, 'block_completion_levels');
}

/**
 * Used in details.php and overview.php to print two tabs for navigation.
 * @param string $current Current tab name ('details' or 'overview').
 * @param int $blockinstanceid Block instance ID.
 * @param int $courseid Course ID.
 * @param int $group Selected group (0 for all groups).
 * @param int $role Selected role (0 for all roles).
 */
function block_completion_levels_print_view_tabs($current, $blockinstanceid, $courseid, $group, $role) {
    global $OUTPUT;
    $parameters = array(
            'instanceid' => $blockinstanceid,
            'courseid'   => $courseid,
            'group'      => $group,
            'role'       => $role
    );

    $tabs = array();
    foreach (array('overview', 'details') as $tab) {
        $tabs[] = new tabobject(
                $tab,
                new moodle_url('/blocks/completion_levels/' . $tab . '.php', $parameters),
                get_string($tab, 'block_completion_levels')
        );
    }

    echo $OUTPUT->tabtree($tabs, $current);
}

/**
 * Retrieve and return all users a block instance might consider, filtered by some criteria.
 * @param number $courseid Course ID.
 * @param object $blockconfig The block instance configuration object.
 * @param number $groupid Limit users who belong to this group (0 for all groups).
 * @param number|string $rolearchetypeorid Limit users who have the given role ID or archetype (0 for all roles).
 * @param number $contextid Course context ID, required if $rolearchetypeorid is provided.
 * @param bool $withlastaccess If true, include information about last access to course as lastonlinetime.
 * @return array
 */
function block_completion_levels_get_users($courseid, $blockconfig, $groupid = 0,
        $rolearchetypeorid = 0, $contextid = null, $withlastaccess = false) {
    global $DB;
    $select = "SELECT DISTINCT " . user_picture::fields('u'); // Distinct is needed because there can be duplicate enrolments.
    $from = " FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id
              JOIN {enrol} e ON (e.id = ue.enrolid)";
    $where = " WHERE e.courseid = :courseid";
    $params = array('courseid' => $courseid);
    if ($withlastaccess) {
        $select .= ", COALESCE(ul.timeaccess, 0) AS lastonlinetime";
        $from .= " LEFT JOIN {user_lastaccess} ul ON (ul.userid = u.id AND ul.courseid = :courseid2)";
        $params['courseid2'] = $courseid;
    }
    if ($rolearchetypeorid !== 0) {
        if (is_number($rolearchetypeorid)) {
            // Filter by role ID.
            $from .= " JOIN {role_assignments} ra ON ra.userid = u.id";
            $where .= " AND ra.contextid = :contextid AND ra.roleid = :roleid";
            $params['contextid'] = $contextid;
            $params['roleid'] = $rolearchetypeorid;
        } else {
            // Filter by role archetype.
            $from .= " JOIN {role_assignments} ra ON ra.userid = u.id
                       JOIN {role} r ON r.id = ra.roleid";
            $where .= " AND ra.contextid = :contextid AND r.archetype = :rolearchetype";
            $params['contextid'] = $contextid;
            $params['rolearchetype'] = $rolearchetypeorid;
        }
    }
    if ($groupid != 0) {
        $from .= " JOIN {groups_members} g ON (g.userid = u.id AND g.groupid = :groupid)";
        $params['groupid'] = $groupid;
    }
    if (!isset($blockconfig->filterinactiveusers) || $blockconfig->filterinactiveusers) {
        // Limit to users not inactive.
        $where .= " AND ue.status = :enrolactive AND e.status = :enrolenabled
                    AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)";
        $params['enrolactive'] = ENROL_USER_ACTIVE;
        $params['enrolenabled'] = ENROL_INSTANCE_ENABLED;
        $params['now1'] = time();
        $params['now2'] = $params['now1'];
    }
    return $DB->get_records_sql($select . $from . $where, $params);
}

/**
 * Get all activites a given block instance is currently tracking.
 * This filters out activites that are set to be tracked, but not trackable with current settings.
 * @param int $courseid Course ID.
 * @param object $blockconfig The block instance configuration object.
 * @return array of stdClass containing fields id (cmid) and weight.
 */
function block_completion_levels_get_tracked_activities($courseid, $blockconfig) {
    if (!isset($blockconfig->trackingmethod) || $blockconfig->trackingmethod == 0) {
        $trackingmode = 0;
    } else {
        $trackingmode = 1;
    }
    $trackable = block_completion_levels_get_trackable_activities($courseid, $trackingmode);
    $activities = array();
    foreach ($trackable as $activity) {
        $tracked = isset($blockconfig->activity[$activity->id]['checkbox']) ? $blockconfig->activity[$activity->id]['checkbox'] : 0;
        if (!$tracked) {
            continue;
        }
        $activitydata = new stdClass();
        $activitydata->id = $activity->id;
        $activitydata->weight = $blockconfig->activity[$activity->id]['weight'] ?: 1;
        $activities[$activity->id] = $activitydata;
    }
    return $activities;
}

/**
 * Returns all activities this block can track completion of.
 * @param int $courseid Course ID.
 * @param mixed|null $trackingmethod If set, return only activities trackable for this trackingmethod.
 * @return array[]
 */
function block_completion_levels_get_trackable_activities($courseid, $trackingmethod = null) {
    global $DB;
    $modinfo = get_fast_modinfo($courseid, -1);
    $activities = array();
    if ($trackingmethod === null || $trackingmethod == 0) {
        $completioninfo = new completion_info(get_course($courseid));
    }
    if ($trackingmethod === null || $trackingmethod != 0) {
        $cmswithgradeitems = $DB->get_records_sql(
                "SELECT cm.id as cmid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                INNER JOIN {grade_items} gi ON gi.itemmodule = m.name AND gi.iteminstance = cm.instance
                WHERE cm.course = :courseid",
                array('courseid' => $courseid));
    }
    foreach ($modinfo->get_cms() as $cm) {
        $activitydata = new stdClass();
        $activitydata->id = $cm->id;
        if ($trackingmethod === null || $trackingmethod == 0) {
            $activitydata->completionenabled = $completioninfo->is_enabled($cm);
        }
        if ($trackingmethod === null || $trackingmethod != 0) {
            $function = "{$cm->modname}_supports";
            $activitydata->hasgrades = function_exists($function) && $function(FEATURE_GRADE_HAS_GRADE)
                    && isset($cmswithgradeitems[$cm->id]);
        }
        if ((($trackingmethod === null || $trackingmethod == 0) && $activitydata->completionenabled)
                || (($trackingmethod === null || $trackingmethod != 0) && $activitydata->hasgrades)) {
            $activities[$cm->id] = $activitydata;
        }
    }
    return $activities;
}

/**
 * Retrieve a role ID corresponding to the 'student' archetype in the given context.
 * @param int $contextid Context ID.
 * @return int Role ID, 0 if not found.
 */
function block_completion_levels_get_student_role_id($contextid) {
    global $DB;

    $sql = "SELECT r.id
                FROM {role} r
                JOIN {role_assignments} a ON a.roleid = r.id
                WHERE a.contextid = :contextid AND r.archetype = :archetype";

    $params = array('contextid' => $contextid, 'archetype' => 'student');
    $studentrole = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
    return $studentrole ? $studentrole->id : 0;
}

/**
 * Retrieve all users that are eligible for block completion notifications.
 * @param int $courseid
 * @param string $fields
 * @return array of user database records.
 */
function block_completion_levels_get_notifiable_users($courseid, $fields = "u.*") {
    global $DB;
    $context = context_course::instance($courseid);
    $roles = get_roles_with_caps_in_context($context, array('moodle/course:manageactivities'));
    list($insql, $params) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED);
    $sql = "SELECT DISTINCT $fields
                  FROM {user} u
                  JOIN {role_assignments} r ON r.userid = u.id
                 WHERE r.contextid = :contextid AND r.roleid $insql";
    $params['contextid'] = $context->id;
    return $DB->get_records_sql($sql, $params);
}

/**
 * Call javascript for edit_form.
 *
 * @param string $function Name of the function to call.
 * @param array $params Javascript function params.
 * @param array|string $strings String identifier or array of strings identifiers to pass to javascript.
 */
function block_completion_levels_require_edit_form_javascript($function, $params = array(), $strings = array()) {
    global $PAGE;
    $PAGE->requires->strings_for_js((array)$strings, 'block_completion_levels');
    $PAGE->requires->js_call_amd('block_completion_levels/editform', $function, $params);
}
