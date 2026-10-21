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
 * Project read-only Video Diagnostic reports for MCP.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videodiagnostic\mcp;

use mod_videodiagnostic\diagnostic_manager;
use moodle_exception;
use stdClass;

/**
 * Wrap existing diagnostic_manager scoring and responses without creating progress.
 */
final class report_data {
    /**
     * Return one student's persisted progress, initial and post-study results.
     *
     * @param stdClass $activity Video Diagnostic.
     * @param int $userid Learner.
     * @return array
     */
    public static function summary(stdClass $activity, int $userid): array {
        global $DB;
        $manager = new diagnostic_manager();
        $initial = $manager->get_attempt((int)$activity->id, $userid, diagnostic_manager::STAGE_INITIAL);
        $final = $manager->get_attempt((int)$activity->id, $userid, diagnostic_manager::STAGE_FINAL);
        $progress = $DB->get_record("videodiagnostic_progress", [
            "videodiagnosticid" => (int)$activity->id,
            "userid" => $userid,
        ]);
        $status = "notstarted";
        if ($initial && $initial->submitted) {
            $status = ($final && $final->submitted) || !$activity->allowpostattempt
                ? "completed" : "inprogress";
        } else if ($progress && (float)$progress->percent > 0) {
            $status = "inprogress";
        }
        return [
            "userid" => $userid,
            "status" => $status,
            "watched_percent" => $progress ? (float)$progress->percent : 0.0,
            "unique_watched_seconds" => $progress ? (float)$progress->uniquewatched : 0.0,
            "duration_seconds" => $progress ? (float)$progress->duration : 0.0,
            "initial" => self::attempt_summary($initial),
            "post_study" => self::attempt_summary($final),
            "changed_answers" => ($final && $final->submitted) ? (int)$final->changedcount : null,
        ];
    }

    /**
     * Return one diagnostic attempt without exposing unpublished draft answers.
     *
     * @param stdClass|null $attempt Attempt row.
     * @return array
     */
    private static function attempt_summary(?stdClass $attempt): array {
        return [
            "submitted" => $attempt !== null && (bool)$attempt->submitted,
            "submitted_at" => $attempt && $attempt->submitted ? (int)$attempt->timesubmitted : null,
            "score_available" => $attempt !== null && (bool)$attempt->submitted && (bool)$attempt->scoreavailable,
            "score_percent" => ($attempt && $attempt->submitted && $attempt->scoreavailable)
                ? (float)$attempt->score : null,
        ];
    }

    /**
     * Read detailed submitted answers and capped watched-segment history.
     *
     * @param stdClass $activity Activity.
     * @param int $userid Target learner.
     * @param int $offset Question offset.
     * @param int $limit Question limit.
     * @return array
     */
    public static function detail(stdClass $activity, int $userid, int $offset, int $limit): array {
        global $DB;
        $manager = new diagnostic_manager();
        $initial = $manager->get_attempt((int)$activity->id, $userid, diagnostic_manager::STAGE_INITIAL);
        $final = $manager->get_attempt((int)$activity->id, $userid, diagnostic_manager::STAGE_FINAL);
        $initialresponses = ($initial && $initial->submitted) ? $manager->responses((int)$initial->id) : [];
        $finalresponses = ($final && $final->submitted) ? $manager->responses((int)$final->id) : [];
        $progress = $DB->get_record("videodiagnostic_progress", [
            "videodiagnosticid" => (int)$activity->id, "userid" => $userid,
        ]);
        $questions = array_values($manager->questions((int)$activity->id));
        $responses = [];
        foreach (array_slice($questions, $offset, $limit) as $question) {
            $responses[] = [
                "questionid" => (int)$question->id,
                "type" => (string)$question->qtype,
                "question" => (string)$question->questiontext,
                "initial" => self::response($initialresponses[$question->id] ?? null),
                "post_study" => self::response($finalresponses[$question->id] ?? null),
                "changed" => isset($finalresponses[$question->id])
                    ? (bool)$finalresponses[$question->id]->changedfrominitial : null,
            ];
        }
        $segments = [];
        if ($progress && $progress->watchedsegments) {
            $decoded = json_decode((string)$progress->watchedsegments, true);
            if (is_array($decoded)) {
                foreach (array_slice($decoded, 0, 50) as $segment) {
                    if (is_array($segment) && count($segment) === 2 &&
                            is_numeric($segment[0]) && is_numeric($segment[1])) {
                        $segments[] = [(float)$segment[0], (float)$segment[1]];
                    }
                }
            }
        }
        return self::summary($activity, $userid) + [
            "questions_total" => count($questions),
            "offset" => $offset,
            "limit" => $limit,
            "has_more" => $offset + $limit < count($questions),
            "responses" => $responses,
            "watched_segments_first_50" => $segments,
            "state_hash" => self::state_hash($activity, $userid),
        ];
    }

    /**
     * Load a saved learner response for a diagnostic question.
     *
     * @param stdClass|null $response Persisted submitted response.
     * @return array|null
     */
    private static function response(?stdClass $response): ?array {
        if ($response === null) {
            return null;
        }
        return [
            "text" => substr((string)$response->answertext, 0, 4000),
            "text_truncated" => strlen((string)$response->answertext) > 4000,
            "start_seconds" => (float)$response->starttime,
            "end_seconds" => (float)$response->endtime,
            "score_percent" => $response->scoreavailable ? (float)$response->score : null,
        ];
    }

    /**
     * Include every persisted response in the hash so confirmation cannot reset changed data.
     *
     * @param stdClass $activity Activity.
     * @param int $userid Learner.
     * @return string
     */
    public static function state_hash(stdClass $activity, int $userid): string {
        global $DB;
        $manager = new diagnostic_manager();
        $signature = [(int)$activity->id, $userid];
        foreach ([diagnostic_manager::STAGE_INITIAL, diagnostic_manager::STAGE_FINAL] as $stage) {
            $attempt = $manager->get_attempt((int)$activity->id, $userid, $stage);
            $signature[] = $attempt ? [
                (int)$attempt->id, (int)$attempt->submitted, (int)$attempt->timemodified,
                (int)$attempt->timesubmitted, (float)$attempt->score,
                (int)$attempt->changedcount,
            ] : null;
            if ($attempt) {
                $answers = $manager->responses((int)$attempt->id);
                ksort($answers);
                foreach ($answers as $response) {
                    $signature[] = [
                        (int)$response->id, (int)$response->questionid,
                        (string)$response->answertext, (float)$response->starttime,
                        (float)$response->endtime, (int)$response->timemodified,
                    ];
                }
            }
        }
        $progress = $DB->get_record("videodiagnostic_progress", [
            "videodiagnosticid" => (int)$activity->id, "userid" => $userid,
        ]);
        $signature[] = $progress ? [
            (int)$progress->id, (int)$progress->timemodified,
            (string)$progress->watchedsegments, (float)$progress->percent,
            (float)$progress->lastposition,
        ] : null;
        return hash("sha256", json_encode($signature, JSON_THROW_ON_ERROR));
    }

    /**
     * Reject a changed state before destructive operations.
     *
     * @param stdClass $activity Activity.
     * @param int $userid Learner.
     * @param string $expected Expected state hash.
     * @return void
     */
    public static function require_state(stdClass $activity, int $userid, string $expected): void {
        if (!preg_match("/^[a-f0-9]{64}$/", $expected) ||
                !hash_equals(self::state_hash($activity, $userid), $expected)) {
            throw new moodle_exception("mcpstalereport", "mod_videodiagnostic");
        }
    }
}
