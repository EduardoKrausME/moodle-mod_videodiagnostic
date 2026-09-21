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
    $now = time();
    $editor = $data->questiontext_editor;
    $options = preg_split('/\R/u', trim((string)$data->options)) ?: [];
    $options = array_values(array_filter(array_map('trim', $options), static fn(string $v): bool => $v !== ''));
    $record = (object)[
        'videodiagnosticid' => $activity->id,
        'questiontext' => $editor['text'] ?? '',
        'questionformat' => $editor['format'] ?? FORMAT_HTML,
        'qtype' => $data->qtype,
        'optionsjson' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'correctanswer' => $data->correctanswer ?? '',
        'expectedstart' => (float)($data->expectedstart ?? 0),
        'expectedend' => (float)($data->expectedend ?? 0),
        'tolerance' => max(0, (float)($data->tolerance ?? 0)),
        'required' => empty($data->required) ? 0 : 1,
        'weight' => max(0.01, (float)($data->weight ?? 1)),
        'timemodified' => $now,
    ];
    if ($qid) {
        $record->id = $qid;
        $DB->update_record('videodiagnostic_questions', $record);
        $message = get_string('questionupdated', 'mod_videodiagnostic');
    } else {
        $maxsort = (int)$DB->get_field_sql(
            'SELECT COALESCE(MAX(sortorder), 0) FROM {videodiagnostic_questions} WHERE videodiagnosticid = ?',
            [$activity->id]
        );
        $record->sortorder = $maxsort + 10;
        $record->timecreated = $now;
        $DB->insert_record('videodiagnostic_questions', $record);
        $message = get_string('questionadded', 'mod_videodiagnostic');
    }
    redirect(new moodle_url('/mod/videodiagnostic/manage.php', ['id' => $id]), $message);
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
