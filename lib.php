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
 * Core callbacks for Video Diagnostic.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Declares supported Moodle features.
 *
 * @param string $feature Feature constant.
 * @return bool|string|null
 */
function videodiagnostic_supports(string $feature): bool|string|null {
    return match ($feature) {
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_ASSESSMENT,
        default => null,
    };
}

/**
 * Adds a Video Diagnostic instance.
 *
 * @param stdClass $data Form data.
 * @param mod_videodiagnostic_mod_form|null $mform Form instance.
 * @return int New instance id.
 */
function videodiagnostic_add_instance(stdClass $data, ?mod_videodiagnostic_mod_form $mform = null): int {
    global $DB;

    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    videodiagnostic_prepare_editor_fields($data);
    $id = $DB->insert_record('videodiagnostic', $data);
    $data->id = $id;
    videodiagnostic_save_files_and_editors($data);
    return $id;
}

/**
 * Updates a Video Diagnostic instance.
 *
 * @param stdClass $data Form data.
 * @param mod_videodiagnostic_mod_form|null $mform Form instance.
 * @return bool
 */
function videodiagnostic_update_instance(stdClass $data, ?mod_videodiagnostic_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    videodiagnostic_prepare_editor_fields($data);
    $DB->update_record('videodiagnostic', $data);
    videodiagnostic_save_files_and_editors($data);

    $cm = get_coursemodule_from_instance('videodiagnostic', $data->id, $data->course, false, MUST_EXIST);
    $completion = new completion_info(get_course($data->course));
    if ($completion->is_enabled($cm)) {
        $completion->update_state($cm, COMPLETION_UNKNOWN);
    }
    return true;
}

/**
 * Copies editor payloads to persistent text fields before DB insert/update.
 *
 * @param stdClass $data Form data.
 * @return void
 */
function videodiagnostic_prepare_editor_fields(stdClass $data): void {
    foreach (['solution', 'teachercomments', 'materials'] as $field) {
        $editor = $field . '_editor';
        if (!empty($data->{$editor}) && is_array($data->{$editor})) {
            $data->{$field} = $data->{$editor}['text'] ?? '';
            $data->{$field . 'format'} = $data->{$editor}['format'] ?? FORMAT_HTML;
        }
    }
}

/**
 * Saves uploaded videos and embedded editor files.
 *
 * @param stdClass $data Instance data.
 * @return void
 */
function videodiagnostic_save_files_and_editors(stdClass $data): void {
    $context = context_module::instance($data->coursemodule);
    $videooptions = ['subdirs' => false, 'maxfiles' => 1, 'accepted_types' => ['video', '.m3u8']];
    $editoroptions = ['subdirs' => true, 'maxfiles' => -1, 'maxbytes' => 0, 'context' => $context];

    if (isset($data->video_filemanager)) {
        file_save_draft_area_files(
            $data->video_filemanager,
            $context->id,
            'mod_videodiagnostic',
            'video',
            0,
            $videooptions
        );
    }
    if (isset($data->explanationvideo_filemanager)) {
        file_save_draft_area_files(
            $data->explanationvideo_filemanager,
            $context->id,
            'mod_videodiagnostic',
            'explanationvideo',
            0,
            $videooptions
        );
    }

    global $DB;
    $changed = false;
    foreach (['solution', 'teachercomments', 'materials'] as $field) {
        $editor = $field . '_editor';
        if (!empty($data->{$editor}) && is_array($data->{$editor})) {
            $saved = file_save_draft_area_files(
                $data->{$editor}['itemid'],
                $context->id,
                'mod_videodiagnostic',
                $field,
                $data->id,
                $editoroptions,
                $data->{$editor}['text'] ?? ''
            );
            $data->{$field} = $saved;
            $data->{$field . 'format'} = $data->{$editor}['format'] ?? FORMAT_HTML;
            $changed = true;
        }
    }
    if ($changed) {
        $DB->update_record('videodiagnostic', $data);
    }
}

/**
 * Deletes an instance and its dependent data.
 *
 * @param int $id Instance id.
 * @return bool
 */
function videodiagnostic_delete_instance(int $id): bool {
    global $DB;

    $activity = $DB->get_record('videodiagnostic', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $cm = get_coursemodule_from_instance('videodiagnostic', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_videodiagnostic');
    }

    $questionids = $DB->get_fieldset_select('videodiagnostic_questions', 'id', 'videodiagnosticid = ?', [$id]);
    if ($questionids) {
        [$insql, $params] = $DB->get_in_or_equal($questionids, SQL_PARAMS_QM);
        $DB->delete_records_select('videodiagnostic_responses', "questionid {$insql}", $params);
    }
    $attemptids = $DB->get_fieldset_select('videodiagnostic_attempts', 'id', 'videodiagnosticid = ?', [$id]);
    if ($attemptids) {
        [$insql, $params] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_QM);
        $DB->delete_records_select('videodiagnostic_responses', "attemptid {$insql}", $params);
    }
    $DB->delete_records('videodiagnostic_attempts', ['videodiagnosticid' => $id]);
    $DB->delete_records('videodiagnostic_progress', ['videodiagnosticid' => $id]);
    $DB->delete_records('videodiagnostic_questions', ['videodiagnosticid' => $id]);
    $DB->delete_records('videodiagnostic', ['id' => $id]);
    return true;
}

