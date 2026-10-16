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
 * Create or update a diagnostic question with MCP confirmation.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videodiagnostic\mcp;

use local_mcp\security\authenticated_identity;
use mod_videodiagnostic\question_writer;

/**
 * A confirmed question change may affect criteria attached to existing attempts.
 */
final class save_question extends write_base {
    /**
     * @return string
     */
    public function get_name(): string {
        return "videodiagnostic_save_question";
    }

    /**
     * @return string
     */
    public function get_title(): string {
        return get_string("mcpsavequestion", "mod_videodiagnostic");
    }

    /**
     * @return string
     */
    public function get_description(): string {
        return get_string("mcpsavequestiondesc", "mod_videodiagnostic");
    }

    /**
     * @return string
     */
    public function get_required_capability(): string {
        return "mod/videodiagnostic:managequestions";
    }

    /**
     * @return bool
     */
    public function is_destructive(): bool {
        return true;
    }

    /**
     * @return array
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "expected_state_hash" => ["type" => "string", "pattern" => "^[a-f0-9]{64}$"],
            "questionid" => ["type" => "integer", "minimum" => 0],
            "questiontext" => ["type" => "string", "minLength" => 1, "maxLength" => 20000],
            "qtype" => ["type" => "string", "enum" => question_writer::TYPES],
            "options" => ["type" => "array", "maxItems" => 50,
                "items" => ["type" => "string", "maxLength" => 4000]],
            "correctanswer" => ["type" => "string", "maxLength" => 10000],
            "expectedstart" => ["type" => "number", "minimum" => 0],
            "expectedend" => ["type" => "number", "minimum" => 0],
            "tolerance" => ["type" => "number", "minimum" => 0],
            "weight" => ["type" => "number", "exclusiveMinimum" => 0, "maximum" => 10000],
            "required" => ["type" => "boolean"],
        ], ["cmid", "expected_state_hash", "questiontext", "qtype"]);
    }

    /**
     * @param array $arguments Arguments.
     * @param authenticated_identity $identity User.
     * @return array
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        global $DB;
        [$cm, $context, $activity] = access::activity((int)$arguments["cmid"],
            $identity, $this->get_required_capability());
        $writer = new question_writer();
        $writer->require_state((int)$activity->id, (string)$arguments["expected_state_hash"]);
        $qid = (int)($arguments["questionid"] ?? 0);
        if ($qid > 0) {
            $DB->get_record("videodiagnostic_questions",
                ["id" => $qid, "videodiagnosticid" => (int)$activity->id], "id", MUST_EXIST);
        }
        $fields = $writer->validate($arguments);
        return [
            "operation" => $this->get_name(),
            "cmid" => (int)$cm->id,
            "questionid" => $qid ?: null,
            "action" => $qid ? "update" : "create",
            "type" => $fields["qtype"],
            "question_text" => $fields["questiontext"],
            "options" => json_decode($fields["optionsjson"], true),
            "expected_answer" => $fields["correctanswer"],
            "expected_start" => $fields["expectedstart"],
            "expected_end" => $fields["expectedend"],
            "weight" => $fields["weight"],
            "may_change_assessment_definition" => $qid > 0,
            "student_responses_will_not_be_deleted" => true,
        ];
    }

    /**
     * @param array $arguments Arguments.
     * @param authenticated_identity $identity User.
     * @return array
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $factory = \core\lock\lock_config::get_lock_factory("mod_videodiagnostic");
        $lock = $factory->get_lock("mcp_questions_" . (int)$arguments["cmid"], 10);
        if (!$lock) {
            throw new \moodle_exception("mcpuploadbusy", "mod_videodiagnostic");
        }
        try {
            $this->preview($arguments, $identity);
            [$cm, $context, $activity] = access::activity((int)$arguments["cmid"],
                $identity, $this->get_required_capability());
            $writer = new question_writer();
            $id = $writer->save($activity, (int)($arguments["questionid"] ?? 0), $arguments);
            return [
                "cmid" => (int)$cm->id,
                "questionid" => $id,
                "action" => !empty($arguments["questionid"]) ? "updated" : "created",
                "state_hash" => $writer->state_hash((int)$activity->id),
            ];
        } finally {
            $lock->release();
        }
    }
}
