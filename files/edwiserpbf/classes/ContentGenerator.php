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
 * @package   local_edwiserpbf
 * @copyright (c) 2022 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Gourav Govande
 */
namespace filter_edwiserpbf;
defined('MOODLE_INTERNAL') || die();
use stdClass;
use core_course_category;

define("_CATID", "catid");
define("_LAYOUT", "layout");
define("EDWISERPBF_LAYOUT_TABLE", "edw_page_block_layouts");

/**
 * class ContentGenerator
 */
class ContentGenerator
{

    private $catiddefault = null;
    private $layoutiddefault = null;

    public function generate_courses($tags) {
        global $OUTPUT, $CFG, $DB;
        require_once($CFG->dirroot. '/filter/edwiserpbf/lib.php');
        require_once($CFG->dirroot.'/course/renderer.php');
        $tags = $this->validate_tags($tags);

        if ($tags->error) {
            // returning the error msg
            return $tags->msg;
        }

        $tags = $tags->tags; // We have $tags->tags after validation

        $catids = explode(",", $tags[_CATID]);

        $finalcourses = ["courses" => []];

        $count = 0;

        while (count($catids) > $count) {
            $catid = $catids[$count];

            if ($catid == "all" || $catid == 0) {
                $finalcourses['courses'] = get_courses();
                break;
            }

            $catinst = core_course_category::get($catid);
            if (!$catinst->is_uservisible()) {
                continue;
            }

            $children = $catinst->get_all_children_ids();

            foreach ($children as $key => $child) {
                if (!in_array($child, $catids)) {
                    $catids[] = $child;
                }
            }

            $courses = get_courses($catid);
            $finalcourses['courses'] = array_merge($finalcourses['courses'], $courses);
            $count++;
        }

        if (empty($finalcourses['courses'])) {
            return $this->no_data_available();
        }

        $limit = $tags["layout"]->constraints->limit;
        $group = $tags["layout"]->constraints->group;

        $count = 1;
        $courses = [];
        $isactive = true;
        $itemnumber = 0;

        // Check to show navigation or not.
        // -1 to remove system level course.
        if (count($finalcourses['courses']) - 1 > $group) {
            $courses['shownav'] = true;
        }

        foreach ($finalcourses['courses'] as $key => $course) {

            if ($count > $limit && $limit > -1) {
                break;
            }
            if ($course->id == 0 || $course->id == 1 ) {
                continue;
            }
            if ($isactive) {
                $courses["item"][$itemnumber]["isactive"] = true;
                $isactive = false;
            }
            $corecourselistelement = new \core_course_list_element($course);
            $chelper = new \coursecat_helper();
            $course->courseimage = $this->get_course_image($course);
            $course->courseurl = $CFG->wwwroot."/course/view.php?id=".$course->id;

            $record = $DB->get_record('course_categories', array('id' => $course->category));
            if ($record) {
                $course->categoryname = $record->name;
            }

            $teacher = get_course_teacher($course);
            if ($teacher) {
                $course->teacher = $teacher;
            }
            $epoch = $course->timemodified;
            $dt = new \DateTime("@$epoch");
            $course->lastmodified = $dt->format('d M, Y');

            if (isset($course->startdate)) {

                $epoch = $course->startdate;
                $dt = new \DateTime("@$epoch");
                $course->startdate = $dt->format('d M, Y');

                $startdate['day'] = substr($course->startdate, 0, 2);
                $startdate['month'] = substr($course->startdate, 3, 3);
                $startdate['year'] = substr($course->startdate, 8, 4);

                $course->startdate = $startdate;
            }

            $coursesummary = strip_tags($chelper->get_course_formatted_summary($corecourselistelement));
            $coursesummary = preg_replace('/\n+/', '', $coursesummary);
            $course->shortsummary = strlen($coursesummary) > 80 ? mb_substr($coursesummary, 0, 80) . "..." : $coursesummary;

            $courses["item"][$itemnumber]["courses"][] = $course;
            if ($count % $group == 0) {
                $itemnumber++;
            }

            $count++;
        }

        return $this->generate_content($tags[_LAYOUT], $courses);
    }

    public function get_course_image($course) {
        global $CFG, $OUTPUT;
        $corecourselistelement = new \core_course_list_element($course);
        // Course image.
        foreach ($corecourselistelement->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            $courseimage = file_encode_url(
                "$CFG->wwwroot/pluginfile.php",
                '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                $file->get_filearea(). $file->get_filepath(). $file->get_filename(),
                !$isimage
            );
            if ($isimage) {
                break;
            }
        }
        if (empty($courseimage)) {
            $courseimage = $OUTPUT->get_generated_image_for_id($course->id);
        }

        return $courseimage;
    }

