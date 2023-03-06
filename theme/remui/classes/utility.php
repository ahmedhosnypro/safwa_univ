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
 * Edwiser RemUI - Utility Class
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_remui;

use Exception;
use moodle_url;
use stdClass;
use user_picture;
use html_writer;
use context_course;
use theme_remui\customizer\customizer;
/**
 * Utility class
 */
class utility {
    /*
     * Returns course categories menu array context.
     * @param $contextmenu -> $primarymenu['moremenu']
     */
    public static function get_coursecategory_menu($contextmenu) {
        global $DB;

        $categories = \core_course_category::make_categories_list();
        $mainarr = [];
        $coursecategorytext = get_config('theme_remui', 'coursecategoriestext');
        $mainarr['text'] = $coursecategorytext == "" ? get_string('coursecategories', 'theme_remui') : $coursecategorytext;
        $mainarr['key'] = 'coursecat';
        $mainarr['url'] = "#";
        $mainarr['children'] = [];
        $mainarr['classes'] = "catselector-menu";
        $mainarr['sort'] = "catselector-menu";
        $obj['text'] = 'All categories';
        $obj['url'] = new moodle_url('/course/index.php?', array(
            'categoryid' => 'all'
        ));
        $obj['title'] = 'all';
        $mainarr['children'][] = $obj;
        foreach ($categories as $categoryid => $categoryname) {
            $categorytitle = "";
            $tempcategorydata = explode('/', $categoryname);

            foreach ($tempcategorydata as $key => $data) {
                $categorytitle .= strlen($data) > 27 ? substr(trim($data, " "), 0, 27).".../" : trim($data, " ")."/";
            }

            $mainarr['haschildren'] = true;
            $obj = [];
            $category = new stdClass();
            $category->title = trim($categorytitle, "/");

            $obj['text'] = format_text(strip_tags($category->title));
            $obj['url'] = new moodle_url('/course/index.php?', array(
                'categoryid' => $categoryid
            ));
            $obj['title'] = format_text(strip_tags($category->title));
            $mainarr['children'][] = $obj;
        }
        if (isset($mainarr['haschildren']) && $mainarr['haschildren']) {
            // To add recent menu at end $contextmenu['moremenu']['nodearray'][] = $mainarr.
            // To add recent menu at end $contextmenu['mobileprimarynav'][] = $mainarr.

            // To add recent menu at start.
            array_unshift($contextmenu['moremenu']['nodearray'], $mainarr);
            array_unshift($contextmenu['mobileprimarynav'], $mainarr);
        }
        return $contextmenu;
    }

    /*
     * To add menu in header bar for recently accessed cources.
     * @param $contextmenu -> $primarymenu['moremenu']
     */
    public static function get_recent_courses_menu($contextmenu) {

        $courses = \theme_remui_coursehandler::get_recent_accessed_courses(5);

        $mainarr = [];
        $mainarr['text'] = get_string('recent', 'theme_remui');
        $mainarr['srtext'] = get_string('recentcoursesmenu', 'theme_remui');
        $mainarr['key'] = 'recentcourses';
        $mainarr['url'] = "#";
        $mainarr['children'] = [];
        foreach ($courses as $key => $course) {

            $mainarr['haschildren'] = true;

            $obj = [];

            $obj['text'] = format_text(strip_tags($course->fullname));
            $obj['url'] = new moodle_url('/course/view.php?id=', array(
                'id' => $course->courseid
            ));
            $obj['title'] = format_text(strip_tags($course->fullname));
            $mainarr['children'][] = $obj;
        }

        if (isset($mainarr['haschildren']) && $mainarr['haschildren']) {
            // To add recent menu at start.
            // Code - $contextmenu['nodearray'] = array_merge(array($mainarr), $contextmenu['nodearray']).
            // To add recent menu at end.
            // Code - $contextmenu['moremenu']['nodearray'][] = $mainarr.

            // This code is to separate menu from primary menu.
            $contextmenu['edwisermenu']['nodearray'][] = $mainarr;

            // Mobile Menu addition.
            $contextmenu['mobileprimarynav'][] = $mainarr;
        }

        return $contextmenu;
    }
    public static function get_login_menu_data($primarymenu) {
        global $PAGE;

        $loginpopup = [];

        $loginpopup['forgotpasswordurl'] = (new moodle_url('/login/forgot_password.php'))->__toString();
        $loginpopup['loginurl'] = get_login_url();
        $loginpopup['logintoken'] = \core\session\manager::get_login_token();

        $authsequence = get_enabled_auth_plugins(true); // Get all auths, in sequence.

        $idps = array();
        foreach ($authsequence as $authname) {
            $authplugin = get_auth_plugin($authname);
            $idps = array_merge($idps, $authplugin->loginpage_idp_list($PAGE->url->out(false)));
        }

        if (!empty($idps)) {
            $loginpopup['authmethods'] = [];
            foreach ($idps as $idp) {
                $authmethod = [];
                $loginpopup['hasauthmethods'] = true;
                $authmethod['url'] = $idp['url']->out();
                $authmethod['name'] = $idp['name'];

                if (!empty($idp['iconurl'])) {
                    $authmethod['iconurl'] = $idp['iconurl'];
                }
                $loginpopup['authmethods'][] = $authmethod;
            }
        }

        $primarymenu['user']['unauthenticateduser']['loginpopup'] = $loginpopup;
        unset($primarymenu['user']['unauthenticateduser']['url']);

        return $primarymenu;
    }