/**
 * Serves protected plugin files.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module.
 * @param context $context Context.
 * @param string $filearea File area.
 * @param array $args Remaining path arguments.
 * @param bool $forcedownload Force download.
 * @param array $options Options.
 * @return bool
 */
function videodiagnostic_pluginfile(
    stdClass $course,
    stdClass $cm,
    context $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    if ($context->contextlevel !== CONTEXT_MODULE || $cm->modname !== 'videodiagnostic') {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/videodiagnostic:view', $context);

    $allowed = ['intro', 'video', 'explanationvideo', 'solution', 'teachercomments', 'materials'];
    if (!in_array($filearea, $allowed, true)) {
        return false;
    }

    $itemid = 0;
    if (in_array($filearea, ['solution', 'teachercomments', 'materials'], true)) {
        if (!$args) {
            return false;
        }
        $itemid = (int)array_shift($args);
        if ($itemid !== (int)$cm->instance) {
            return false;
        }
    }

    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_videodiagnostic', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
    return true;
}

/**
 * Extends activity navigation.
 *
 * @param settings_navigation $settingsnav Settings navigation.
 * @param navigation_node $videodiagnosticnode Module node.
 * @return void
 */
function videodiagnostic_extend_settings_navigation(
    settings_navigation $settingsnav,
    navigation_node $videodiagnosticnode
): void {
    global $PAGE;

    if (!$PAGE->cm || $PAGE->cm->modname !== 'videodiagnostic') {
        return;
    }
    $context = context_module::instance($PAGE->cm->id);
    if (has_capability('mod/videodiagnostic:managequestions', $context)) {
        $videodiagnosticnode->add(
            get_string('managequestions', 'mod_videodiagnostic'),
            new moodle_url('/mod/videodiagnostic/manage.php', ['id' => $PAGE->cm->id])
        );
    }
    if (has_capability('mod/videodiagnostic:viewreport', $context)) {
        $videodiagnosticnode->add(
            get_string('reports', 'mod_videodiagnostic'),
            new moodle_url('/mod/videodiagnostic/report.php', ['id' => $PAGE->cm->id])
        );
    }
}

/**
 * Provides cached course module information.
 *
 * @param stdClass $coursemodule Course module.
 * @return cached_cm_info|null
 */
function videodiagnostic_get_coursemodule_info(stdClass $coursemodule): ?cached_cm_info {
    global $DB;

    $activity = $DB->get_record('videodiagnostic', ['id' => $coursemodule->instance], 'id,name,intro,introformat');
    if (!$activity) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('videodiagnostic', $activity, $coursemodule->id, false);
    }
    return $info;
}

/**
 * Formats one rich-text activity field and rewrites embedded file URLs.
 *
 * @param stdClass $activity Activity.
 * @param context_module $context Context.
 * @param string $field Field name.
 * @return string
 */
function videodiagnostic_format_editor_field(stdClass $activity, context_module $context, string $field): string {
    $text = (string)($activity->{$field} ?? '');
    if ($text === '') {
        return '';
    }
    $text = file_rewrite_pluginfile_urls(
        $text,
        'pluginfile.php',
        $context->id,
        'mod_videodiagnostic',
        $field,
        $activity->id
    );
    $formatfield = $field . 'format';
    return format_text($text, $activity->{$formatfield} ?? FORMAT_HTML, ['context' => $context]);
}

/**
 * Formats a stored response for reports and comparison.
 *
 * @param stdClass|null $response Response.
 * @param stdClass $question Question.
 * @return string
 */
function videodiagnostic_format_response(?stdClass $response, stdClass $question): string {
    if (!$response) {
        return '-';
    }
    if (in_array($question->qtype, ['text', 'choice'], true)) {
        return s((string)$response->answertext);
    }
    $start = format_float((float)$response->starttime, 1) . 's';
    if ($question->qtype === 'interval') {
        return $start . ' – ' . format_float((float)$response->endtime, 1) . 's';
    }
    return $start;
}

/**
 * Converts persisted watched segments into template data for a visual timeline.
 *
 * @param stdClass|null $progress Progress record.
 * @return array Timeline template data.
 */
function videodiagnostic_timeline_data(?stdClass $progress): array {
    $duration = $progress ? (float)$progress->duration : 0.0;
    $raw = $progress ? json_decode((string)$progress->watchedsegments, true) : [];
    $segments = [];
    if ($duration > 0 && is_array($raw)) {
        foreach ($raw as $segment) {
            if (!is_array($segment) || count($segment) < 2) {
                continue;
            }
            $start = max(0.0, min($duration, (float)$segment[0]));
            $end = max($start, min($duration, (float)$segment[1]));
            if ($end <= $start) {
                continue;
            }
            $segments[] = [
                'left' => sprintf('%.4F', ($start / $duration) * 100),
                'width' => sprintf('%.4F', (($end - $start) / $duration) * 100),
                'start' => format_float($start, 1) . 's',
                'end' => format_float($end, 1) . 's',
            ];
        }
    }
    return [
        'hasduration' => $duration > 0,
        'segments' => $segments,
        'description' => get_string('timelinedescription', 'mod_videodiagnostic'),
    ];
}
