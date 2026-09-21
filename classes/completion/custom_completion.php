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

namespace mod_videodiagnostic\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Evaluates a custom completion rule.
     *
     * @param string $rule Rule name.
     * @return int Completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $activity = $DB->get_record('videodiagnostic', ['id' => $this->cm->instance], '*', MUST_EXIST);
        if ($rule === 'completiondiagnostic') {
            if (empty($activity->completiondiagnostic)) {
                return COMPLETION_COMPLETE;
            }
            $submitted = $DB->record_exists('videodiagnostic_attempts', [
                'videodiagnosticid' => $activity->id,
                'userid' => $this->userid,
                'stage' => 1,
                'submitted' => 1,
            ]);
            return $submitted ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        if ($rule === 'completionpercent') {
            $required = (float)$activity->completionpercent;
            if ($required <= 0) {
                return COMPLETION_COMPLETE;
            }
            $percent = (float)$DB->get_field('videodiagnostic_progress', 'percent', [
                'videodiagnosticid' => $activity->id,
                'userid' => $this->userid,
            ]);
            return $percent >= $required ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        return COMPLETION_INCOMPLETE;
    }

    /**
     * Returns custom rule identifiers.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completiondiagnostic', 'completionpercent'];
    }

    /**
     * Returns descriptions for enabled rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;
        $activity = $DB->get_record('videodiagnostic', ['id' => $this->cm->instance], '*', MUST_EXIST);
        return [
            'completiondiagnostic' => get_string('completiondiagnostic_desc', 'mod_videodiagnostic'),
            'completionpercent' => get_string('completionpercent_desc', 'mod_videodiagnostic',
                format_float($activity->completionpercent, 0)),
        ];
    }

    /**
     * Defines display order alongside standard rules.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return ['completionview', 'completiondiagnostic', 'completionpercent'];
    }
}