    /**
     * This function is used to get the sections for footer section.
     *
     * @return array which contains the column values
     */

    public static function get_sections() {
        $sectionvalue = 0;
        $allsections = [];
        $noofwidgests = get_config('theme_remui', 'footercolumn');
        for ($i = 1; $i <= $noofwidgests; $i++) {
            $allsections[] = $i;

        }
        if (count($allsections) < 4) {
            $allsections = array_pad($allsections, 4, 0);
        };
        return $allsections;
    }
    /**
     * This function is used to get the data for footer section.
     *
     * @return array Footer  data
     */
    public static function get_footer_data() {
        global $OUTPUT, $SITE;
        $customizer = customizer::instance();
        $footer = array();
        $colcount = self::get_sections();

        $colsize = 100 / count($colcount);
        $emptyfootersection = count(array_keys($colcount, 0));
        $footer['sections'] = [];
        $footer['sectionisnotempty'] = $emptyfootersection != 4;
        $sociallist = [
            'facebook' => [
                'class' => "social-facebook",
                'icon' => "icon edw-icon edw-icon-Facebook",
                'link' => $customizer->get_config('facebooksetting'),
                'title' => get_string('follometext', 'theme_remui', 'facebook')
            ],
            'twitter' => [
                'class' => "social-twitter",
                'icon' => "icon edw-icon edw-icon-Twitter",
                'link' => $customizer->get_config('twittersetting'),
                'title' => get_string('follometext', 'theme_remui', 'twitter')

            ],
            'linkedin' => [
                'class' => "social-linkedin",
                'icon' => "icon edw-icon edw-icon-Linkedin",
                'link' => $customizer->get_config('linkedinsetting'),
                'title' => get_string('follometext', 'theme_remui', 'linkedin')
            ],
            'gplus' => [
                'class' => "social-google-plus",
                'icon' => "icon edw-icon edw-icon-Gplus",
                'link' => $customizer->get_config('gplussetting'),
                'title' => get_string('follometext', 'theme_remui', 'gplus')
            ],
            'youtube' => [
                'class' => "social-youtube",
                'icon' => "icon fa fa-youtube",
                'link' => $customizer->get_config('youtubesetting'),
                'title' => get_string('follometext', 'theme_remui', 'youtube')
            ],
            'instagram' => [
                'class' => "social-instagram",
                'icon' => "icon fa fa-instagram",
                'link' => $customizer->get_config('instagramsetting'),
                'title' => get_string('follometext', 'theme_remui', 'instagram')
            ],
            'pinterest' => [
                'class' => "social-pinterest",
                'icon' => "icon fa fa-pinterest",
                'link' => $customizer->get_config('pinterestsetting'),
                'title' => get_string('follometext', 'theme_remui', 'pinterest')
            ],
            'quora' => [
                'class' => "social-quora",
                'icon' => "icon fa fa-quora",
                'link' => $customizer->get_config('quorasetting'),
                'title' => get_string('follometext', 'theme_remui', 'quore')
            ]
        ];

        foreach ($sociallist as $key => $value) {
            if (empty($value['link'])) {
                unset($sociallist[$key]);
            }
        }

        $colid = 0;
        foreach ($colcount as $i) {
            $colid++;
            $footerarr = [];
            $footerarr['width'] = $colsize;
            $footerarr['coulumnid'] = $colid;
            $footerarr['customhtml'] = $customizer->get_config( 'footercolumn'.$i.'type') == 'customhtml';
            $footerarr['menu'] = $customizer->get_config( 'footercolumn'.$i.'type') == 'menu';
            $footerarr['title'] = $customizer->get_config('footercolumn'.$i.'title');
            $footerarr['classes'] = ($i) == 0 ? "empty" : '';

            $footerarr['hascontenthtml'] = array(
                'title' => $customizer->get_config('footercolumn'.$i.'title'),
                "content" => $customizer->get_config('footercolumn'.$i.'customhtml'),
            );
            $footerarr['hassocial'] = $customizer->get_config('socialmediaiconcol' . $i) && $footerarr['customhtml'];
            $footerarr['socialiconvisibility'] = $footerarr['hassocial'];
            if (!$selectedsocial = json_decode($customizer->get_config('footercolumn'.$i.'social'), true)) {
                $selectedsocial = [];
            }

            $tempsocial = $sociallist;

            foreach ($tempsocial as $key => $value) {
                if (!in_array($key, $selectedsocial)) {
                    unset($tempsocial[$key]);
                }
            }
            $footerarr['hassocial'] = array(
                'social' => array_values($tempsocial)
            );

            $footerarr['menu'] = $customizer->get_config('footercolumn'.$i.'menu');

            $footer['sections'][] = $footerarr;
        }
        $footer['bottomtext'] = format_text(\theme_remui\toolbox::get_setting('footerbottomtext'));
        $footer['bottomlink'] = strip_tags(format_text(\theme_remui\toolbox::get_setting('footerbottomlink')));

        if (\theme_remui\toolbox::get_setting('poweredbyedwiser')) {
            $footer['poweredby']  = true;
            $footer['isadmin'] = is_siteadmin();
        }

        // Secondary footer data.
        // Show footer logo.
        $footer['footershowlogo'] = $customizer->get_config('footershowlogo');
        $footer['useheaderlogo'] = $customizer->get_config('useheaderlogo');
        $secondaryfooterlogo = '';
        if (!$footer['useheaderlogo']) {
            $secondaryfooterlogo = \theme_remui\toolbox::setting_file_url('secondaryfooterlogo', 'secondaryfooterlogo');
            if (empty($secondaryfooterlogo)) {
                $secondaryfooterlogo = \theme_remui\toolbox::image_url('logo', 'theme_remui');
            }
        }
        $footer['secondaryfooterlogo'] = $secondaryfooterlogo;
        // Show social icons in secondary footer.
        $footer['footersecondarysocial'] = get_config('theme_remui', 'footersecondarysocial') != false;

        // Show terms and conditions.
        $footer['footertermsandconditionsshow'] = $customizer->get_config('footertermsandconditionsshow');
        $footer['footertermsandconditions'] = $customizer->get_config('footertermsandconditions');
        $footer['termsandconditionewtab'] = $customizer->get_config('termsandconditionewtab');

        // Show privacy policy.
        $footer['footerprivacypolicyshow'] = $customizer->get_config('footerprivacypolicyshow');
        $footer['footerprivacypolicy'] = $customizer->get_config('footerprivacypolicy');
        $footer['privacypolicynewtab'] = $customizer->get_config('privacypolicynewtab');

        // Show copyrights condition.
        $cfgfootercopyrights = $customizer->get_config('footercopyrights');
        $copyrights = $cfgfootercopyrights ? $cfgfootercopyrights : get_string('footercopyrights', 'theme_remui');

        $copyrights = str_replace('[site]', $SITE->fullname, $copyrights);
        $copyrights = str_replace('[year]', date("Y"), $copyrights);

        $footer['footercopyrights'] = [
            'footercopyrightsshow' => $customizer->get_config('footercopyrightsshow'),
            'content' => strip_tags(format_text($copyrights)),
            'attributes' => [
                'data-site="' . $SITE->fullname . '"'
            ]
        ];
        return $footer;
    }
    /*
     * To add icons to profile menu dropdown in header.
     * @param $primarymenu
     * @return $primarymenu
     */
    public static function add_profile_dropdown_icons($primarymenu) {
        $customicons = array(
            "profile,moodle" => "edw-icon-User",
            "grades,grades" => "edw-icon-Grade",
            "calendar,core_calendar" => "edw-icon-Calendar",
            "privatefiles,moodle" => "edw-icon-Private-Files",
            "reports,core_reportbuilder" => "edw-icon-Report",
            "preferences,moodle" => "edw-icon-Preferences",
            "switchroleto,moodle" => "edw-icon-Grade",
            "language" => "edw-icon-Language",
            "logout,moodle" => "edw-icon-Logout",
        );
        foreach ($primarymenu['user']['items'] as $key => $user) {
            $item = $primarymenu['user']['items'][$key];
            if ($item->itemtype == "submenu-link" && !isset($item->titleidentifier) ) {
                $item->titleidentifier = 'language';
            }
            if (($item->itemtype == "link" || $item->itemtype == "submenu-link") && isset($item->titleidentifier) && isset($customicons[$item->titleidentifier])) {
                $item->profileicon = $customicons[$item->titleidentifier];
            }

            $primarymenu['user']['items'][$key] = $item;
        }
        return $primarymenu;
    }
    /**
     * Get items which have been graded.
     *
     * @return string grades
     * @throws \coding_exception
     */
    public static function graded() {
        $grades = self::events_graded();
        return $grades;
    }
    /**
     * Get everything graded from a specific date to the current date.
     *
     * @return mixed Event data
     */
    public static function events_graded() {
        global $DB, $USER;

        $params = [];
        $coursesql = '';
        $courses = enrol_get_my_courses();
        $courseids = array_keys($courses);
        $courseids[] = SITEID;
        list($coursesql, $params) = $DB->get_in_or_equal($courseids);
        $coursesql = 'AND gi.courseid '.$coursesql;

        $onemonthago = time() - (DAYSECS * 31);
        $showfrom = $onemonthago;

        $sql = "SELECT gg.*, gi.itemmodule, gi.iteminstance, gi.courseid, gi.itemtype
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi
                    ON gg.itemid = gi.id $coursesql
                 WHERE gg.userid = ?
                   AND (gg.timemodified > ?
                    OR gg.timecreated > ?)
                   AND (gg.finalgrade IS NOT NULL
                    OR gg.rawgrade IS NOT NULL
                    OR gg.feedback IS NOT NULL)
                   AND gi.itemtype = 'mod'
                 ORDER BY gg.timemodified DESC";

        $params = array_merge($params, [$USER->id, $showfrom, $showfrom]);
        $grades = $DB->get_records_sql($sql, $params, 0, 5);

        $eventdata = array();
        foreach ($grades as $grade) {
            $eventdata[] = $grade;
        }

        return $eventdata;
    }
    /**
     * Check user is admin or manager
     * @param  object  $userobject User object
     * @return boolean             True if admin or manager
     */
    public static function check_user_admin_cap($userobject = null) {
        global $USER;
        if (!$userobject) {
            $userobject = $USER;
        }
        if (is_siteadmin()) {
            return true;
        }
        $context = \context_system::instance();
        $roles = get_user_roles($context, $userobject->id, false);
        foreach ($roles as $role) {
            if ($role->roleid == 1 && $role->shortname == 'manager') {
                return true;
            }
        }
        return false;
    }
    /**
     * Generates an array of sections and an array of activities for the given course.
     *
     * This method uses the cache to improve performance and avoid the get_fast_modinfo call
     *
     * @param stdClass $course
     * @return array Array($sections, $activities)
     */
    public static function get_focus_mode_sections(stdClass $course, $coursemoduleid = false) {
        global $CFG, $USER;
        require_once($CFG->dirroot.'/course/lib.php');

        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        // For course formats using 'numsections' trim the sections list.
        $courseformatoptions = course_get_format($course)->get_format_options();
        if (isset($courseformatoptions['numsections'])) {
            $sections = array_slice($sections, 0, $courseformatoptions['numsections'] + 1, true);
        }

        $allsections = array();
        $active = '';
        $previous = '';
        $current = '';
        $next = '';

        foreach ($sections as $sectiondata) {
            $section = new stdClass;
            $section->sectionid = 'Section-'.$sectiondata->id;
            $section->section = $sectiondata->section;
            $section->name = get_section_name($course, $sectiondata->section);
            $section->hasactivites = false;
            $section->activities = [];
            $section->active = '';
            if (!array_key_exists($sectiondata->section, $modinfo->sections)) {
                continue;
            }

            foreach ($modinfo->sections[$sectiondata->section] as $cmid) {
                $cm = $modinfo->cms[$cmid];

                // Only add activities the user can access, aren't in stealth mode and have a url (eg. mod_label does not).
                if (!$cm->uservisible || $cm->is_stealth() || empty($cm->url)) {
                    continue;
                }
                $activity = new stdClass;
                $activity->id = $cm->id;
                $activity->course = $course->id;
                $activity->section = $sectiondata->section;
                $activity->name = strip_tags(format_text($cm->name));
                $activity->icon = $cm->get_icon_url();
                $activity->hidden = (!$cm->visible);
                $activity->modname = $cm->modname;
                $activity->onclick = $cm->onclick;
                $activity->active = '';
                $url = $cm->url;
                if (!$url) {
                    $activity->url = null;
                    $activity->display = false;
                } else {
                    $activity->url = $url->out();
                    $activity->display = $cm->is_visible_on_course_page() ? true : false;
                }
                if ($activity->display) {
                    if ($coursemoduleid != false) {
                        if ($active == '') {
                            $previous = $current;
                            $current = $activity->url;
                        }
                        if ($active != '' && $next == '') {
                            $next = $activity->url;
                        }
                        if ($cm->id == $coursemoduleid) {
                            $activity->active = 'active';
                            $active = $activity->name;
                            $section->active = 'show';
                        }
                    }
                    $section->hasactivites = true;
                    $section->activities[] = $activity;
                }
            }
            $allsections[] = $section;
        }
        if (count($allsections) != 0 && $active == '') {
            $allsections[0]->active = 'show';
            $allsections[count($allsections) - 1]->last = true;
        }

        // Make forceview true for previous and next link.
        if ($previous != '') {
            $previous .= '&forceview=1';
        }
        if ($next != '') {
            $next .= '&forceview=1';
        }

        return [$allsections, $active, $previous, $next];
    }

