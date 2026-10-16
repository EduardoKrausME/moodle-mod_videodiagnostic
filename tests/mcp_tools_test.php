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
 * PHPUnit coverage for the optional Video Diagnostic MCP integration.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videodiagnostic;

use advanced_testcase;
use context_module;
use mod_videodiagnostic\mcp\access;
use mod_videodiagnostic\mcp\get_student;
use mod_videodiagnostic\mcp\list_students;
use mod_videodiagnostic\mcp\list_questions;
use mod_videodiagnostic\mcp\report_data;
use mod_videodiagnostic\mcp\reset_student;
use mod_videodiagnostic\mcp\save_question;

/**
 * Ensure previews remain safe and reports never mutate a learner's progress.
 *
 * @covers \mod_videodiagnostic\question_writer
 * @covers \mod_videodiagnostic\mcp\access
 * @covers \mod_videodiagnostic\mcp\report_data
 * @covers \mod_videodiagnostic\mcp\list_questions
 * @covers \mod_videodiagnostic\mcp\get_student
 * @covers \mod_videodiagnostic\mcp\list_students
 * @covers \mod_videodiagnostic\mcp\save_question
 * @covers \mod_videodiagnostic\mcp\reset_student
 */
final class mcp_tools_test extends advanced_testcase {
    /**
     * @return array Course, activity and course module.
     */
    private function fixture(): array {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module("videodiagnostic", [
            "course" => $course->id,
            "name" => "Video diagnostic",
            "videosource" => "url",
            "videourl" => "https://example.com/video.mp4",
            "explanationpublished" => 1,
            "allowpostattempt" => 1,
            "releaseafterinitial" => 1,
        ]);
        $cm = get_coursemodule_from_instance("videodiagnostic", $activity->id, $course->id, false, MUST_EXIST);
        return [$course, $activity, $cm];
    }

    /**
     * @return void
     */
    private function require_mcp(): void {
        if (!class_exists(\local_mcp\security\authenticated_identity::class)) {
            $this->markTestSkipped("Optional local_mcp is not installed.");
        }
    }

