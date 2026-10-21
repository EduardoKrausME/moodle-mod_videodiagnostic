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
 * Validate and save Video Diagnostic questions from web forms and MCP.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videodiagnostic;

use moodle_exception;
use stdClass;

/**
 * Central persistence service shared by the native editor and MCP adapters.
 */
final class question_writer {
    /** Supported diagnostic question types. */
    public const TYPES = ["text", "choice", "marker", "interval"];

    /**
     * Validate a question request without database changes.
     *
     * @param array $fields Normalized question fields.
     * @return array
     */
    public function validate(array $fields): array {
        $type = (string)($fields["qtype"] ?? "text");
        $text = (string)($fields["questiontext"] ?? "");
        if (!in_array($type, self::TYPES, true) ||
                trim(strip_tags($text)) === "" || strlen($text) > 20000) {
            throw new moodle_exception("mcpinvalidquestion", "mod_videodiagnostic");
        }
        $options = $fields["options"] ?? [];
        if (is_string($options)) {
            $options = preg_split("/\R/u", trim($options)) ?: [];
        }
        if (!is_array($options) || count($options) > 50) {
            throw new moodle_exception("mcpinvalidquestion", "mod_videodiagnostic");
        }
        $options = array_values(array_filter(array_map(
            static fn($text): string => trim((string)$text), $options
        ), static fn(string $text): bool => $text !== ""));
        if (strlen(json_encode($options)) > 20000) {
            throw new moodle_exception("mcpinvalidquestion", "mod_videodiagnostic");
        }
        $expected = (string)($fields["correctanswer"] ?? "");
        if (strlen($expected) > 10000) {
            throw new moodle_exception("mcpinvalidquestion", "mod_videodiagnostic");
        }
        $start = (float)($fields["expectedstart"] ?? 0);
        $end = (float)($fields["expectedend"] ?? 0);
        $tolerance = (float)($fields["tolerance"] ?? 5);
        $weight = (float)($fields["weight"] ?? 1);
        if (!is_finite($start) || !is_finite($end) || !is_finite($tolerance) ||
                !is_finite($weight) || $start < 0 || $end < 0 ||
                $tolerance < 0 || $weight <= 0 || $weight > 10000) {
            throw new moodle_exception("mcpinvalidquestion", "mod_videodiagnostic");
        }
        return [
            "questiontext" => $text,
            "questionformat" => (int)($fields["questionformat"] ?? FORMAT_HTML),
            "qtype" => $type,
            "optionsjson" => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            "correctanswer" => $expected,
            "expectedstart" => round($start, 3),
            "expectedend" => round($end, 3),
            "tolerance" => round($tolerance, 3),
            "required" => empty($fields["required"]) ? 0 : 1,
            "weight" => round(max(0.01, $weight), 2),
        ];
    }

    /**
     * Save fields only in the requested activity, preserving stable question identifiers.
     *
     * @param stdClass $activity Video Diagnostic instance.
     * @param int $questionid Existing ID or 0 to create.
     * @param array $fields Validated or raw request.
     * @return int Question record ID.
     */
    public function save(stdClass $activity, int $questionid, array $fields): int {
        global $DB;
        $record = (object)$this->validate($fields);
        $record->videodiagnosticid = (int)$activity->id;
        $record->timemodified = time();
        if ($questionid > 0) {
            $existing = $DB->get_record("videodiagnostic_questions", [
                "id" => $questionid, "videodiagnosticid" => (int)$activity->id,
            ], "*", MUST_EXIST);
            $record->id = (int)$existing->id;
            $DB->update_record("videodiagnostic_questions", $record);
            return (int)$record->id;
        }
        $record->sortorder = 10 + (int)$DB->get_field_sql(
            "SELECT COALESCE(MAX(sortorder), 0) FROM {videodiagnostic_questions} WHERE videodiagnosticid = ?",
            [(int)$activity->id]
        );
        $record->timecreated = time();
        return (int)$DB->insert_record("videodiagnostic_questions", $record);
    }

    /**
     * Stable hash of the full saved question definitions for optimistic concurrency.
     *
     * @param int $activityid Activity.
     * @return string
     */
    public function state_hash(int $activityid): string {
        $questions = (new diagnostic_manager())->questions($activityid);
        $values = [];
        foreach ($questions as $question) {
            $values[] = [
                (int)$question->id, (int)$question->sortorder,
                (string)$question->questiontext, (int)$question->questionformat,
                (string)$question->qtype,
                (string)$question->optionsjson, (string)$question->correctanswer,
                (float)$question->expectedstart, (float)$question->expectedend,
                (float)$question->tolerance, (int)$question->required,
                (float)$question->weight, (int)$question->timemodified,
            ];
        }
        return hash("sha256", json_encode($values, JSON_THROW_ON_ERROR));
    }

    /**
     * Reject updates based on a stale question bank hash.
     *
     * @param int $activityid Activity.
     * @param string $expected Client state hash.
     * @return void
     */
    public function require_state(int $activityid, string $expected): void {
        if (!preg_match("/^[a-f0-9]{64}$/", $expected) ||
                !hash_equals($this->state_hash($activityid), $expected)) {
            throw new moodle_exception("mcpstalequestions", "mod_videodiagnostic");
        }
    }
}
