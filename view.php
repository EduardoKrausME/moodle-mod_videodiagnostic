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
 * view.php
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_videodiagnostic\diagnostic_manager;
use mod_videodiagnostic\event\course_module_viewed;
use mod_videodiagnostic\form\response_form;
use mod_videodiagnostic\video_helper;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videodiagnostic', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videodiagnostic', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodiagnostic:view', $context);

$PAGE->set_url('/mod/videodiagnostic/view.php', ['id' => $id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

$event = course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('videodiagnostic', $activity);
$event->trigger();

$manager = new diagnostic_manager();
$questions = $manager->questions($activity->id);
$initialattempt = $manager->get_attempt($activity->id, $USER->id, diagnostic_manager::STAGE_INITIAL);
$finalattempt = $manager->get_attempt($activity->id, $USER->id, diagnostic_manager::STAGE_FINAL);
$studyavailable = $manager->study_available($activity, $USER->id);

$stage = 0;
if (!$initialattempt || !$initialattempt->submitted) {
    $stage = diagnostic_manager::STAGE_INITIAL;
} else if ($studyavailable && $activity->allowpostattempt && (!$finalattempt || !$finalattempt->submitted)) {
    $stage = diagnostic_manager::STAGE_FINAL;
}

$form = null;
if ($stage && $questions) {
    $form = new response_form(null, ['questions' => $questions, 'stage' => $stage]);
    $form->set_data(['id' => $id, 'stage' => $stage]);
    if ($data = $form->get_data()) {
        $manager->submit($activity, $cm, $USER->id, $stage, (array)$data);
        $message = $stage === diagnostic_manager::STAGE_INITIAL
            ? get_string('initialsaved', 'mod_videodiagnostic')
            : get_string('postsaved', 'mod_videodiagnostic');
        redirect($PAGE->url, $message);
    }
}

$progress = $DB->get_record('videodiagnostic_progress', [
    'videodiagnosticid' => $activity->id,
    'userid' => $USER->id,
]);
$percent = $progress ? (float)$progress->percent : 0.0;
$lastposition = $progress ? (float)$progress->lastposition : 0.0;
$player = video_helper::player_data($activity, $context, false);

$initialresponses = $initialattempt ? $manager->responses($initialattempt->id) : [];
$finalresponses = $finalattempt ? $manager->responses($finalattempt->id) : [];

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name));
if (trim((string)$activity->intro) !== '') {
    echo $OUTPUT->box(format_module_intro('videodiagnostic', $activity, $cm->id), 'generalbox mod_introbox');
}

if (has_capability('mod/videodiagnostic:managequestions', $context) || has_capability('mod/videodiagnostic:viewreport', $context)) {
    $buttons = [];
    if (has_capability('mod/videodiagnostic:managequestions', $context)) {
        $buttons[] = html_writer::link(
            new moodle_url('/mod/videodiagnostic/manage.php', ['id' => $id]),
            get_string('managequestions', 'mod_videodiagnostic'),
            ['class' => 'btn btn-secondary']
        );
    }
    if (has_capability('mod/videodiagnostic:viewreport', $context)) {
        $buttons[] = html_writer::link(
            new moodle_url('/mod/videodiagnostic/report.php', ['id' => $id]),
            get_string('reports', 'mod_videodiagnostic'),
            ['class' => 'btn btn-secondary']
        );
    }
    echo html_writer::div(implode(' ', $buttons), 'mb-3');
}

if (!empty($player['available'])) {
    $player += [
        'playerid' => 'videodiagnostic-main',
        'showprogress' => true,
        'progresslabel' => get_string('watchprogress', 'mod_videodiagnostic'),
        'percent' => $percent,
        'percentformatted' => format_float($percent, 1) . '%',
    ];
    echo $OUTPUT->render_from_template('mod_videodiagnostic/player', $player);
    $PAGE->requires->js_call_amd('mod_videodiagnostic/tracker', 'init', [[
        'playerid' => 'videodiagnostic-main',
        'cmid' => $cm->id,
        'source' => $player['source'],
        'videoid' => $player['videoid'] ?? '',
        'track' => true,
        'capturemarkers' => true,
        'percent' => $percent,
        'lastposition' => $lastposition,
    ]]);
} else {
    echo $OUTPUT->notification(get_string('invalidvideo', 'mod_videodiagnostic'), 'warning');
}

if ($progress) {
    echo $OUTPUT->heading(get_string('watchtimeline', 'mod_videodiagnostic'), 4);
    echo $OUTPUT->render_from_template('mod_videodiagnostic/timeline', videodiagnostic_timeline_data($progress));
}