    public function generate_categories($tags) {
        global $OUTPUT, $CFG;
        $tags = $this->validate_tags($tags);

        if ($tags->error) {
            // returning the error msg
            return $tags->msg;
        }

        $tags = $tags->tags; // We have $tags->tags after validation

        $limit = $tags["layout"]->constraints->limit;
        $group = $tags["layout"]->constraints->group;
        $count = 1;
        $categories = [];
        $isactive = true;
        $itemnumber = 0;

        $data = core_course_category::get_all();

        if (empty($data)) {
            return $this->no_data_available();
        }

        // Check to show navigation or not.
        if (count($data) > $group) {
            $categories['shownav'] = true;
        }

        foreach ($data as $key => $category) {
            if ($count > $limit && $limit > -1) {
                break;
            }

            if ($isactive) {
                $categories["item"][$itemnumber]["isactive"] = true;
                $isactive = false;
            }

            $obj = new stdClass();

            $obj->id = $category->id;
            $obj->name = $category->name;
            $obj->parentid = $category->parent;
            $obj->parentname = ($category->parent != 0) ? $data[$category->parent]->name : "Top";

            if ($tags['count'] == 'on') {
                $obj->hascount = true;
                $obj->coursecount = core_course_category::get($category->id)->get_courses_count(array('recursive' => true));
            }
            $obj->description = format_text($category->description);
            $obj->categoryurl = $CFG->wwwroot."/course/index.php?categoryid=".$category->id;

            $obj->viewcourses = ($tags['btnlabel'] == "" || !$tags['btnlabel']) ? "Explore" : $tags['btnlabel'];

            $catdesc = strip_tags($obj->description);
            $catdesc = preg_replace('/\n+/', '', $catdesc);
            $obj->shortdesc = strlen($catdesc) > 80 ? mb_substr($catdesc, 0, 80) . "..." : $catdesc;

            $categories["item"][$itemnumber]["categories"][] = $obj;
            if ($count % $group == 0) {
                $itemnumber++;
            }

            $count++;
        }

        return $this->generate_content($tags[_LAYOUT], $categories);
    }

    public function validate_tags($tags) {

        $retobj = new stdClass;
        $retobj->error = false;

        if (!isset($tags[_CATID]) || $tags[_CATID] == "" || $tags[_CATID] == null) {
            $tags[_CATID] = "all";
        }

        if (!isset($tags[_LAYOUT]) || $tags[_LAYOUT] == "" || $tags[_LAYOUT] == null) {
            $retobj->error = true;
            $retobj->msg = get_string("selectthelayout", "filter_edwiserpbf");
        } else if (!$tags[_LAYOUT] = $this->get_layout($tags[_LAYOUT])) {
            $retobj->error = true;
            $retobj->msg = get_string("selectedlayoutnotfound", "filter_edwiserpbf");
        }

        $retobj->tags = $tags;

        return $retobj;
    }

    public function get_layout($layout) {
        global $DB;

        if (is_numeric($layout)) {
            $condition = array("id" => $layout);
        } else {
            $condition = array("title" => $layout);
        }
        $record = $DB->get_record(EDWISERPBF_LAYOUT_TABLE, $condition, '*');

        if (!$record) {
            return false;
        }

        return json_decode($record->content);
    }

    public function generate_content($content, $context) {
        global $CFG;
        require_once($CFG->dirroot.'/filter/edwiserpbf/lib.php');
        if (epbf_is_plugin_available("block_edwiseradvancedblock")) {
            require_once($CFG->dirroot.'/blocks/edwiseradvancedblock/lib.php');
            $content->html = replace_cdn_url($content->html);
        }

        $mustache = new \core\output\mustache_engine();

        $finalcontent = $mustache->render($content->html, $context);
        $finalcontent .= $this->get_wrapped_css($content->css);
        $finalcontent .= $this->generate_js_content($content->js);

        return $this->replace_inst_with_fakeid($finalcontent);
    }
    public function replace_inst_with_fakeid($content) {
        return str_replace("[[inst]]", rand(0, 9), $content);
    }

    public function generate_js_content($js) {
        return "<script>".$js."</script>";
    }
    public function no_data_available() {
        return "<h1>".get_string("nodataavailable", "filter_edwiserpbf")."</h1>";
    }

    public function get_wrapped_css( $css) {
        global $CFG;
        if ($css == "") {
            return "";
        }
        try {
            require_once($CFG->libdir . "/classes/scss.php");

            $scss = ".section-[[inst]]{" . htmlspecialchars_decode($css) . "}";
            $scssprocessor = new \core_scss();
            $scssprocessor->append_raw_scss($scss);
            $css = $scssprocessor->to_css();

        } catch (Exception $e) {
            return "";
        }

        return "<style>". $css ."</style>";
    }
}
