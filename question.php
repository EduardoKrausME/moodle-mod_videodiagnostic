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
 * question.php
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_videodiagnostic\form\question_form;

$id = required_param('id', PARAM_INT);
$qid = optional_param('qid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('videodiagnostic', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videodiagnostic', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodiagnostic:managequestions', $context);

$PAGE->set_url('/mod/videodiagnostic/question.php', ['id' => $id, 'qid' => $qid]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('managequestions', 'mod_videodiagnostic'),
    new moodle_url('/mod/videodiagnostic/manage.php', ['id' => $id]));
$PAGE->navbar->add($qid ? get_string('editquestion', 'mod_videodiagnostic') : get_string('addquestion', 'mod_videodiagnostic'));

$question = null;
if ($qid) {
    $question = $DB->get_record('videodiagnostic_questions', ['id' => $qid, 'videodiagnosticid' => $activity->id], '*', MUST_EXIST);
}
$form = new question_form(null, ['context' => $context]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/videodiagnostic/manage.php', ['id' => $id]));
}
if ($data = $form->get_data()) {
    $editor = $data->questiontext_editor;
    (new \mod_videodiagnostic\question_writer())->save($activity, $qid, [
        "questiontext" => $editor["text"] ?? "",
        "questionformat" => $editor["format"] ?? FORMAT_HTML,
        "qtype" => $data->qtype,
        "options" => (string)($data->options ?? ""),
        "correctanswer" => (string)($data->correctanswer ?? ""),
        "expectedstart" => (float)($data->expectedstart ?? 0),
        "expectedend" => (float)($data->expectedend ?? 0),
        "tolerance" => (float)($data->tolerance ?? 0),
        "required" => !empty($data->required),
        "weight" => (float)($data->weight ?? 1),
    ]);
    redirect(
        new moodle_url("/mod/videodiagnostic/manage.php", ["id" => $id]),
        get_string($qid ? "questionupdated" : "questionadded", "mod_videodiagnostic")
    );
}

$defaults = ['id' => $id, 'qid' => $qid];
if ($question) {
    $defaults += [
        'qtype' => $question->qtype,
        'options' => implode("\n", json_decode((string)$question->optionsjson, true) ?: []),
        'correctanswer' => $question->correctanswer,
        'expectedstart' => $question->expectedstart,
        'expectedend' => $question->expectedend,
        'tolerance' => $question->tolerance,
        'required' => $question->required,
        'weight' => $question->weight,
        'questiontext_editor' => ['text' => $question->questiontext, 'format' => $question->questionformat],
    ];
}
$form->set_data($defaults);

echo $OUTPUT->header();
echo $OUTPUT->heading($qid ? get_string('editquestion', 'mod_videodiagnostic') : get_string('addquestion', 'mod_videodiagnostic'));
$form->display();
echo $OUTPUT->footer();
