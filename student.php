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
 * student.php
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_videodiagnostic\diagnostic_manager;

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$cm = get_coursemodule_from_id('videodiagnostic', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videodiagnostic', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodiagnostic:viewreport', $context);
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

$PAGE->set_url('/mod/videodiagnostic/student.php', ['id' => $id, 'userid' => $userid]);
$PAGE->set_title(fullname($user) . ' - ' . format_string($activity->name));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('report', 'mod_videodiagnostic'), new moodle_url('/mod/videodiagnostic/report.php', ['id' => $id]));
$PAGE->navbar->add(fullname($user));

$manager = new diagnostic_manager();
$questions = $manager->questions($activity->id);
$initial = $manager->get_attempt($activity->id, $userid, diagnostic_manager::STAGE_INITIAL);
$final = $manager->get_attempt($activity->id, $userid, diagnostic_manager::STAGE_FINAL);
$initialresponses = $initial ? $manager->responses($initial->id) : [];
$finalresponses = $final ? $manager->responses($final->id) : [];
$progress = $DB->get_record('videodiagnostic_progress', ['videodiagnosticid' => $activity->id, 'userid' => $userid]);

echo $OUTPUT->header();
echo $OUTPUT->heading(fullname($user));
$summary = new html_table();
$summary->data = [
    [
        get_string('watchprogress', 'mod_videodiagnostic'),
        $progress ? format_float($progress->percent, 1) . '%' : '0%',
        ],
    [
        get_string('lastposition', 'mod_videodiagnostic'),
        $progress ? format_float($progress->lastposition, 1) . 's' : '-',
        ],
    [
        get_string('initialresult', 'mod_videodiagnostic'),
        $initial && $initial->scoreavailable ? format_float($initial->score) . '%' :
            get_string('scoreunavailable', 'mod_videodiagnostic'),
        ],
    [
        get_string('finalresult', 'mod_videodiagnostic'),
        $final && $final->scoreavailable ? format_float($final->score) . '%' :
            get_string('scoreunavailable', 'mod_videodiagnostic'),
        ],
    [
        get_string('changedanswers', 'mod_videodiagnostic'),
        $final && $final->submitted ? (int)$final->changedcount : '-',
        ],
];
echo html_writer::table($summary);
if ($progress) {
    echo $OUTPUT->heading(get_string('watchtimeline', 'mod_videodiagnostic'), 3);
    echo $OUTPUT->render_from_template('mod_videodiagnostic/timeline', videodiagnostic_timeline_data($progress));
}

$table = new html_table();
$table->head = [
    get_string('question', 'mod_videodiagnostic'),
    get_string('initialresponse', 'mod_videodiagnostic'),
    get_string('finalresponse', 'mod_videodiagnostic'),
    get_string('change', 'mod_videodiagnostic'),
];
foreach ($questions as $question) {
    $initialresponse = $initialresponses[$question->id] ?? null;
    $finalresponse = $finalresponses[$question->id] ?? null;
    $table->data[] = [
        format_text($question->questiontext, $question->questionformat, ['context' => $context]),
        videodiagnostic_format_response($initialresponse, $question),
        videodiagnostic_format_response($finalresponse, $question),
        $finalresponse
            ? ($finalresponse->changedfrominitial ?
            get_string('changed', 'mod_videodiagnostic') :
            get_string('unchanged', 'mod_videodiagnostic'))
            : '-',
    ];
}
echo html_writer::table($table);

if (has_capability('mod/videodiagnostic:resetresponses', $context)) {
    echo $OUTPUT->single_button(
        new moodle_url('/mod/videodiagnostic/reset.php', ['id' => $id, 'userid' => $userid, 'sesskey' => sesskey()]),
        get_string('reset', 'mod_videodiagnostic'),
        'get'
    );
}
echo $OUTPUT->footer();