    /**
     * The single question writer supports both create and update without losing record identity.
     *
     * @return void
     */
    public function test_question_writer_and_stale_hash(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $cm] = $this->fixture();
        $writer = new question_writer();
        $initialhash = $writer->state_hash((int)$activity->id);
        $questionid = $writer->save($activity, 0, [
            "qtype" => "choice", "questiontext" => "<p>Which concept?</p>",
            "options" => ["Knowledge", "Guess"], "correctanswer" => "Knowledge",
            "required" => true, "weight" => 1,
        ]);
        $this->assertGreaterThan(0, $questionid);
        $this->assertNotSame($initialhash, $writer->state_hash((int)$activity->id));
        $writer->save($activity, $questionid, [
            "qtype" => "choice", "questiontext" => "<p>Which concept is correct?</p>",
            "options" => ["Knowledge", "Guess"], "correctanswer" => "Knowledge",
            "required" => true, "weight" => 1,
        ]);
        $this->assertSame(1, $DB->count_records("videodiagnostic_questions", [
            "videodiagnosticid" => $activity->id,
        ]));
        $this->expectException(\moodle_exception::class);
        $writer->require_state((int)$activity->id, $initialhash);
    }

    /**
     * A learner's submitted answers must be returned without creating new progress records.
     *
     * @return void
     */
    public function test_report_data_reads_without_writing(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $cm] = $this->fixture();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$student->id, (int)$course->id);
        $writer = new question_writer();
        $questionid = $writer->save($activity, 0, [
            "qtype" => "text", "questiontext" => "<p>What did you observe?</p>",
            "correctanswer" => "a change", "weight" => 1,
        ]);
        $manager = new diagnostic_manager();
        $manager->submit($activity, $cm, (int)$student->id, diagnostic_manager::STAGE_INITIAL, [
            "q" . $questionid => "no change",
        ]);
        $manager->submit($activity, $cm, (int)$student->id, diagnostic_manager::STAGE_FINAL, [
            "q" . $questionid => "a change",
        ]);
        $before = $DB->count_records("videodiagnostic_progress");
        $first = report_data::detail($activity, (int)$student->id, 0, 20);
        $this->assertSame("completed", $first["status"]);
        $this->assertTrue($first["initial"]["submitted"]);
        $this->assertTrue($first["post_study"]["submitted"]);
        $this->assertSame(1, $first["changed_answers"]);
        $this->assertCount(1, $first["responses"]);
        $this->assertTrue($first["responses"][0]["changed"]);
        $this->assertSame($before, $DB->count_records("videodiagnostic_progress"));
        $this->assertSame($first["state_hash"], report_data::state_hash($activity, (int)$student->id));
    }

    /**
     * MCP write previews do not create questions or erase saved learner attempts.
     *
     * @return void
     */
    public function test_write_previews_are_side_effect_free(): void {
        global $DB;
        $this->resetAfterTest();
        $this->require_mcp();
        [$course, $activity, $cm] = $this->fixture();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id);
        $identity = new \local_mcp\security\authenticated_identity(
            (int)$this->getUser()->id, "oauth", ["read", "write"]
        );
        $before = $DB->count_records("videodiagnostic_questions");
        $questions = (new list_questions())->execute(["cmid" => $cm->id], $identity);
        $preview = (new save_question())->preview([
            "cmid" => $cm->id, "expected_state_hash" => $questions["state_hash"],
            "qtype" => "text", "questiontext" => "What happened?",
        ], $identity);
        $this->assertSame("create", $preview["action"]);
        $this->assertSame($before, $DB->count_records("videodiagnostic_questions"));
        $beforeattempts = $DB->count_records("videodiagnostic_attempts");
        $details = (new get_student())->execute([
            "cmid" => $cm->id, "userid" => $user->id,
        ], $identity);
        $resetpreview = (new reset_student())->preview([
            "cmid" => $cm->id, "userid" => $user->id,
            "expected_state_hash" => $details["state_hash"],
        ], $identity);
        $this->assertTrue($resetpreview["cannot_undo"]);
        $this->assertSame($beforeattempts, $DB->count_records("videodiagnostic_attempts"));
    }

    /**
     * The connected identity, not the PHP global administrator, controls report access.
     *
     * @return void
     */
    public function test_unprivileged_user_cannot_read_reports(): void {
        $this->resetAfterTest();
        $this->require_mcp();
        [$course, $activity, $cm] = $this->fixture();
        $other = $this->getDataGenerator()->create_user();
        $identity = new \local_mcp\security\authenticated_identity(
            (int)$other->id, "oauth", ["read"]
        );
        $this->expectException(\required_capability_exception::class);
        (new list_students())->execute(["cmid" => $cm->id], $identity);
    }

    /**
     * The native manager should remove the attempts and update completion after MCP reset.
     *
     * @return void
     */
    public function test_reset_uses_existing_manager(): void {
        global $DB;
        $this->resetAfterTest();
        $this->require_mcp();
        [$course, $activity, $cm] = $this->fixture();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id);
        $manager = new diagnostic_manager();
        $manager->submit($activity, $cm, (int)$user->id, diagnostic_manager::STAGE_INITIAL, []);
        $this->assertSame(1, $DB->count_records("videodiagnostic_attempts", [
            "videodiagnosticid" => $activity->id, "userid" => $user->id,
        ]));
        $identity = new \local_mcp\security\authenticated_identity(
            (int)$this->getUser()->id, "oauth", ["read", "write"]
        );
        $hash = report_data::state_hash($activity, (int)$user->id);
        $result = (new reset_student())->execute([
            "cmid" => $cm->id, "userid" => $user->id, "expected_state_hash" => $hash,
        ], $identity);
        $this->assertTrue($result["reset"]);
        $this->assertSame(0, $DB->count_records("videodiagnostic_attempts", [
            "videodiagnosticid" => $activity->id, "userid" => $user->id,
        ]));
    }

    /**
     * A teacher restricted to separate groups cannot access a learner in another group.
     *
     * @return void
     */
    public function test_group_boundary_rejects_foreign_learner(): void {
        global $DB;
        $this->resetAfterTest();
        $this->require_mcp();
        [$course, $activity, $cm] = $this->fixture();
        $teacher = $this->getDataGenerator()->create_user();
        $learner = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record("role", ["shortname" => "editingteacher"], "id", MUST_EXIST);
        $this->getDataGenerator()->enrol_user((int)$teacher->id, (int)$course->id, (int)$teacherrole->id);
        $this->getDataGenerator()->enrol_user((int)$learner->id, (int)$course->id);
        $groupa = $this->getDataGenerator()->create_group(["courseid" => $course->id]);
        $groupb = $this->getDataGenerator()->create_group(["courseid" => $course->id]);
        groups_add_member($groupa, $teacher);
        groups_add_member($groupb, $learner);
        $DB->set_field("course_modules", "groupmode", SEPARATEGROUPS, ["id" => $cm->id]);
        role_change_permission((int)$teacherrole->id, context_module::instance($cm->id),
            "moodle/site:accessallgroups", CAP_PREVENT);
        rebuild_course_cache((int)$course->id, true);
        $cm = get_coursemodule_from_id("videodiagnostic", $cm->id, 0, false, MUST_EXIST);
        $identity = new \local_mcp\security\authenticated_identity(
            (int)$teacher->id, "oauth", ["read"]
        );
        $this->assertFalse(access::allowed_group($cm, context_module::instance($cm->id),
            (int)$learner->id, $identity));
        $this->expectException(\moodle_exception::class);
        (new get_student())->execute(["cmid" => $cm->id, "userid" => $learner->id], $identity);
    }

}