    /**
     * Get user picture from user object
     * @param  object  $userobject User object
     * @param  integer $imgsize    Size of image in pixel
     * @return String              User picture link
     */
    public static function get_user_picture($userobject = null, $imgsize = 100) {
        global $USER, $PAGE;
        if (!$userobject) {
            $userobject = $USER;
        }

        $userimg = new user_picture($userobject);
        $userimg->size = $imgsize;
        return  $userimg->get_url($PAGE);
    }
    /**
     * Add extra body classes.
     * Do some data manipulation then add your classes.
     */
    public static function get_main_bg_class() {
        global $PAGE;
        $haystack = explode(" ", $PAGE->bodyclasses);

        if (in_array('ignore-main-bg', $haystack)) {
            return;
        }

        $bodyclasses = array(
            'pagelayout-mydashboard',
            'pagelayout-mycourses',
            'pagelayout-frontpage',
            'path-calendar',
            'pagelayout-course'
        );

        foreach ($bodyclasses as $key => $needle) {
            if (in_array($needle, $haystack)) {
                return;
            }
        }

        return "main-area-bg";
    }

       /**
        * Get card content for courses
        * @param  object $wdmdata Data to create cards
        * @param  string $date    Date filter
        * @return array           Courses cards
        */
    public static function get_course_cards_content($wdmdata, $date = 'all') {
        global $CFG, $OUTPUT;
        $courseperpage = \theme_remui\toolbox::get_setting('courseperpage');
        $categorysort = $wdmdata->sort;
        $search       = $wdmdata->search;
        $category     = $wdmdata->category;
        $courses      = isset($wdmdata->courses) ? $wdmdata->courses : [];
        $mycourses    = $wdmdata->tab;
        $page         = ($mycourses) ? $wdmdata->page->mycourses : $wdmdata->page->courses;
        $startfrom    = $courseperpage * $page;
        $limitto      = $courseperpage;
        $filtermodified = isset($wdmdata->isFilterModified) ? $wdmdata->isFilterModified : true;
        $allowfull = true;
        // Resultant Array.
        $result = array();

        if ($page == -1) {
            $startfrom = 0;
            $limitto = 0;
        }

        // This condition is for coursecategory page only, that is why on frontpage it is not
        // necessary so returning limiteddata.
        if (isset($wdmdata->limiteddata)) {
            $allowfull = false;
        }

        // Pagination Context creation.
        if ($wdmdata->pagination) {
            // First paremeter true means get_courses function will return count of the result and if false, returns actual data.
            list($totalcourses, $courses)  = self::get_courses(
                2,
                $search,
                $category,
                $startfrom,
                $limitto,
                $mycourses,
                $categorysort,
                $courses,
                $filtermodified
            );

            $pagingbar  = new \paging_bar($totalcourses, $page, $courseperpage, 'javascript:void(0);', 'page');
            $result['totalcoursescount'] = $totalcourses;
            $result['pagination'] = $OUTPUT->render($pagingbar);
        } else {
            // Fetch the courses.
            $courses = self::get_courses(
                false,
                $search,
                $category,
                $startfrom,
                $limitto,
                $mycourses,
                $categorysort,
                $courses,
                $filtermodified
            );
        }

        // Courses Data.
        $coursecontext = array();
        foreach ($courses as $key => $course) {
            if ($date != 'all') {
                // Get the current time value.
                $time = new \DateTime("now", \core_date::get_user_timezone_object());
                $time->add(new \DateInterval("P1D"));

                $timestamp = $time->getTimestamp();

                // Check if inprogress and not passed the course end date.
                if ($date == 'inprogress' && $timestamp < $course['epochenddate']) {
                    continue;
                }

                // Check if future and not passed course start date.
                if ($date == 'future' && $timestamp > $course['epochstartdate']) {
                    continue;
                }

                // Check if past and not passed end date.
                if ($date == 'past' && $timestamp < $course['epochenddate']) {
                    continue;
                }
            }

            // Get Ratings and Review Context.
            $rnrshortdesign = '';
            if (is_plugin_available("block_edwiserratingreview")) {
                $rnr = new \block_edwiserratingreview\ReviewManager();
                $rnrshortdesign = $rnr->get_course_cardlayout_ratingdata($course['courseid']);
            }
            $coursedata = array();
            $coursedata['id'] = $course['courseid'];
            $coursedata['grader']    = $course['grader'];
            $coursedata['shortname'] = strip_tags(format_text($course['shortname']));
            $coursedata['courseurl'] = $course['courseurl'];
            $coursedata['coursename'] = strip_tags(format_text($course['coursename']));
            $coursedata['enrollusers'] = $course['enrollusers'];
            $coursedata['editcourse']  = $course['editcourse'];
            $coursedata['activity']    = $course['activity'];
            $coursedata['categoryname'] = format_text($course['categoryname']);
            $coursedata['ernrshortdesign'] = $rnrshortdesign;
            if ($course['visible']) {
                $coursedata['visible'] = $course['visible'];
            }

            // This condition to handle the string url or moodle_url object problem.
            if (is_object($course['courseimage'])) {
                $coursedata['courseimage'] = $course['courseimage']->__toString();
            } else {
                $coursedata['courseimage'] = $course['courseimage'];
            }
            $coursedata['coursesummary'] = $course['coursesummary'];
            if (isset($course['coursestartdate'])) {
                $coursedata['startdate']['day'] = substr($course['coursestartdate'], 0, 2);
                $coursedata['startdate']['month'] = substr($course['coursestartdate'], 3, 3);
                $coursedata['startdate']['year'] = substr($course['coursestartdate'], 8, 4);
            }
            // Course card - Footer context is different for mycourses and all courses tab.
            if ($mycourses) {
                // Context creation for mycourses.
                $coursedata['mycourses'] = true;
                if (isset($course['coursecompleted'])) {
                    $coursedata["coursecompleted"] = $course['coursecompleted'];
                }
                if (isset($course['courseinprogress'])) {
                    $coursedata["courseinprogress"] = $course['courseinprogress'];
                    $coursedata["percentage"] = $course['percentage'];
                }
                if (isset($course['coursetostart'])) {
                    $coursedata["coursetostart"] = $course['coursetostart'];
                }
            } else {
                // Context creation for all courses.
                if (isset($course['usercanmanage']) && $allowfull) {
                    $coursedata["usercanmanage"] = $course['usercanmanage'];
                }

                if (isset($course['enrollmenticons']) && $allowfull) {
                    $coursedata["enrollmenticons"] = $course['enrollmenticons'];
                }
            }

            if (isset($course['instructors']) && $allowfull) {
                $instructors = array();
                foreach ($course['instructors'] as $key2 => $instructor) {
                    $instructordetail['name'] = $instructor['name'];
                    $instructordetail['url'] = $instructor['url'];
                    $instructordetail['picture'] = $instructor['picture']->__toString();
                    $instructors[] = $instructordetail;
                }
                $coursedata['instructors'] = $instructors;
            }

            $coursedata['instructorcount'] = $course['instructorcount'];
            $coursedata['lessoncount'] = $course['lessoncount'];
            // $pagelayout = get_config('theme_remui', 'categorypagelayout');

            $pagelayout = 0;
            if ($pagelayout !== "0") {

                switch ($pagelayout) {
                    case '1':
                        if ($allowfull) {
                            $coursedata['widthclasses'] = 'card-main col-lg-4 col-sm-12 col-md-6';
                        } else {
                            $coursedata['widthclasses'] = 'col-12 h-p100 ';
                        }
                        break;

                    // Commented this code, add here new layout design condition.
                    // case '2':
                    // $templatecontext['layout2'] = true;
                    // break;
                    default:
                        if ($allowfull) {
                            $coursedata['widthclasses'] = 'col-lg-3 col-sm-12 col-md-6';
                        } else {
                            $coursedata['widthclasses'] = 'col-12 h-p100 ';
                        }
                        break;
                }

            } else {
                if ($allowfull) {
                    $coursedata['widthclasses'] = 'col-lg-3 col-sm-12 col-md-6';
                } else {
                    $coursedata['widthclasses'] = 'col-12 h-p100 ';
                }
            }

            $coursedata['animation'] = \theme_remui\toolbox::get_setting('courseanimation');
            if (!\theme_remui\toolbox::get_setting('enablenewcoursecards')) {
                $coursedata['old_card'] = true;
            }
            $coursecontext[] = $coursedata;
        }
        $result['courses'] = $coursecontext;
        $result['view'] = get_user_preferences('course_view_state');

        if (\theme_remui\toolbox::get_setting('enablenewcoursecards')) {
            $result['latest_card'] = true;
        }

        return $result;
    }

