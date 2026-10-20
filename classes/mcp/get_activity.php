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
 * Read the diagnostic activity configuration without unreleased study materials.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videodiagnostic\mcp;

use local_mcp\security\authenticated_identity;
use mod_videodiagnostic\diagnostic_manager;
use moodle_url;

/**
 * Provide teacher-readable activity metadata and question counts.
 */
final class get_activity extends read_base {
    /**
     * Return the MCP tool identifier.
     *
     * @return string
     */
    public function get_name(): string {
        return "videodiagnostic_get_activity";
    }

    /**
     * Return the translated title of the MCP tool.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string("mcpgetactivity", "mod_videodiagnostic");
    }

    /**
     * Return the translated description of the MCP tool.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string("mcpgetactivitydesc", "mod_videodiagnostic");
    }

    /**
     * Declare the Moodle capability required by the MCP tool.
     *
     * @return string
     */
    public function get_required_capability(): string {
        return "mod/videodiagnostic:viewreport";
    }

    /**
     * Declare the supported MCP input arguments.
     *
     * @return array
     */
    public function get_input_schema(): array {
        return $this->object_schema(["cmid" => ["type" => "integer", "minimum" => 1]], ["cmid"]);
    }

    /**
     * Perform the authorized MCP operation.
     *
     * @param array $arguments Input.
     * @param authenticated_identity $identity Connected user.
     * @return array
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        [$cm, $context, $activity] = access::activity((int)$arguments["cmid"],
            $identity, $this->get_required_capability());
        return [
            "cmid" => (int)$cm->id,
            "courseid" => (int)$cm->course,
            "name" => (string)$activity->name,
            "question_count" => count((new diagnostic_manager())->questions((int)$activity->id)),
            "video_source_type" => (string)$activity->videosource,
            "explanation_source_type" => (string)$activity->explanationsource,
            "explanation_published" => (bool)$activity->explanationpublished,
            "release_after_initial" => (bool)$activity->releaseafterinitial,
            "post_study_enabled" => (bool)$activity->allowpostattempt,
            "completion_initial_required" => (bool)$activity->completiondiagnostic,
            "completion_watched_percent" => (float)$activity->completionpercent,
            "uses_moodle_gradebook" => false,
            "url" => (new moodle_url("/mod/videodiagnostic/view.php",
                ["id" => (int)$cm->id]))->out(false),
        ];
    }
}
