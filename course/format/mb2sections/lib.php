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
 * This file contains main class for Mb2sections course format.
 *
 * @since     Moodle 2.0
 * @package   format_mb2sections
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/course/format/lib.php');

use core\output\inplace_editable;

/**
 * Main class for the Mb2sections course format.
 *
 * @package    format_mb2sections
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//class format_mb2sections extends format_base {
class format_mb2sections extends core_courseformat\base {

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    public function uses_course_index() {
        return true;
    }

    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #").
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                ['context' => context_course::instance($this->courseid)]);
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the mb2sections course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of format_base::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_mb2sections');
        } else {
            // Use format_base::get_default_section_name implementation which
            // will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * Generate the title for this section page.
     *
     * @return string the page title
     */
    public function page_title(): string {
        return get_string('topicoutline');
    }

    /**
     * The URL to use for the specified course (with section).
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = []) {
        global $CFG;
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', ['id' => $course->id]);

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            if ($sr !== null) {
                if ($sr) {
                    $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
                    $sectionno = $sr;
                } else {
                    $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
                }
            } else {
                $usercoursedisplay = $course->coursedisplay;
            }
            if ($sectionno != 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $url->param('section', $sectionno);
            } else {
                if (empty($CFG->linkcoursesections) && !empty($options['navigation'])) {
                    return null;
                }
                $url->set_anchor('section-'.$sectionno);
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }


    /**
     * Returns the format's settings and gets them if they do not exist.
     * @return array The settings as an array.
     */
    public function get_settings() {
        if (empty($this->settings) == true) {
            $this->settings = $this->get_format_options();
            $this->settings['mb2sectionscontent'] = $this->get_content();
            $this->settings['mb2sectionsimage'] = $this->get_fileitemid();
            $this->settings['mb2sectionsvideo'] = $this->get_videofileitemid();
        }
        return $this->settings;
    }


    public function supports_components() {
        return true;
    }


    /**
     * Loads all of the course sections into the navigation.
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     * @return void
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                    $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode.
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = [];
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return ['sectiontitles' => $titles, 'action' => 'move'];
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Definitions of the additional options that this course format uses for course.
     *
     * Mb2sections format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ),
                'coursedisplay' => array(
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT,
                ),
                'enrollayout' => array(
                    'default' => 'theme',
                    'type' => PARAM_TEXT
                ),
                'courseslogan' => array(
                    'default' => '',
                    'type' => PARAM_TEXT
                ),
                'introvideourl' => array(
                    'default' => '',
                    'type' => PARAM_TEXT
                ),
                'showmorebtn' => array(
                    'default' => 'theme',
                    'type' => PARAM_TEXT
                ),
                'skills' => array(
                    'default' => '',
                    'type' => PARAM_TEXT
                ),
                'elrollsections' => array(
                    'default' => 'theme',
                    'type' => PARAM_TEXT
                ),
                // 'freeprice' => array(
                //     'default' => 1,
                //     'type' => PARAM_INT
                // ),
                // 'instructors' => array(
                //     'default' => 0,
                //     'type' => PARAM_INT
                // ),
                'shareicons' => array(
                    'default' => 'theme',
                    'type' => PARAM_TEXT
                )
            );
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseformatoptionsedit = array(
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => get_string('hiddensectionscollapsed'),
                            1 => get_string('hiddensectionsinvisible')
                        ),
                    ),
                ),
                'coursedisplay' => array(
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            COURSE_DISPLAY_SINGLEPAGE => get_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => get_string('coursedisplay_multi'),
                        ),
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                ),
                'courseslogan' => array(
                    'label' => get_string('courseslogan', 'format_mb2sections'),
                    'element_type' => 'textarea'
                ),
                'enrollayout' => array(
                    'label' => get_string('enrollayoutpage', 'format_mb2sections'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            'theme' => get_string( 'usetheme', 'format_mb2sections' ),
                            '1' => get_string( 'enrollayout', 'format_mb2sections', array( 'layout' => 1 ) ),
                            '2' => get_string( 'enrollayout', 'format_mb2sections', array( 'layout' => 2 ) ),
                        ),
                    )
                ),
                'introvideourl' => array(
                    'label' => get_string('introvideourl', 'format_mb2sections'),
                    'element_type' => 'text',
                    'help' => 'introvideourl',
                    'help_component' => 'format_mb2sections',
                ),
                'showmorebtn' => array(
                    'label' => get_string('showmorebtn', 'format_mb2sections'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            'theme' => get_string( 'usetheme', 'format_mb2sections' ),
                            '1' => get_string( 'yes', 'format_mb2sections' ),
                            '0' => get_string( 'no', 'format_mb2sections' ),
                        )
                    )
                ),
                'skills' => array(
                    'label' => get_string('skills', 'format_mb2sections'),
                    'element_type' => 'textarea',
                    'help' => 'skills',
                    'help_component' => 'format_mb2sections'
                ),
                'elrollsections' => array(
                    'label' => get_string('elrollsections', 'format_mb2sections'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            'theme' => get_string( 'usetheme', 'format_mb2sections' ),
                            '1' => get_string( 'yes', 'format_mb2sections' ),
                            '0' => get_string( 'no', 'format_mb2sections' ),
                        )
                    )
                ),
                // 'instructors' => array(
                //     'label' => get_string('instructors', 'format_mb2sections'),
                //     'element_type' => 'checkbox'
                // ),
                // 'freeprice' => array(
                //     'label' => get_string('freeprice', 'format_mb2sections'),
                //     'element_type' => 'checkbox'
                // ),
                'shareicons' => array(
                    'label' => get_string('shareicons', 'format_mb2sections'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            'theme' => get_string( 'usetheme', 'format_mb2sections' ),
                            '1' => get_string( 'yes', 'format_mb2sections' ),
                            '0' => get_string( 'no', 'format_mb2sections' ),
                        )
                    )
                )

            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }






    /**
     * Adds format options elements to the course/section edit form.
     * This function is called from course_edit_form::definition_after_data().
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE, $USER;

        $elements = parent::create_edit_form_elements($mform, $forsection);
        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" element to the create course form - it will force new course to be prepopulated
            // with empty sections.
            // The "Number of sections" option is no longer available when editing course, instead teachers should
            // delete and add sections when needed.
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        // Define require variables
        $elementsnew = [];
        $data = new stdClass;
        $fs = get_file_storage();
        $coursecontext = $this->courseid ? context_course::instance( $this->courseid ) : null;
        $usercontext = context_user::instance( $USER->id );

        foreach ( $elements as $key => $element )
        {
            if ( $element->getName() === 'enrollayout' )
            {
                $header = $mform->addElement('header', 'enrolpage', get_string( 'enrolmentpage', 'format_mb2sections' ) );
                $elementsnew[] = $header;
            }

            if ( $element->getName() === 'introvideourl' )
            {
                // Course hero image
                //$fileitemid = $this->get_fileitemid();
                //$fs->delete_area_files( $usercontext->id, 'user', 'draft', $fileitemid );
                file_prepare_standard_filemanager( $data, 'mb2sectionsimage', $this->get_filemanager_options(), $coursecontext, 'format_mb2sections', 'mb2sectionsimage', 0 );
                $filemanager = $mform->addElement( 'filemanager', 'mb2sectionsimage_filemanager', get_string('mb2sectionsimage', 'format_mb2sections'), null,
                $this->get_filemanager_options() );
                $filemanager->setValue( $data->mb2sectionsimage_filemanager );

                // Course video
                //$videofileitemid = $this->get_videofileitemid();
                //$fs->delete_area_files( $usercontext->id, 'user', 'draft', $fileitemid );
                file_prepare_standard_filemanager( $data, 'mb2sectionsvideo', $this->get_video_filemanager_options(), $coursecontext, 'format_mb2sections', 'mb2sectionsvideo', 0 );
                $filemanagervideo = $mform->addElement( 'filemanager', 'mb2sectionsvideo_filemanager', get_string('introvideo', 'format_mb2sections'), null,
                $this->get_video_filemanager_options() );
                $filemanagervideo->setValue( $data->mb2sectionsvideo_filemanager );

                $elementsnew[] = $filemanager;
                $elementsnew[] = $filemanagervideo;
            }

            if ( $element->getName() === 'showmorebtn' )
            {
                $data->mb2sectionscontentformat = FORMAT_HTML;
                $data->mb2sectionscontent = $this->get_content();
                file_prepare_standard_editor( $data, 'mb2sectionscontent', $this->get_editor_options(), $coursecontext, 'format_mb2sections', 'mb2sectionscontent', 0 );
                $editor = $mform->addElement('editor', 'mb2sectionscontent_editor', get_string('mb2sectionscontent', 'format_mb2sections'), null, $this->get_editor_options());
                $editor->setValue( $data->mb2sectionscontent_editor );

                $elementsnew[] = $editor;
            }

            unset( $elements[$key] );
            $elementsnew[] = $element;
        }

        return $elementsnew;
    }





    /**
     *
     * Method to get editor options
     *
     */
    public function get_editor_options()
    {
        $coursecontext = $this->courseid ? context_course::instance($this->courseid) : null;
        return array('subdirs' => false, 'maxfiles' => -1, 'context' => $coursecontext);
    }







    /**
     *
     * Method to get file manager options
     *
     */
    public function get_filemanager_options()
    {
        $coursecontext = $this->courseid ? context_course::instance($this->courseid) : null;
        return array('subdirs' => false, 'maxfiles' => 1, 'accepted_types' => '.jpg,.png,.gif', 'context' => $coursecontext );

    }




    /**
     *
     * Method to get file manager options
     *
     */
    public function get_video_filemanager_options()
    {
        $coursecontext = $this->courseid ? context_course::instance($this->courseid) : null;
        return array('subdirs' => false, 'maxfiles' => 1, 'accepted_types' => '.webm,.mpg,.mp2,.mpeg,.mpe,.mpv,.mp4,.m4p,.m4v,.avi,.mov', 'context' => $coursecontext );

    }







    /**
     *
     * Method to get content for editor
     *
     */
    public function get_content()
    {
        global $DB;

        $coursecontext = $this->courseid ? context_course::instance( $this->courseid ) : null;

        $content = $DB->get_field( 'course_format_options', 'value',
        array('courseid' => $this->courseid, 'format' => 'mb2sections', 'sectionid' => 0, 'name' => 'mb2sectionscontent' ) );

        if ( ! $content )
        {
            $content = '';
        }

        return $content;
    }







    /**
     *
     * Method to get image for the filemanager
     *
     */
    public function get_fileitemid()
    {

        global $DB;

        $itemid = $DB->get_field('course_format_options', 'value',
        array( 'courseid' => $this->courseid, 'format' => 'mb2sections', 'sectionid' => 0, 'name' => 'mb2sectionsimage' ) );

        if ( ! $itemid )
        {
            $itemid = 0;//file_get_unused_draft_itemid();
        }

        return $itemid;
    }





    /**
     *
     * Method to get image for the filemanager
     *
     */
    public function get_videofileitemid()
    {

        global $DB;

        $itemid = $DB->get_field('course_format_options', 'value',
        array( 'courseid' => $this->courseid, 'format' => 'mb2sections', 'sectionid' => 0, 'name' => 'mb2sectionsvideo' ) );

        if ( ! $itemid )
        {
            $itemid = 0;//file_get_unused_draft_itemid();
        }

        return $itemid;
    }






    /**
     *
     * Method to set content to update course
     *
     */
    public function set_content( $mb2sectionscontent )
    {

        global $DB;
        $data = new stdClass;

        $contenrecordsql = 'SELECT * FROM {course_format_options} WHERE courseid = ? AND format = ? AND sectionid = ? AND name = ?';
        $recordatts = array( 'courseid'=> $this->courseid, 'format' => 'mb2sections', 'sectionid' => 0, 'name' => 'mb2sectionscontent');

        if ( ! $DB->record_exists_sql( $contenrecordsql, array( $this->courseid, 'mb2sections',  0, 'mb2sectionscontent' ) ) )
        {
            $data->id = $DB->insert_record( 'course_format_options', $recordatts );
        }
        else
        {
            $data = $DB->get_record( 'course_format_options', $recordatts );
        }

        $data->value = $mb2sectionscontent;
        $DB->update_record('course_format_options', $data);

        return true;
    }





    /**
     * DB value setter for remuicourseimage_filemanager option
     * @param boolean $itemid Image itemid
     */
    public function set_fileitemid( $itemid )
    {
        global $DB;
        $data = new stdClass;

        $imagerecordsql = 'SELECT * FROM {course_format_options} WHERE courseid = ? AND format = ? AND sectionid = ? AND name = ?';
        $recordatts = array( 'courseid'=> $this->courseid, 'format' => 'mb2sections', 'sectionid' => 0, 'name' => 'mb2sectionsimage');

        if ( ! $DB->record_exists_sql( $imagerecordsql, array( $this->courseid, 'mb2sections',  0, 'mb2sectionsimage' ) ) )
        {
            $data->id = $DB->insert_record( 'course_format_options', $recordatts );
        }
        else
        {
            $data = $DB->get_record( 'course_format_options', $recordatts );
        }

        $data->value = $itemid;
        $DB->update_record('course_format_options', $data);

        return true;

    }





    /**
     * DB value setter for remuicourseimage_filemanager option
     * @param boolean $itemid Image itemid
     */
    public function set_videofileitemid( $itemid )
    {
        global $DB;
        $data = new stdClass;

        $imagerecordsql = 'SELECT * FROM {course_format_options} WHERE courseid = ? AND format = ? AND sectionid = ? AND name = ?';
        $recordatts = array( 'courseid'=> $this->courseid, 'format' => 'mb2sections', 'sectionid' => 0, 'name' => 'mb2sectionsvideo');

        if ( ! $DB->record_exists_sql( $imagerecordsql, array( $this->courseid, 'mb2sections',  0, 'mb2sectionsvideo' ) ) )
        {
            $data->id = $DB->insert_record( 'course_format_options', $recordatts );
        }
        else
        {
            $data = $DB->get_record( 'course_format_options', $recordatts );
        }

        $data->value = $itemid;
        $DB->update_record('course_format_options', $data);

        return true;

    }









    /**
     * Updates format options for a course.
     *
     * In case if course format was changed to 'mb2sections', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {

        if ( ! isset( $data->mb2sectionscontent_editor ) )
        {
            $data->mb2sectionscontent_editor = '';
        }

        if ( ! isset( $data->mb2sectionsimage_filemanager ) )
        {
            $data->mb2sectionsimage_filemanager = '';
        }

        if ( ! isset( $data->mb2sectionsvideo_filemanager ) )
        {
            $data->mb2sectionsvideo_filemanager = '';
        }

        if ( ! empty( $data )  )
        {
            $contextid = context_course::instance( $this->courseid );

            if ( ! empty( $data->mb2sectionsimage_filemanager ) )
            {
                file_postupdate_standard_filemanager( $data, 'mb2sectionsimage', $this->get_filemanager_options(), $contextid, 'format_mb2sections',
                'mb2sectionsimage', 0 );
            }

            if ( ! empty( $data->mb2sectionsvideo_filemanager ) )
            {
                file_postupdate_standard_filemanager( $data, 'mb2sectionsvideo', $this->get_video_filemanager_options(), $contextid, 'format_mb2sections',
                'mb2sectionsvideo', 0 );
            }

            if ( ! empty( $data->mb2sectionscontent_editor ) )
            {
                file_postupdate_standard_editor( $data, 'mb2sectionscontent',
                $this->get_editor_options(), $contextid, 'format_mb2sections', 'mb2sectionscontent', 0);
            }

            $this->set_fileitemid( $data->mb2sectionsimage_filemanager );
            $this->set_videofileitemid( $data->mb2sectionsvideo_filemanager );
            $this->set_content( $data->mb2sectionscontent );
        }

        return $this->update_format_options($data);
    }




    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Prepares the templateable object to display section name.
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
            $editable = null, $edithint = null, $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_mb2sections');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_mb2sections', $title);
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    /**
     * Callback used in WS core_course_edit_section when teacher performs an AJAX action on a section (show/hide).
     *
     * Access to the course is already validated in the WS but the callback has to make sure
     * that particular action is allowed by checking capabilities
     *
     * Course formats should register.
     *
     * @param section_info|stdClass $section
     * @param string $action
     * @param int $sr
     * @return null|array any data for the Javascript post-processor (must be json-encodeable)
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;

        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'mb2sections' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_mb2sections');
        $rv['section_availability'] = $renderer->section_availability($this->get_section($section));
        return $rv;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        return $this->get_format_options();
    }
}




/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_mb2sections_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'mb2sections'], MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}





/**
 * Serve the files from the format_mb2sections file areas
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function format_mb2sections_pluginfile( $course, $cm, $context, $filearea, $args, $forcedownload, array $options = array() ) {

    //global $PAGE;

    if ($context->contextlevel != CONTEXT_COURSE)
    {
        return false;
    }

    require_login();

    if ( $filearea !== 'mb2sectionscontent' && $filearea !== 'mb2sectionsimage' && $filearea !== 'mb2sectionsvideo' )
    {
        return false;
    }

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.

    if ( ! $args)
    {
        $filepath = '/';
    }
    else
    {
        $filepath = '/' . implode('/', $args) . '/';
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'format_mb2sections', $filearea, $itemid, $filepath, $filename);

    if ( ! $file )
    {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    // From Moodle 2.3, use send_stored_file instead.
    send_stored_file( $file, null, 0, $forcedownload, $options );
}
