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
 * reset.php
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
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$cm = get_coursemodule_from_id('videodiagnostic', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videodiagnostic', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videodiagnostic:resetresponses', $context);
require_sesskey();
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$PAGE->set_url('/mod/videodiagnostic/reset.php', ['id' => $id, 'userid' => $userid]);
$PAGE->set_title(get_string('reset', 'mod_videodiagnostic'));
$PAGE->set_heading($course->fullname);

if ($confirm) {
    (new diagnostic_manager())->reset_user($activity, $cm, $userid);
    redirect(new moodle_url('/mod/videodiagnostic/report.php', ['id' => $id]), get_string('resetdone', 'mod_videodiagnostic'));
}

echo $OUTPUT->header();
$yes = new moodle_url('/mod/videodiagnostic/reset.php', [
    'id' => $id,
    'userid' => $userid,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);
$no = new moodle_url('/mod/videodiagnostic/student.php', ['id' => $id, 'userid' => $userid]);
echo $OUTPUT->confirm(get_string('resetconfirm', 'mod_videodiagnostic') . ' ' . fullname($user), $yes, $no);
echo $OUTPUT->footer();
