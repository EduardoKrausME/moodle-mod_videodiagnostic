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
 * Read a learner's initial and post-study diagnostic comparison.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videodiagnostic\mcp;

use local_mcp\security\authenticated_identity;

/**
 * Reuse diagnostic_manager for responses and scores, never mutate progress.
 */
final class get_student extends read_base {
    /**
     * Return the MCP tool identifier.
     *
     * @return string
     */
    public function get_name(): string {
        return "videodiagnostic_get_student";
    }

    /**
     * Return the translated title of the MCP tool.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string("mcpgetstudent", "mod_videodiagnostic");
    }

    /**
     * Return the translated description of the MCP tool.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string("mcpgetstudentdesc", "mod_videodiagnostic");
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
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "userid" => ["type" => "integer", "minimum" => 1],
            "offset" => ["type" => "integer", "minimum" => 0],
            "limit" => ["type" => "integer", "minimum" => 1, "maximum" => 40],
        ], ["cmid", "userid"]);
    }

    /**
     * Perform the authorized MCP operation.
     *
     * @param array $arguments Input.
     * @param authenticated_identity $identity User.
     * @return array
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        [$cm, $context, $activity] = access::activity((int)$arguments["cmid"],
            $identity, $this->get_required_capability());
        $student = access::student($cm, $context, $activity, (int)$arguments["userid"], $identity);
        return [
            "cmid" => (int)$cm->id,
            "name" => fullname($student),
        ] + report_data::detail($activity, (int)$student->id,
            max(0, (int)($arguments["offset"] ?? 0)),
            max(1, min(40, (int)($arguments["limit"] ?? 20))));
    }
}
