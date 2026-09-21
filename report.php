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
 * report.php
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_videodiagnostic\diagnostic_manager;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videodiagnostic', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videodiagnostic', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodiagnostic:viewreport', $context);

$PAGE->set_url('/mod/videodiagnostic/report.php', ['id' => $id]);
$PAGE->set_title(get_string('report', 'mod_videodiagnostic') . ': ' . format_string($activity->name));
$PAGE->set_heading($course->fullname);

$manager = new diagnostic_manager();
$users = get_enrolled_users($context, '', 0,
    "u.id,u.firstname,u.lastname,u.email,u.picture,u.imagealt,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename",
    "u.lastname,u.firstname");

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'mod_videodiagnostic') . ': ' . format_string($activity->name));
if (has_capability('mod/videodiagnostic:exportreport', $context)) {
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/mod/videodiagnostic/export.php', ['id' => $id]),
            get_string('exportcsv', 'mod_videodiagnostic'),
            ['class' => 'btn btn-secondary']
        ),
        'mb-3'
    );
}

$table = new html_table();
$table->head = [
    get_string('student', 'mod_videodiagnostic'),
    get_string('watchprogress', 'mod_videodiagnostic'),
    get_string('initialresult', 'mod_videodiagnostic'),
    get_string('finalresult', 'mod_videodiagnostic'),
    get_string('changedanswers', 'mod_videodiagnostic'),
    get_string('status', 'mod_videodiagnostic'),
    get_string('actions'),
];

$count = 0;
foreach ($users as $user) {
    if (has_capability('mod/videodiagnostic:managequestions', $context, $user->id)) {
        continue;
    }
    $count++;
    $progress = $DB->get_record('videodiagnostic_progress', [
        'videodiagnosticid' => $activity->id,
        'userid' => $user->id,
    ]);
    $initial = $manager->get_attempt($activity->id, $user->id, diagnostic_manager::STAGE_INITIAL);
    $final = $manager->get_attempt($activity->id, $user->id, diagnostic_manager::STAGE_FINAL);
    $status = get_string('notstarted', 'mod_videodiagnostic');
    if ($initial && $initial->submitted) {
        $status = ($final && $final->submitted) || !$activity->allowpostattempt
            ? get_string('completed', 'mod_videodiagnostic')
            : get_string('inprogress', 'mod_videodiagnostic');
    } else if ($progress && $progress->percent > 0) {
        $status = get_string('inprogress', 'mod_videodiagnostic');
    }
    $initialresult = ($initial && $initial->scoreavailable)
        ? format_float($initial->score, 1) . '%'
        : (($initial && $initial->submitted) ? get_string('scoreunavailable', 'mod_videodiagnostic') : '-');
    $finalresult = ($final && $final->scoreavailable)
        ? format_float($final->score, 1) . '%'
        : (($final && $final->submitted) ? get_string('scoreunavailable', 'mod_videodiagnostic') : '-');
    $actions = [
        html_writer::link(
            new moodle_url('/mod/videodiagnostic/student.php', ['id' => $id, 'userid' => $user->id]),
            get_string('viewdetails', 'mod_videodiagnostic')
        ),
    ];
    if (has_capability('mod/videodiagnostic:resetresponses', $context)) {
        $actions[] = html_writer::link(
            new moodle_url('/mod/videodiagnostic/reset.php', ['id' => $id, 'userid' => $user->id, 'sesskey' => sesskey()]),
            get_string('reset', 'mod_videodiagnostic')
        );
    }
    $table->data[] = [
        fullname($user),
        $progress ? format_float($progress->percent, 1) . '%' : '0%',
        $initialresult,
        $finalresult,
        $final && $final->submitted ? (int)$final->changedcount : '-',
        $status,
        implode(' · ', $actions),
    ];
}

if (!$count) {
    echo $OUTPUT->notification(get_string('nostudents', 'mod_videodiagnostic'), 'info');
} else {
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
