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
 * Backup structure for Video Diagnostic.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_videodiagnostic_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the activity backup tree.
     *
     * @return backup_nested_element
     */
    protected function define_structure(): backup_nested_element {
        $userinfo = $this->get_setting_value('userinfo');

        $activity = new backup_nested_element('videodiagnostic', ['id'], [
            'course', 'name', 'intro', 'introformat', 'videosource', 'videourl',
            'explanationsource', 'explanationvideourl', 'solution', 'solutionformat',
            'teachercomments', 'teachercommentsformat', 'materials', 'materialsformat',
            'releaseafterinitial', 'explanationpublished', 'allowpostattempt',
            'completiondiagnostic', 'completionpercent', 'timecreated', 'timemodified',
        ]);
        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], [
            'sortorder', 'questiontext', 'questionformat', 'qtype', 'optionsjson', 'correctanswer',
            'expectedstart', 'expectedend', 'tolerance', 'required', 'weight', 'timecreated', 'timemodified',
        ]);
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'userid', 'stage', 'submitted', 'scoreavailable', 'score', 'changedcount',
            'timecreated', 'timemodified', 'timesubmitted',
        ]);
        $responses = new backup_nested_element('responses');
        $response = new backup_nested_element('response', ['id'], [
            'questionid', 'answertext', 'starttime', 'endtime', 'score', 'scoreavailable',
            'changedfrominitial', 'timecreated', 'timemodified',
        ]);
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'duration', 'lastposition', 'uniquewatched', 'totalwatchtime', 'percent',
            'watchedsegments', 'timecreated', 'timemodified',
        ]);

        $activity->add_child($questions);
        $questions->add_child($question);
        $activity->add_child($attempts);
        $attempts->add_child($attempt);
        $attempt->add_child($responses);
        $responses->add_child($response);
        $activity->add_child($progresses);
        $progresses->add_child($progress);

        $activity->set_source_table('videodiagnostic', ['id' => backup::VAR_ACTIVITYID]);
        $question->set_source_table('videodiagnostic_questions', ['videodiagnosticid' => backup::VAR_PARENTID]);
        if ($userinfo) {
            $attempt->set_source_table('videodiagnostic_attempts', ['videodiagnosticid' => backup::VAR_PARENTID]);
            $response->set_source_table('videodiagnostic_responses', ['attemptid' => backup::VAR_PARENTID]);
            $progress->set_source_table('videodiagnostic_progress', ['videodiagnosticid' => backup::VAR_PARENTID]);
            $attempt->annotate_ids('user', 'userid');
            $progress->annotate_ids('user', 'userid');
        }

        $activity->annotate_files('mod_videodiagnostic', 'intro', null);
        $activity->annotate_files('mod_videodiagnostic', 'video', null);
        $activity->annotate_files('mod_videodiagnostic', 'explanationvideo', null);
        $activity->annotate_files('mod_videodiagnostic', 'solution', 'id');
        $activity->annotate_files('mod_videodiagnostic', 'teachercomments', 'id');
        $activity->annotate_files('mod_videodiagnostic', 'materials', 'id');

        return $this->prepare_activity_structure($activity);
    }
}
