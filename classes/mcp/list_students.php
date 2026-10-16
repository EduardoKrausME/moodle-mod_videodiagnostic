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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Page enrolled learners' diagnostic summaries through MCP.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videodiagnostic\mcp;

use local_mcp\security\authenticated_identity;

/**
 * Read only teacher-authorized learner summaries, filtered by separate groups.
 */
final class list_students extends read_base {
    /**
     * @return string
     */
    public function get_name(): string {
        return "videodiagnostic_list_students";
    }

    /**
     * @return string
     */
    public function get_title(): string {
        return get_string("mcpliststudents", "mod_videodiagnostic");
    }

    /**
     * @return string
     */
    public function get_description(): string {
        return get_string("mcpliststudentsdesc", "mod_videodiagnostic");
    }

    /**
     * @return string
     */
    public function get_required_capability(): string {
        return "mod/videodiagnostic:viewreport";
    }

    /**
     * @return array
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "offset" => ["type" => "integer", "minimum" => 0],
            "limit" => ["type" => "integer", "minimum" => 1, "maximum" => 50],
        ], ["cmid"]);
    }

    /**
     * @param array $arguments Input.
     * @param authenticated_identity $identity User.
     * @return array
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        [$cm, $context, $activity] = access::activity((int)$arguments["cmid"],
            $identity, $this->get_required_capability());
        $offset = max(0, (int)($arguments["offset"] ?? 0));
        $limit = max(1, min(50, (int)($arguments["limit"] ?? 20)));
        $students = [];
        $enrolled = get_enrolled_users($context, "", 0, "u.id,u.firstname,u.lastname",
            "u.lastname ASC,u.firstname ASC,u.id ASC");
        foreach ($enrolled as $user) {
            if (has_capability("mod/videodiagnostic:managequestions", $context, (int)$user->id) ||
                    !access::allowed_group($cm, $context, (int)$user->id, $identity)) {
                continue;
            }
            $students[] = $user;
        }
        $result = [];
        foreach (array_slice($students, $offset, $limit) as $student) {
            $result[] = [
                "name" => fullname($student),
            ] + report_data::summary($activity, (int)$student->id);
        }
        return [
            "cmid" => (int)$cm->id,
            "total" => count($students),
            "offset" => $offset,
            "limit" => $limit,
            "has_more" => $offset + $limit < count($students),
            "students" => $result,
        ];
    }
}
