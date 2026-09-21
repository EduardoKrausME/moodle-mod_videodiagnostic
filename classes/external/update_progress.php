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

namespace mod_videodiagnostic\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videodiagnostic\progress_manager;

/**
 * AJAX web service for playback tracking.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_progress extends external_api {
    /**
     * Defines parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Course module id'),
            'position' => new external_value(PARAM_FLOAT, 'Current playback position'),
            'duration' => new external_value(PARAM_FLOAT, 'Video duration'),
            'segmentstart' => new external_value(PARAM_FLOAT, 'Observed segment start'),
            'segmentend' => new external_value(PARAM_FLOAT, 'Observed segment end'),
        ]);
    }

    /**
     * Updates progress.
     *
     * @param int $id Course module id.
     * @param float $position Position.
     * @param float $duration Duration.
     * @param float $segmentstart Segment start.
     * @param float $segmentend Segment end.
     * @return array
     */
    public static function execute(
        int $id,
        float $position,
        float $duration,
        float $segmentstart,
        float $segmentend
    ): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'id' => $id,
            'position' => $position,
            'duration' => $duration,
            'segmentstart' => $segmentstart,
            'segmentend' => $segmentend,
        ]);
        $cm = get_coursemodule_from_id('videodiagnostic', $params['id'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videodiagnostic:view', $context);
        $activity = $DB->get_record('videodiagnostic', ['id' => $cm->instance], '*', MUST_EXIST);

        $progress = (new progress_manager())->update(
            $activity,
            $cm,
            $USER->id,
            (float)$params['position'],
            (float)$params['duration'],
            (float)$params['segmentstart'],
            (float)$params['segmentend']
        );
        return [
            'percent' => (float)$progress->percent,
            'lastposition' => (float)$progress->lastposition,
            'uniquewatched' => (float)$progress->uniquewatched,
            'totalwatchtime' => (float)$progress->totalwatchtime,
        ];
    }

    /**
     * Defines return shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'percent' => new external_value(PARAM_FLOAT, 'Unique watched percentage'),
            'lastposition' => new external_value(PARAM_FLOAT, 'Last position'),
            'uniquewatched' => new external_value(PARAM_FLOAT, 'Unique watched seconds'),
            'totalwatchtime' => new external_value(PARAM_FLOAT, 'Observed total playback seconds'),
        ]);
    }
}
