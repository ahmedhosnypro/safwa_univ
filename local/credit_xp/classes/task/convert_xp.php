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
 * Version details.
 *
 * @package    local_credit_xp
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credit_xp\task;

defined('MOODLE_INTERNAL') || die;

class convert_xp extends \core\task\scheduled_task
{
    /**
     * @return string
     * @throws \coding_exception
     * @codeCoverageIgnore
     */
    public function get_name()
    {
        return get_string('convert_xp', 'local_credit_xp');
    }

    public function execute()
    {
        global $DB;

        if (!$ratio = get_config('local_credit_xp', 'conversion_ratio')) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        /** @var \enrol_credit_plugin $credit_enrol */
        if (!$credit_enrol = enrol_get_plugin('credit')) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $records = $DB->get_recordset_sql('SELECT
                                           SUM(xp.xp) AS total_xp,
                                           xp_converted.converted_xp AS converted_xp,
                                           xp.userid AS userid                      
                                           FROM {block_xp} xp
                                           LEFT JOIN {credit_xp_converted} AS xp_converted ON xp_converted.user_id = xp.userid
                                           WHERE 1
                                           GROUP BY xp.userid
                                           HAVING xp_converted.converted_xp IS NULL
                                           OR SUM(xp.xp) - xp_converted.converted_xp > :ratio', ['ratio' => $ratio]);

        foreach ($records as $record) {
            $xp_to_convert = $record->total_xp - $record->converted_xp;

            $credits = intval($xp_to_convert*$ratio);

            $credit_enrol->add_credits($record->userid, $credits);

            if (!$converted = $DB->get_record('credit_xp_converted', ['user_id' => $record->userid])) {
                $converted = new \stdClass();
                $converted->user_id = $record->userid;
                $converted->converted_xp = 0;
            }

            $converted->converted_xp += $xp_to_convert;

            if (isset($converted->id)) {
                $DB->update_record('credit_xp_converted', $converted);
            } else {
                $DB->insert_record('credit_xp_converted', $converted);
            }
        }
    }
}