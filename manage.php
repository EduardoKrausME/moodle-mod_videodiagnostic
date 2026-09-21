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
 * manage.php
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$qid = optional_param('qid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videodiagnostic', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videodiagnostic', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodiagnostic:managequestions', $context);

$PAGE->set_url('/mod/videodiagnostic/manage.php', ['id' => $id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading($course->fullname);

if ($action && $qid) {
    require_sesskey();
    $question = $DB->get_record('videodiagnostic_questions', ['id' => $qid, 'videodiagnosticid' => $activity->id], '*', MUST_EXIST);
    if ($action === 'delete') {
        if (optional_param('confirm', 0, PARAM_BOOL)) {
            $DB->delete_records('videodiagnostic_responses', ['questionid' => $qid]);
            $DB->delete_records('videodiagnostic_questions', ['id' => $qid]);
            redirect($PAGE->url, get_string('questiondeleted', 'mod_videodiagnostic'));
        }
        $confirmurl = new moodle_url($PAGE->url, [
            'action' => 'delete', 'qid' => $qid, 'confirm' => 1, 'sesskey' => sesskey(),
        ]);
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('deletequestionconfirm', 'mod_videodiagnostic'),
            $confirmurl,
            $PAGE->url
        );
        echo $OUTPUT->footer();
        exit;
    }
    if (in_array($action, ['up', 'down'], true)) {
        $operator = $action === 'up' ? '<' : '>';
        $direction = $action === 'up' ? 'DESC' : 'ASC';
        $other = $DB->get_record_sql(
            "SELECT * FROM {videodiagnostic_questions}
              WHERE videodiagnosticid = :activityid AND sortorder {$operator} :sortorder
           ORDER BY sortorder {$direction}, id {$direction}",
            ['activityid' => $activity->id, 'sortorder' => $question->sortorder],
            IGNORE_MULTIPLE
        );
        if ($other) {
            $old = $question->sortorder;
            $question->sortorder = $other->sortorder;
            $other->sortorder = $old;
            $DB->update_record('videodiagnostic_questions', $question);
            $DB->update_record('videodiagnostic_questions', $other);
        }
        redirect($PAGE->url);
    }
}

$questions = $DB->get_records('videodiagnostic_questions', ['videodiagnosticid' => $activity->id], 'sortorder ASC, id ASC');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managequestions', 'mod_videodiagnostic'));
echo $OUTPUT->single_button(new moodle_url('/mod/videodiagnostic/question.php',
    ['id' => $id]), get_string('addquestion', 'mod_videodiagnostic'), 'get');
if (!$questions) {
    echo $OUTPUT->notification(get_string('noquestions', 'mod_videodiagnostic'), 'info');
} else {
    $table = new html_table();
    $table->head = ['#', get_string('questiontext', 'mod_videodiagnostic'),
        get_string('questiontype', 'mod_videodiagnostic'), get_string('actions')];
    $index = 0;
    foreach ($questions as $question) {
        $index++;
        $actions = [];
        $actions[] = html_writer::link(
            new moodle_url('/mod/videodiagnostic/question.php', ['id' => $id, 'qid' => $question->id]),
            get_string('edit')
        );
        $actions[] = html_writer::link(
            new moodle_url($PAGE->url, ['action' => 'up', 'qid' => $question->id, 'sesskey' => sesskey()]),
            get_string('moveup', 'mod_videodiagnostic')
        );
        $actions[] = html_writer::link(
            new moodle_url($PAGE->url, ['action' => 'down', 'qid' => $question->id, 'sesskey' => sesskey()]),
            get_string('movedown', 'mod_videodiagnostic')
        );
        $actions[] = html_writer::link(
            new moodle_url($PAGE->url, ['action' => 'delete', 'qid' => $question->id, 'sesskey' => sesskey()]),
            get_string('delete')
        );
        $table->data[] = [
            $index,
            format_text($question->questiontext, $question->questionformat, ['context' => $context]),
            get_string('qtype' . $question->qtype, 'mod_videodiagnostic'),
            implode(' · ', $actions),
        ];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
