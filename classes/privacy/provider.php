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

namespace mod_videodiagnostic\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\request\core_user_data_provider;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Privacy provider.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider, core_user_data_provider, core_userlist_provider {
    /**
     * Returns metadata declarations.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videodiagnostic_attempts', [
            'userid' => 'privacy:metadata:videodiagnostic_attempts:userid',
            'stage' => 'privacy:metadata:videodiagnostic_attempts:stage',
            'score' => 'privacy:metadata:videodiagnostic_attempts:score',
            'timesubmitted' => 'privacy:metadata:videodiagnostic_attempts:timesubmitted',
        ], 'privacy:metadata:videodiagnostic_attempts');
        $collection->add_database_table('videodiagnostic_responses', [
            'answertext' => 'privacy:metadata:videodiagnostic_responses:answertext',
            'starttime' => 'privacy:metadata:videodiagnostic_responses:starttime',
            'endtime' => 'privacy:metadata:videodiagnostic_responses:endtime',
        ], 'privacy:metadata:videodiagnostic_responses');
        $collection->add_database_table('videodiagnostic_progress', [
            'userid' => 'privacy:metadata:videodiagnostic_progress:userid',
            'lastposition' => 'privacy:metadata:videodiagnostic_progress:lastposition',
            'percent' => 'privacy:metadata:videodiagnostic_progress:percent',
            'watchedsegments' => 'privacy:metadata:videodiagnostic_progress:watchedsegments',
        ], 'privacy:metadata:videodiagnostic_progress');
        return $collection;
    }

    /**
     * Returns contexts containing user data.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videodiagnostic} vd ON vd.id = cm.instance
             LEFT JOIN {videodiagnostic_attempts} a ON a.videodiagnosticid = vd.id AND a.userid = :attemptuserid
             LEFT JOIN {videodiagnostic_progress} p ON p.videodiagnosticid = vd.id AND p.userid = :progressuserid
                 WHERE a.id IS NOT NULL OR p.id IS NOT NULL";
        $contextlist->add_from_sql($sql, [
            'contextmodule' => CONTEXT_MODULE,
            'modname' => 'videodiagnostic',
            'attemptuserid' => $userid,
            'progressuserid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Returns users with data in the specified module context.
     *
     * @param userlist $userlist User list for the context.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('videodiagnostic', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        $params = ['videodiagnosticid' => $cm->instance];
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {videodiagnostic_attempts} WHERE videodiagnosticid = :videodiagnosticid',
            $params
        );
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {videodiagnostic_progress} WHERE videodiagnosticid = :videodiagnosticid',
            $params
        );
    }

    /**
     * Exports user data from approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videodiagnostic', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $activity = $DB->get_record('videodiagnostic', ['id' => $cm->instance]);
            if (!$activity) {
                continue;
            }
            $basepath = [get_string('privacy:diagnosticpath', 'mod_videodiagnostic', $activity->name)];
            $progress = $DB->get_record('videodiagnostic_progress', [
                'videodiagnosticid' => $activity->id,
                'userid' => $userid,
            ]);
            if ($progress) {
                writer::with_context($context)->export_data($basepath, (object)[
                    'watchedpercent' => $progress->percent,
                    'lastposition' => $progress->lastposition,
                    'uniquewatched' => $progress->uniquewatched,
                    'totalwatchtime' => $progress->totalwatchtime,
                    'watchedsegments' => $progress->watchedsegments,
                    'timemodified' => transform::datetime($progress->timemodified),
                ]);
            }
            $attempts = $DB->get_records('videodiagnostic_attempts', [
                'videodiagnosticid' => $activity->id,
                'userid' => $userid,
            ]);
            foreach ($attempts as $attempt) {
                $responses = $DB->get_records('videodiagnostic_responses', ['attemptid' => $attempt->id]);
                $exportresponses = [];
                foreach ($responses as $response) {
                    $question = $DB->get_record('videodiagnostic_questions', ['id' => $response->questionid]);
                    $exportresponses[] = (object)[
                        'question' => $question ? strip_tags($question->questiontext) : '#' . $response->questionid,
                        'answer' => $response->answertext,
                        'starttime' => $response->starttime,
                        'endtime' => $response->endtime,
                        'changedfrominitial' => (bool)$response->changedfrominitial,
                    ];
                }
                $path = array_merge($basepath, [get_string('privacy:attemptpath', 'mod_videodiagnostic', $attempt->stage)]);
                writer::with_context($context)->export_data($path, (object)[
                    'stage' => $attempt->stage,
                    'scoreavailable' => (bool)$attempt->scoreavailable,
                    'score' => $attempt->score,
                    'timesubmitted' => transform::datetime($attempt->timesubmitted),
                    'responses' => $exportresponses,
                ]);
            }
        }
    }

    /**
     * Deletes all user data in a module context.
     *
     * @param context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;
        if (!$context instanceof context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videodiagnostic', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $attemptids = $DB->get_fieldset_select('videodiagnostic_attempts', 'id', 'videodiagnosticid = ?', [$cm->instance]);
        if ($attemptids) {
            [$insql, $params] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_QM);
            $DB->delete_records_select('videodiagnostic_responses', "attemptid {$insql}", $params);
        }
        $DB->delete_records('videodiagnostic_attempts', ['videodiagnosticid' => $cm->instance]);
        $DB->delete_records('videodiagnostic_progress', ['videodiagnosticid' => $cm->instance]);
    }

    /**
     * Deletes data for an approved list of users in one module context.
     *
     * @param approved_userlist $userlist Approved users and context.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('videodiagnostic', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'privacyuser');
        $params = array_merge(['videodiagnosticid' => $cm->instance], $userparams);
        $attemptids = $DB->get_fieldset_select(
            'videodiagnostic_attempts',
            'id',
            "videodiagnosticid = :videodiagnosticid AND userid {$usersql}",
            $params
        );
        if ($attemptids) {
            [$attemptsql, $attemptparams] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED, 'privacyattempt');
            $DB->delete_records_select('videodiagnostic_responses', "attemptid {$attemptsql}", $attemptparams);
        }

        $DB->delete_records_select(
            'videodiagnostic_attempts',
            "videodiagnosticid = :videodiagnosticid AND userid {$usersql}",
            $params
        );
        $DB->delete_records_select(
            'videodiagnostic_progress',
            "videodiagnosticid = :videodiagnosticid AND userid {$usersql}",
            $params
        );
    }

    /**
     * Deletes user data in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videodiagnostic', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $attemptids = $DB->get_fieldset_select(
                'videodiagnostic_attempts', 'id', 'videodiagnosticid = ? AND userid = ?', [$cm->instance, $userid]
            );
            if ($attemptids) {
                [$insql, $params] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_QM);
                $DB->delete_records_select('videodiagnostic_responses', "attemptid {$insql}", $params);
            }
            $DB->delete_records('videodiagnostic_attempts', ['videodiagnosticid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('videodiagnostic_progress', ['videodiagnosticid' => $cm->instance, 'userid' => $userid]);
        }
    }
}
