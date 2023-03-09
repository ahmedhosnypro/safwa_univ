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
 * This file contains the filter base class.
 *
 * @package    block_filtered_course_list
 * @copyright  2018 CLAMP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_filtered_course_list;

defined('MOODLE_INTERNAL') || die();

/**
 * This interface allows us to define the following static functions in a way
 * that mimics a "public abstract static function()" in the filter class itself.
 * This is a workaround for limitations in PHP 5 -- see the below link for more details.
 *
 * https://stackoverflow.com/questions/999066/why-does-php-5-2-disallow-abstract-static-class-methods/6386309#6386309
 *
 * @package    block_filtered_course_list
 * @copyright  2016 CLAMP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface filter_interface {
    /**
     * Retrieve filter short name.
     *
     * @return string This filter's shortname.
     */
    public static function getshortname();

    /**
     * Retrieve filter full name.
     *
     * @return string This filter's shortname.
     */
    public static function getfullname();

    /**
     * Retrieve filter component.
     *
     * @return string This filter's component.
     */
    public static function getcomponent();

    /**
     * Retrieve filter version sync number.
     *
     * @return string This filter's version sync number.
     */
    public static function getversionsyncnum();
}

/**
 * An abstract class to generate rubrics based on a line of rubric config
 *
 * @package    block_filtered_course_list
 * @copyright  2016 CLAMP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class filter implements filter_interface {
    /** @var array Elements of the rubric config line */
    public $line;
    /** @var array A list of courses the current user is enrolled in */
    public $courselist;
    /** @var array Config settings for the block */
    public $config;
    /** @var array A list of the rubric objects generated by the line */
    public $rubrics = array();

    /**
     * Constructor
     *
     * @param array $line The first element is the config type, the second is the rest of the line
     * @param array $courselist An array of courses the user is enrolled in
     * @param array $config Details of the block configuration
     */
    public function __construct($line, $courselist, $config) {
        $this->line = $this->validate_line($line);
        $this->courselist = $courselist;
        $this->config = $config;
    }

    /**
     * Each subclass must define its own line validation.
     * In general, the first element has already been validated or we wouldn't
     * have gotten to the right class.
     * Rubric titles will pass through htmlentities() when they need to, so no
     * need to innoculate them here.
     * Return a fixed-up array.
     *
     * @param array $line The array of line elements that has been passed to the constructor
     */
    abstract public function validate_line($line);

    /**
     * Each subclass must define how to get the right rubrics
     */
    abstract public function get_rubrics();

    /**
     * Validate expanded value
     * This should be similar for all subclasses.
     *
     * @param int $index The index of the $line array that should contain the expanded value
     * @param array $arr The line array
     */
    public function validate_expanded($index, &$arr) {
        if (!array_key_exists($index, $arr)) {
            $arr[$index] = 'collapsed';
        }
        $arr[$index] = (\core_text::strpos($arr[$index], 'e') === 0) ? 'expanded' : 'collapsed';
    }
}
