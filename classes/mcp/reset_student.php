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
 * Confirm a destructive learner reset through existing diagnostic_manager.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videodiagnostic\mcp;

use local_mcp\security\authenticated_identity;
use mod_videodiagnostic\diagnostic_manager;

/**
 * Reset one learner only after a state-bound MCP confirmation.
 */
final class reset_student extends write_base {
    /**
     * Return the unique MCP tool identifier.
     *
     * @return string
     */
    public function get_name(): string {
        return "videodiagnostic_reset_student";
    }

    /**
     * Return the translated title of this tool.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string("mcpresetstudent", "mod_videodiagnostic");
    }

    /**
     * Return the translated description of this tool.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string("mcpresetstudentdesc", "mod_videodiagnostic");
    }

    /**
     * Declare the capability required to access this tool.
     *
     * @return string
     */
    public function get_required_capability(): string {
        return "mod/videodiagnostic:resetresponses";
    }

    /**
     * Mark a write that can modify or delete existing data.
     *
     * @return bool
     */
    public function is_destructive(): bool {
        return true;
    }

    /**
     * Describe the allowed MCP input parameters.
     *
     * @return array
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "userid" => ["type" => "integer", "minimum" => 1],
            "expected_state_hash" => ["type" => "string", "pattern" => "^[a-f0-9]{64}$",
                "description" => "Use the state_hash returned by videodiagnostic_get_student."],
        ], ["cmid", "userid", "expected_state_hash"]);
    }

    /**
     * Validate and preview the requested changes without saving.
     *
     * @param array $arguments Arguments.
     * @param authenticated_identity $identity User.
     * @return array
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        [$cm, $context, $activity] = access::activity((int)$arguments["cmid"],
            $identity, $this->get_required_capability());
        require_capability("mod/videodiagnostic:viewreport", $context, $identity->userid);
        $user = access::student($cm, $context, $activity, (int)$arguments["userid"], $identity);
        report_data::require_state($activity, (int)$user->id, (string)$arguments["expected_state_hash"]);
        return [
            "operation" => $this->get_name(),
            "cmid" => (int)$cm->id,
            "userid" => (int)$user->id,
            "name" => fullname($user),
            "current_state" => report_data::summary($activity, (int)$user->id),
            "will_delete_diagnostic_attempts" => true,
            "will_delete_watched_segments" => true,
            "will_reset_completion" => true,
            "cannot_undo" => true,
        ];
    }

    /**
     * Execute the confirmed MCP operation.
     *
     * @param array $arguments Arguments.
     * @param authenticated_identity $identity User.
     * @return array
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $factory = \core\lock\lock_config::get_lock_factory("mod_videodiagnostic");
        $lock = $factory->get_lock("mcp_reset_" . (int)$arguments["cmid"] . "_" .
            (int)$arguments["userid"], 10);
        if (!$lock) {
            throw new \moodle_exception("mcpuploadbusy", "mod_videodiagnostic");
        }
        try {
            $this->preview($arguments, $identity);
            [$cm, $context, $activity] = access::activity((int)$arguments["cmid"],
                $identity, $this->get_required_capability());
            (new diagnostic_manager())->reset_user($activity, $cm, (int)$arguments["userid"]);
            return [
                "cmid" => (int)$cm->id,
                "userid" => (int)$arguments["userid"],
                "reset" => true,
                "status" => "notstarted",
            ];
        } finally {
            $lock->release();
        }
    }
}
