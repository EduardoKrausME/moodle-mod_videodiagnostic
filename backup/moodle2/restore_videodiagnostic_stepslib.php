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
 * Restore structure for Video Diagnostic.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_videodiagnostic_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines restore paths.
     *
     * @return restore_path_element[]
     */
    protected function define_structure(): array {
        $paths = [];
        $paths[] = new restore_path_element('videodiagnostic', '/activity/videodiagnostic');
        $paths[] = new restore_path_element('videodiagnostic_question', '/activity/videodiagnostic/questions/question');
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('videodiagnostic_attempt',
                '/activity/videodiagnostic/attempts/attempt');
            $paths[] = new restore_path_element('videodiagnostic_response',
                '/activity/videodiagnostic/attempts/attempt/responses/response');
            $paths[] = new restore_path_element('videodiagnostic_progress',
                '/activity/videodiagnostic/progresses/progress');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores main activity row.
     *
     * @param array $data Data.
     * @return void
     */
    protected function process_videodiagnostic(array $data): void {
        global $DB;
        $record = (object)$data;
        $record->course = $this->get_courseid();
        $oldid = $record->id;
        unset($record->id);
        $newid = $DB->insert_record('videodiagnostic', $record);
        $this->apply_activity_instance($newid);
        $this->set_mapping('videodiagnostic', $oldid, $newid, true);
    }

    /**
     * Restores question.
     *
     * @param array $data Data.
     * @return void
     */
    protected function process_videodiagnostic_question(array $data): void {
        global $DB;
        $record = (object)$data;
        $oldid = $record->id;
        unset($record->id);
        $record->videodiagnosticid = $this->get_new_parentid('videodiagnostic');
        $newid = $DB->insert_record('videodiagnostic_questions', $record);
        $this->set_mapping('videodiagnostic_question', $oldid, $newid);
    }

    /**
     * Restores attempt.
     *
     * @param array $data Data.
     * @return void
     */
    protected function process_videodiagnostic_attempt(array $data): void {
        global $DB;
        $record = (object)$data;
        $oldid = $record->id;
        unset($record->id);
        $record->videodiagnosticid = $this->get_new_parentid('videodiagnostic');
        $record->userid = $this->get_mappingid('user', $record->userid);
        $newid = $DB->insert_record('videodiagnostic_attempts', $record);
        $this->set_mapping('videodiagnostic_attempt', $oldid, $newid);
    }

    /**
     * Restores response.
     *
     * @param array $data Data.
     * @return void
     */
    protected function process_videodiagnostic_response(array $data): void {
        global $DB;
        $record = (object)$data;
        unset($record->id);
        $record->attemptid = $this->get_new_parentid('videodiagnostic_attempt');
        $record->questionid = $this->get_mappingid('videodiagnostic_question', $record->questionid);
        $DB->insert_record('videodiagnostic_responses', $record);
    }

    /**
     * Restores progress.
     *
     * @param array $data Data.
     * @return void
     */
    protected function process_videodiagnostic_progress(array $data): void {
        global $DB;
        $record = (object)$data;
        unset($record->id);
        $record->videodiagnosticid = $this->get_new_parentid('videodiagnostic');
        $record->userid = $this->get_mappingid('user', $record->userid);
        $DB->insert_record('videodiagnostic_progress', $record);
    }

    /**
     * Restores files after the DB records exist.
     *
     * @return void
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_videodiagnostic', 'intro', null);
        $this->add_related_files('mod_videodiagnostic', 'video', null);
        $this->add_related_files('mod_videodiagnostic', 'explanationvideo', null);
        $this->add_related_files('mod_videodiagnostic', 'solution', 'videodiagnostic');
        $this->add_related_files('mod_videodiagnostic', 'teachercomments', 'videodiagnostic');
        $this->add_related_files('mod_videodiagnostic', 'materials', 'videodiagnostic');
    }
}
