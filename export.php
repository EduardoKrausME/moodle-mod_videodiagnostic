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
 * export.php
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_videodiagnostic\diagnostic_manager;

$dataformat = optional_param('dataformat', 'csv', PARAM_ALPHANUMEXT);

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videodiagnostic', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videodiagnostic', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodiagnostic:exportreport', $context);

$manager = new diagnostic_manager();
$identityfields = \core_user\fields::get_identity_fields($context, false);
$userfields = array_unique(array_merge(
    ['id', 'firstname', 'lastname', 'firstnamephonetic', 'lastnamephonetic', 'middlename', 'alternatename'],
    $identityfields
));
$userfieldsql = implode(',', array_map(static fn(string $field): string => 'u.' . $field, $userfields));
$users = get_enrolled_users($context, '', 0, $userfieldsql, 'u.lastname,u.firstname');

$columns = ['student' => get_string('student', 'mod_videodiagnostic')];
foreach ($identityfields as $field) {
    $columns[$field] = \core_user\fields::get_display_name($field);
}
$columns += [
    'watchprogress' => get_string('watchprogress', 'mod_videodiagnostic'),
    'initialresult' => get_string('initialresult', 'mod_videodiagnostic'),
    'finalresult' => get_string('finalresult', 'mod_videodiagnostic'),
    'changedanswers' => get_string('changedanswers', 'mod_videodiagnostic'),
];

$rows = [];
foreach ($users as $user) {
    if (has_capability('mod/videodiagnostic:managequestions', $context, $user->id)) {
        continue;
    }
    $progress = $DB->get_record('videodiagnostic_progress', [
        'videodiagnosticid' => $activity->id,
        'userid' => $user->id,
    ]);
    $initial = $manager->get_attempt($activity->id, $user->id, diagnostic_manager::STAGE_INITIAL);
    $final = $manager->get_attempt($activity->id, $user->id, diagnostic_manager::STAGE_FINAL);

    $row = ['student' => fullname($user)];
    foreach ($identityfields as $field) {
        $row[$field] = $user->{$field} ?? '';
    }
    $row += [
        'watchprogress' => $progress ? format_float($progress->percent, 1) . '%' : '0%',
        'initialresult' => $initial && $initial->scoreavailable ? format_float($initial->score, 1) . '%' : '',
        'finalresult' => $final && $final->scoreavailable ? format_float($final->score, 1) . '%' : '',
        'changedanswers' => $final && $final->submitted ? (int)$final->changedcount : '',
    ];
    $rows[] = $row;
}

$filename = clean_filename('videodiagnostic-' . $activity->name . '-' . userdate(time(), '%Y%m%d'));
\core\dataformat::download_data($filename, $dataformat, $columns, new ArrayIterator($rows));
exit;
