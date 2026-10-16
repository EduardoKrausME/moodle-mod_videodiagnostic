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
 * List diagnostic questions for users authorized to manage them.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videodiagnostic\mcp;

use local_mcp\security\authenticated_identity;
use mod_videodiagnostic\diagnostic_manager;

/**
 * Correct answers and expected time ranges are only shown to question managers.
 */
final class list_questions extends read_base {
    /**
     * Return the MCP tool identifier.
     *
     * @return string
     */
    public function get_name(): string {
        return "videodiagnostic_list_questions";
    }

    /**
     * Return the translated title of the MCP tool.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string("mcplistquestions", "mod_videodiagnostic");
    }

    /**
     * Return the translated description of the MCP tool.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string("mcplistquestionsdesc", "mod_videodiagnostic");
    }

    /**
     * Declare the Moodle capability required by the MCP tool.
     *
     * @return string
     */
    public function get_required_capability(): string {
        return "mod/videodiagnostic:managequestions";
    }

    /**
     * Declare the supported MCP input arguments.
     *
     * @return array
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "limit" => ["type" => "integer", "minimum" => 1, "maximum" => 50],
            "offset" => ["type" => "integer", "minimum" => 0],
        ], ["cmid"]);
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
        $questions = array_values((new diagnostic_manager())->questions((int)$activity->id));
        $offset = max(0, (int)($arguments["offset"] ?? 0));
        $limit = max(1, min(50, (int)($arguments["limit"] ?? 20)));
        $items = [];
        foreach (array_slice($questions, $offset, $limit) as $question) {
            $items[] = [
                "questionid" => (int)$question->id,
                "sortorder" => (int)$question->sortorder,
                "type" => (string)$question->qtype,
                "text" => (string)$question->questiontext,
                "format" => (int)$question->questionformat,
                "options" => json_decode((string)$question->optionsjson, true) ?: [],
                "expected_answer" => (string)$question->correctanswer,
                "expected_start" => (float)$question->expectedstart,
                "expected_end" => (float)$question->expectedend,
                "tolerance" => (float)$question->tolerance,
                "weight" => (float)$question->weight,
                "required" => (bool)$question->required,
                "modified" => (int)$question->timemodified,
            ];
        }
        return [
            "cmid" => (int)$cm->id,
            "total" => count($questions),
            "state_hash" => (new \mod_videodiagnostic\question_writer())->state_hash((int)$activity->id),
            "limit" => $limit,
            "offset" => $offset,
            "has_more" => $offset + $limit < count($questions),
            "questions" => $items,
        ];
    }
}
