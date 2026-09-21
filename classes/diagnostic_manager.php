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

namespace mod_videodiagnostic;

use completion_info;
use moodle_exception;
use stdClass;

/**
 * Saves diagnostic attempts and compares initial and post-study answers.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diagnostic_manager {
    /** @var int */
    public const STAGE_INITIAL = 1;

    /** @var int */
    public const STAGE_FINAL = 2;

    /**
     * Returns ordered activity questions.
     *
     * @param int $activityid Activity id.
     * @return array
     */
    public function questions(int $activityid): array {
        global $DB;
        return $DB->get_records('videodiagnostic_questions', ['videodiagnosticid' => $activityid], 'sortorder ASC, id ASC');
    }

    /**
     * Loads one attempt.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @param int $stage Stage.
     * @return stdClass|null
     */
    public function get_attempt(int $activityid, int $userid, int $stage): ?stdClass {
        global $DB;
        $attempt = $DB->get_record('videodiagnostic_attempts', [
            'videodiagnosticid' => $activityid,
            'userid' => $userid,
            'stage' => $stage,
        ]);
        return $attempt ?: null;
    }

    /**
     * Returns responses keyed by question id.
     *
     * @param int $attemptid Attempt id.
     * @return array
     */
    public function responses(int $attemptid): array {
        global $DB;
        $records = $DB->get_records('videodiagnostic_responses', ['attemptid' => $attemptid]);
        $byquestion = [];
        foreach ($records as $record) {
            $byquestion[$record->questionid] = $record;
        }
        return $byquestion;
    }

    /**
     * Saves a submitted stage.
     *
     * @param stdClass $activity Activity.
     * @param stdClass $cm Course module.
     * @param int $userid User.
     * @param int $stage Stage.
     * @param array $values Submitted values indexed by element name.
     * @return stdClass Attempt.
     */
    public function submit(stdClass $activity, stdClass $cm, int $userid, int $stage, array $values): stdClass {
        global $DB;

        if (!in_array($stage, [self::STAGE_INITIAL, self::STAGE_FINAL], true)) {
            throw new moodle_exception('invalidstage', 'mod_videodiagnostic');
        }
        if ($stage === self::STAGE_FINAL && (!$activity->allowpostattempt || !$this->study_available($activity, $userid))) {
            throw new moodle_exception('studycontentnotavailable', 'mod_videodiagnostic');
        }

        $attempt = $this->get_attempt($activity->id, $userid, $stage);
        if ($attempt && $attempt->submitted) {
            throw new moodle_exception('alreadysubmitted', 'mod_videodiagnostic');
        }
        $now = time();
        if (!$attempt) {
            $attempt = (object)[
                'videodiagnosticid' => $activity->id,
                'userid' => $userid,
                'stage' => $stage,
                'submitted' => 0,
                'scoreavailable' => 0,
                'score' => 0,
                'changedcount' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
                'timesubmitted' => 0,
            ];
            $attempt->id = $DB->insert_record('videodiagnostic_attempts', $attempt);
        }

        $initialresponses = [];
        if ($stage === self::STAGE_FINAL) {
            $initialattempt = $this->get_attempt($activity->id, $userid, self::STAGE_INITIAL);
            if ($initialattempt) {
                $initialresponses = $this->responses($initialattempt->id);
            }
        }

        $totalweight = 0.0;
        $weightedscore = 0.0;
        $scoreavailable = false;
        $changedcount = 0;
        foreach ($this->questions($activity->id) as $question) {
            $response = $this->response_from_values($attempt->id, $question, $values);
            [$responsescore, $responsescoreavailable] = $this->score_response($question, $response);
            $response->score = $responsescore;
            $response->scoreavailable = $responsescoreavailable ? 1 : 0;
            $response->changedfrominitial = 0;
            if ($stage === self::STAGE_FINAL && isset($initialresponses[$question->id])) {
                $response->changedfrominitial = $this->responses_differ($initialresponses[$question->id], $response) ? 1 : 0;
                $changedcount += $response->changedfrominitial;
            }
            $existing = $DB->get_record('videodiagnostic_responses', [
                'attemptid' => $attempt->id,
                'questionid' => $question->id,
            ]);
            if ($existing) {
                $response->id = $existing->id;
                $response->timecreated = $existing->timecreated;
                $DB->update_record('videodiagnostic_responses', $response);
            } else {
                $response->timecreated = $now;
                $response->id = $DB->insert_record('videodiagnostic_responses', $response);
            }
            if ($responsescoreavailable) {
                $weight = max(0.01, (float)$question->weight);
                $totalweight += $weight;
                $weightedscore += $responsescore * $weight;
                $scoreavailable = true;
            }
        }

        $attempt->submitted = 1;
        $attempt->scoreavailable = $scoreavailable ? 1 : 0;
        $attempt->score = $totalweight > 0 ? round($weightedscore / $totalweight, 2) : 0;
        $attempt->changedcount = $changedcount;
        $attempt->timemodified = $now;
        $attempt->timesubmitted = $now;
        $DB->update_record('videodiagnostic_attempts', $attempt);

        $completion = new completion_info(get_course($activity->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
        }
        return $attempt;
    }

    /**
     * Whether study content is available to the user.
     *
     * @param stdClass $activity Activity.
     * @param int $userid User id.
     * @return bool
     */
    public function study_available(stdClass $activity, int $userid): bool {
        if (empty($activity->explanationpublished)) {
            return false;
        }
        if (empty($activity->releaseafterinitial)) {
            return true;
        }
        $attempt = $this->get_attempt($activity->id, $userid, self::STAGE_INITIAL);
        return $attempt && !empty($attempt->submitted);
    }

    /**
     * Deletes all per-user diagnostic data for one activity.
     *
     * @param stdClass $activity Activity.
     * @param stdClass $cm Course module.
     * @param int $userid User id.
     * @return void
     */
    public function reset_user(stdClass $activity, stdClass $cm, int $userid): void {
        global $DB;
        $attemptids = $DB->get_fieldset_select(
            'videodiagnostic_attempts',
            'id',
            'videodiagnosticid = ? AND userid = ?',
            [$activity->id, $userid]
        );
        if ($attemptids) {
            [$insql, $params] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_QM);
            $DB->delete_records_select('videodiagnostic_responses', "attemptid {$insql}", $params);
        }
        $DB->delete_records('videodiagnostic_attempts', ['videodiagnosticid' => $activity->id, 'userid' => $userid]);
        $DB->delete_records('videodiagnostic_progress', ['videodiagnosticid' => $activity->id, 'userid' => $userid]);
        $completion = new completion_info(get_course($activity->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_INCOMPLETE, $userid);
        }
    }

    /**
     * Creates a response record from dynamic form values.
     *
     * @param int $attemptid Attempt id.
     * @param stdClass $question Question.
     * @param array $values Form values.
     * @return stdClass
     */
    private function response_from_values(int $attemptid, stdClass $question, array $values): stdClass {
        $prefix = 'q' . $question->id;
        $answer = '';
        $start = 0.0;
        $end = 0.0;
        if (in_array($question->qtype, ['text', 'choice'], true)) {
            $answer = trim((string)($values[$prefix] ?? ''));
        } else if ($question->qtype === 'marker') {
            $start = (float)($values[$prefix . '_start'] ?? 0);
            $end = $start;
        } else if ($question->qtype === 'interval') {
            $start = (float)($values[$prefix . '_start'] ?? 0);
            $end = (float)($values[$prefix . '_end'] ?? 0);
            if ($end < $start) {
                [$start, $end] = [$end, $start];
            }
        }
        return (object)[
            'attemptid' => $attemptid,
            'questionid' => $question->id,
            'answertext' => $answer,
            'starttime' => round(max(0, $start), 3),
            'endtime' => round(max(0, $end), 3),
            'score' => 0,
            'scoreavailable' => 0,
            'changedfrominitial' => 0,
            'timemodified' => time(),
        ];
    }

    /**
     * Calculates optional diagnostic score from configured criteria.
     *
     * @param stdClass $question Question.
     * @param stdClass $response Response.
     * @return array{0:float,1:bool}
     */
    private function score_response(stdClass $question, stdClass $response): array {
        if ($question->qtype === 'text') {
            $expected = $this->normalise_text((string)$question->correctanswer);
            if ($expected === '') {
                return [0, false];
            }
            return [$this->normalise_text((string)$response->answertext) === $expected ? 100 : 0, true];
        }
        if ($question->qtype === 'choice') {
            $expected = trim((string)$question->correctanswer);
            if ($expected === '') {
                return [0, false];
            }
            return [trim((string)$response->answertext) === $expected ? 100 : 0, true];
        }
        if ($question->qtype === 'marker') {
            $expectedstart = (float)$question->expectedstart;
            $expectedend = (float)$question->expectedend;
            if ($expectedend <= 0) {
                $expectedend = $expectedstart;
            }
            if ($expectedstart <= 0 && $expectedend <= 0) {
                return [0, false];
            }
            $tolerance = max(0, (float)$question->tolerance);
            $value = (float)$response->starttime;
            $correct = $value >= ($expectedstart - $tolerance) && $value <= ($expectedend + $tolerance);
            return [$correct ? 100 : 0, true];
        }
        if ($question->qtype === 'interval') {
            $expectedstart = (float)$question->expectedstart;
            $expectedend = (float)$question->expectedend;
            if ($expectedend <= $expectedstart) {
                return [0, false];
            }
            $tolerance = max(0, (float)$question->tolerance);
            $start = (float)$response->starttime;
            $end = (float)$response->endtime;
            $correct = $start <= ($expectedstart + $tolerance) && $end >= ($expectedend - $tolerance);
            return [$correct ? 100 : 0, true];
        }
        return [0, false];
    }

    /**
     * Compares two responses.
     *
     * @param stdClass $initial Initial response.
     * @param stdClass $final Final response.
     * @return bool
     */
    private function responses_differ(stdClass $initial, stdClass $final): bool {
        if ($this->normalise_text((string)$initial->answertext) !== $this->normalise_text((string)$final->answertext)) {
            return true;
        }
        if (abs((float)$initial->starttime - (float)$final->starttime) > 0.5) {
            return true;
        }
        return abs((float)$initial->endtime - (float)$final->endtime) > 0.5;
    }

    /**
     * Normalises text for exact diagnostic comparison.
     *
     * @param string $text Text.
     * @return string
     */
    private function normalise_text(string $text): string {
        $text = trim(\core_text::strtolower(strip_tags($text)));
        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }
}
