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

use filter_edwiserpbf\ContentGenerator;

class filter_edwiserpbf extends moodle_text_filter {

    /**
     * Filter tags and convert to associative array
     * @param  array $tags Tags array
     * @return array       Tags array
     */
    private function filter_tags($tags) {
        $assoarr = array();
        for ($i = 1; $i < count($tags); $i++) {
            $assoarr[$tags[$i][0]] = $tags[$i + 1][0];
            $i = $i + 1;
        }
        return $assoarr;
    }

    public function filter($text, array $options = array()) {

        if (!is_string($text) or empty($text)) {
            return $text;
        }

        $edwisershortcode = "[edwiser-";

        $pos = strpos($text, $edwisershortcode);

        if ($pos === false) {
            return $text;
        }
        // Courses Pregmatch
        $pregmatch = "(\[edwiser\-courses[ ]+(catid)\=[\'\"’‘“”]([a-zA-Z0-9,]+)[\'\"’‘“”][ ](layout)\=[\'\"’‘“”]([a-zA-Z0-9]+)[\'\"’‘“”]\])";

        preg_match_all(
            $pregmatch,
            $text,
            $tags
        );

        // Find the replacement Text
        if (isset($tags[0][0]) && $tags[0][0] != "") {
            $replace = $tags[0][0];

            // Filter the shortcode tags to object
            $tags = $this->filter_tags($tags);

            // Content Generation
            $cg = new ContentGenerator();
            $content = $cg->generate_courses($tags);

            // return $text;
            return str_replace($replace, $content, $text);
        }

        // Categories Pregmatch
        $pregmatch = "(\[edwiser\-categories[ ]+(layout)\=[\'\"’‘“”]([a-zA-Z0-9]*)[\'\"’‘“”][ ](btnlabel)\=[\'\"’‘“”]([a-zA-Z0-9]+)[\'\"’‘“”][ ](count)\=[\'\"’‘“”]([a-zA-Z0-9]+)[\'\"’‘“”]\])";

        preg_match_all(
            $pregmatch,
            $text,
            $tags
        );

        // Find the replacement Text
        if (isset($tags[0][0]) && $tags[0][0] != "") {
            $replace = $tags[0][0];

            // Filter the shortcode tags to object
            $tags = $this->filter_tags($tags);

            // Content Generation
            $cg = new ContentGenerator();
            $content = $cg->generate_categories($tags);

            // return $text;
            return str_replace($replace, $content, $text);
        }

        return $text;
    }
}