        /**
         * Return user's courses or all the courses
         *
         * Usually called to get usr's courese, or it could also be called to get all course.
         * This function will also be called whern search course is used.
         *
         * @param  bool   $totalcount     If true then returns total course count
         * @param  string $search         course name to be search
         * @param  int    $category       ids to be search of courses.
         * @param  int    $limitfrom      course to be returned from these number onwards, like from course 5 .
         * @param  int    $limitto        till this number course to be returned,
         *                                like from course 10, then 5 course will be returned from 5 to 10.
         * @param  array  $mycourses      Courses to return user's course which he/she enrolled into.
         * @param  bool   $categorysort   if true the categories are sorted
         * @param  array  $courses        pass courses if would like to load more data for those courses
         * @param  bool   $filtermodified If true then cache will be cleared and regenerated when filter is modified
         * @return array                  Course array
         */
    public static function get_courses(
        $totalcount = false,
        $search = null,
        $category = null,
        $limitfrom = 0,
        $limitto = 0,
        $mycourses = null,
        $categorysort = null,
        $courses = [],
        $filtermodified = true
    ) {
        $coursehandler = new \theme_remui_coursehandler();
        return $coursehandler->get_courses(
            $totalcount,
            $search,
            $category,
            $limitfrom,
            $limitto,
            $mycourses,
            $categorysort,
            $courses,
            $filtermodified
        );

    }
    /**
     * Return HTML for site announcement.
     *
     * @return string Site announcement HTML
     */
    public static function render_site_announcement() {

        $announce = '';
        $classes = '';
        $html = '';

        $type = \theme_remui\toolbox::get_setting('announcementtype');
        $message = \theme_remui\toolbox::get_setting('announcementtext');

        if (\theme_remui\toolbox::get_setting('enabledismissannouncement')) {
            $html .= '<button id="dismiss_announcement" type="button" class="close" data-dismiss="alert" aria-label="Close">';
            $html .= '<span aria-hidden="true" class="edw-icon edw-icon-Cancel large"></span>';
            $html .= '</button>';
            $classes = 'alert-dismissible';
        }

        $classes .= ' site-announcement mb-0';
        $announce .= "<div class='alert alert-{$type} {$classes} show-more' role='alert'>";
        $announce .= "<span class='ellipsis ellipsis-1'>{$message}</span>";
        $announce .= $html;
        $announce .= "</div>";

        return $announce;
    }
    /**
     * Function to get the remote data from url
     *
     * @param string $url
     * @return array
     */
    public static function url_get_contents ($url) {
        global $CFG;
        $urlgetcontentsdata = array();

        if (class_exists('curl')) {
            $curl = new \curl();
            $curl->setopt(array(
                'CURLOPT_SSL_VERIFYPEER' => false,
                'CURLOPT_FRESH_CONNECT' => true,
                'CURLOPT_RETURNTRANSFER' => 1,
                'CURLOPT_TIMEOUT' => 3,
                'CURLOPT_USERAGENT' => $_SERVER['HTTP_USER_AGENT'] . ' - ' . $CFG->wwwroot,
            ));
            $urlgetcontentsdata = $curl->get($url);

            if ($curl->get_errno() !== 0) {
                $urlgetcontentsdata = [];
            }
        } else if (function_exists('curl_exec')) {
            $conn = \curl_init($url);
            curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($conn, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($conn, CURLOPT_TIMEOUT, 3);
            curl_setopt($curl, CURLOPT_USERAGENT, \core_useragent::get_user_agent_string());
            curl_setopt($curl, CURLOPT_REFERER, $CFG->wwwroot);
            if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($conn, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
            $urlgetcontentsdata = (curl_exec($conn));
            if (curl_errno($conn)) {
                $errormsg = curl_error($conn);
                $urlgetcontentsdata = array();
            }
            curl_close($conn);
        } else if (function_exists('file_get_contents')) {
            $urlgetcontentsdata = file_get_contents($url);
        } else if (function_exists('fopen') && function_exists('stream_get_contents')) {
            $handle = fopen($url, "r");
            $urlgetcontentsdata = stream_get_contents($handle);
        } else {
            $urlgetcontentsdata = array();
        }
        return $urlgetcontentsdata;
    }
    /**
     * Throw error with string and error code
     * @param string $error     Eigther error string id or direct string
     * @param int    $code      Numeric error code
     * @param bool   $getstring If true then $error will be treated as string id
     */
    public static function throw_error($error, $code = '', $getstring = true) {
        if ($getstring) {
            $error = get_string($error, 'theme_remui');
        }
        throw new Exception(json_encode(['error' => true, 'msg' => $error . " : " . $code]), $code);
    }

    public static function get_inproduct_notification() {
        global $OUTPUT;
        // Init product notification configuration
        $notification = get_user_preferences('edwiser_inproduct_notification');

        if ($notification == null || $notification == "false" || $notification == false) {
            return false;
        }

        $notification = json_decode($notification);

        return [
            "msg" => $notification->msg,
            "imgclass" => $notification->img,
            "edwiserlogo" => $OUTPUT->image_url('edwiser-logo', 'theme_remui')->__toString(),
            "mainimg" => $OUTPUT->image_url($notification->img, 'theme_remui')->__toString()
        ];
    }

        /**
         * Show license or update notice
         *
         * @return HTML for license notice.
         */
    public static function show_license_notice() {
        // Get license data from license controller.
        $lcontroller = new \theme_remui\controller\LicenseController();
        $getlidatafromdb = $lcontroller->get_data_from_db();
        if (isloggedin() && !isguestuser()) {
            $content = '';

            $classes = ['alert', 'text-center', 'license-notice', ' alert-dismissible', 'site-announcement' , 'mb-0'];
            if ('available' != $getlidatafromdb) {
                $classes[] = 'alert-danger';
                if (is_siteadmin()) {
                    $content .= '<strong>'.get_string('licensenotactiveadmin', 'theme_remui').'</strong>';
                } else {
                    $content .= get_string('licensenotactive', 'theme_remui');
                }
            } else if ('available' == $getlidatafromdb) {
                $licensekeyactivate = \theme_remui\toolbox::get_setting(EDD_LICENSE_ACTION);

                if (isset($licensekeyactivate) && !empty($licensekeyactivate)) {
                    $classes[] = 'alert-success';
                    $content .= get_string('licensekeyactivated', 'theme_remui');
                } else {
                    // Show update notice if license is active and update is available.
                    $available  = \theme_remui\controller\RemUIController::check_remui_update();
                    if (is_siteadmin() && $available == 'available') {
                        $classes[] = 'update-nag bg-info moodle-has-zindex';
                        $url = new moodle_url(
                            '/admin/settings.php',
                            array(
                                'section' => 'themesettingremui',
                                'activetab' => 'informationcenter'
                            )
                        );
                        $content .= get_string('newupdatemessage', 'theme_remui', $url->out());
                    }
                }
            }
            if ($content != '') {
                $content .= '<button type="button" id="dismiss_announcement" class="close" data-dismiss="alert" aria-hidden="true"><span class="edw-icon edw-icon-Cancel  large"></span></button>';
                return html_writer::tag('div', $content, array('class' => implode(' ', $classes)));
            }
        }
        return '';
    }
    public static function remove_announcement_preferences() {
        global $DB;
        // Delete from DB.
        $DB->delete_records('user_preferences', array('name' => 'remui_dismised_announcement'));
    }

    /**
     * Returns left navigation footer menus details.
     *
     * @return Array Menu details.
     */
    public static function edw_quick_menu() {
        global $CFG, $DB, $COURSE, $USER, $PAGE;

        // Course Create link.
        $createcourselink = "#";
        $categories = $DB->get_records('course_categories', null, '', 'id');
        if (!empty($categories)) {
            $firstcategory = reset($categories);
            $createcourselink = $CFG->wwwroot. '/course/edit.php?category='.$firstcategory->id;
        }

        $menudata = array (
            [
                'url' => $CFG->wwwroot.'/course/index.php',
                'iconclass' => 'edw-icon edw-icon-Glossary',
                'title' => get_string('createarchivepage', 'theme_remui')
            ]
        );
        // Return all menus for site administrator.
        if (is_siteadmin($USER)) {

            $menus = array (
                [
                    'url' => "{$CFG->wwwroot}/{$CFG->admin}/user.php",
                    'iconclass' => 'edw-icon edw-icon-Group-user',
                    'title' => get_string('userlist')
                ],
                [
                    'url' => $createcourselink,
                    'iconclass' => 'edw-icon edw-icon-File_Activity',
                    'title' => get_string('createanewcourse', 'theme_remui')
                ],
                [
                    'url' => $CFG->wwwroot . "/theme/remui/customizer.php?url=" . urlencode($PAGE->url->out()),
                    'iconclass' => 'edw-icon edw-icon-brush customizer-editing-icon',
                    'title' => get_string('customizer', 'theme_remui')
                ]
            );
            if (get_config('theme_remui', 'showimportericon')) {
                $menus[] = [
                    'url' => "{$CFG->wwwroot}/{$CFG->admin}/settings.php?section=themesettingremui&activetab=edwisersiteimporter",
                    'iconclass' => 'edw-icon edw-icon-Download',
                    'title' => get_string('importer', 'theme_remui')
                ];
            }
            $menus[] = [
                'url' => "{$CFG->wwwroot}/{$CFG->admin}/settings.php?section=themesettingremui",
                'iconclass' => 'edw-icon edw-icon-Preferences',
                'title' => get_string('remuisettings', 'theme_remui')
            ];
            $menudata = array_merge($menudata, $menus);
            $temp = array_splice($menudata, 1, 1);
            array_splice($menudata, 0, 0, $temp);
            return $menudata;
        }

        // Return menus for course creator.
        $coursecontext = context_course::instance($COURSE->id);
        if (has_capability('moodle/course:create', $coursecontext)) {
            $menu = [
                'url' => $createcourselink,
                'iconclass' => 'fa-file',
                'title' => get_string('createanewcourse', 'theme_remui')
            ];
            array_push($menudata, $menu);
            $temp = array_splice($menudata, 1, 1);
            array_splice($menudata, 0, 0, $temp);
            return $menudata;
        }
        return $menudata;
    }
}