if (!$questions) {
    echo $OUTPUT->notification(get_string('noquestions', 'mod_videodiagnostic'), 'info');
} else if ($stage === diagnostic_manager::STAGE_INITIAL) {
    echo $OUTPUT->heading(get_string('initialdiagnosis', 'mod_videodiagnostic'), 3);
    echo html_writer::tag('p', get_string('initialinstructions', 'mod_videodiagnostic'));
    $form->display();
} else if ($initialattempt && $initialattempt->submitted) {
    echo $OUTPUT->heading(get_string('initialdiagnosis', 'mod_videodiagnostic'), 3);
    $table = new html_table();
    $table->head = [get_string('question', 'mod_videodiagnostic'), get_string('initialresponse', 'mod_videodiagnostic')];
    foreach ($questions as $question) {
        $table->data[] = [
            format_text($question->questiontext, $question->questionformat, ['context' => $context]),
            videodiagnostic_format_response($initialresponses[$question->id] ?? null, $question),
        ];
    }
    echo html_writer::table($table);
}

if ($initialattempt && $initialattempt->submitted) {
    echo $OUTPUT->heading(get_string('studymaterials', 'mod_videodiagnostic'), 3);
    if (!$activity->explanationpublished) {
        echo $OUTPUT->notification(get_string('studynotpublished', 'mod_videodiagnostic'), 'info');
    } else if (!$studyavailable) {
        echo $OUTPUT->notification(get_string('studylocked', 'mod_videodiagnostic'), 'info');
    } else {
        $explanationplayer = video_helper::player_data($activity, $context, true);
        if (!empty($explanationplayer['available'])) {
            $explanationplayer += [
                'playerid' => 'videodiagnostic-explanation',
                'showprogress' => false,
                'progresslabel' => '',
                'percent' => 0,
                'percentformatted' => '',
            ];
            echo $OUTPUT->render_from_template('mod_videodiagnostic/player', $explanationplayer);
            if (!empty($explanationplayer['youtube'])) {
                $PAGE->requires->js_call_amd('mod_videodiagnostic/tracker', 'init', [[
                    'playerid' => 'videodiagnostic-explanation',
                    'cmid' => $cm->id,
                    'source' => $explanationplayer['source'],
                    'videoid' => $explanationplayer['videoid'] ?? '',
                    'track' => false,
                    'capturemarkers' => false,
                    'percent' => 0,
                    'lastposition' => 0,
                ]]);
            }
        }
        foreach (['solution', 'teachercomments', 'materials'] as $field) {
            $content = videodiagnostic_format_editor_field($activity, $context, $field);
            if ($content !== '') {
                echo html_writer::div($content, 'card card-body mb-3 videodiagnostic-study-' . $field);
            }
        }
    }
}

if ($stage === diagnostic_manager::STAGE_FINAL && $studyavailable) {
    echo $OUTPUT->heading(get_string('poststudydiagnosis', 'mod_videodiagnostic'), 3);
    echo html_writer::tag('p', get_string('postinstructions', 'mod_videodiagnostic'));
    $form->display();
}

if ($initialattempt && $initialattempt->submitted && $finalattempt && $finalattempt->submitted) {
    echo $OUTPUT->heading(get_string('comparison', 'mod_videodiagnostic'), 3);
    $table = new html_table();
    $table->head = [
        get_string('question', 'mod_videodiagnostic'),
        get_string('initialresponse', 'mod_videodiagnostic'),
        get_string('finalresponse', 'mod_videodiagnostic'),
        get_string('change', 'mod_videodiagnostic'),
    ];
    foreach ($questions as $question) {
        $initial = $initialresponses[$question->id] ?? null;
        $final = $finalresponses[$question->id] ?? null;
        $changed = $final && $final->changedfrominitial;
        $table->data[] = [
            format_text($question->questiontext, $question->questionformat, ['context' => $context]),
            videodiagnostic_format_response($initial, $question),
            videodiagnostic_format_response($final, $question),
            $changed ? get_string('changed', 'mod_videodiagnostic') : get_string('unchanged', 'mod_videodiagnostic'),
        ];
    }
    echo html_writer::table($table);
    if ($initialattempt->scoreavailable || $finalattempt->scoreavailable) {
        $initialscore = $initialattempt->scoreavailable ?
            format_float($initialattempt->score, 1) . '%' :
            get_string('scoreunavailable', 'mod_videodiagnostic');
        $finalscore = $finalattempt->scoreavailable ?
            format_float($finalattempt->score, 1) . '%' :
            get_string('scoreunavailable', 'mod_videodiagnostic');
        echo html_writer::div(
            get_string('initialresult', 'mod_videodiagnostic') . ': ' . $initialscore . ' · ' .
            get_string('finalresult', 'mod_videodiagnostic') . ': ' . $finalscore,
            'alert alert-secondary'
        );
    }
}

echo $OUTPUT->footer();
