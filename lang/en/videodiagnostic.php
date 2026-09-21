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
 * English strings.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addquestion'] = 'Add question';
$string['allowpostattempt'] = 'Allow a post-study response';
$string['allowpostattempt_help'] = 'Allows students to answer the diagnostic questions again after the study content is released.';
$string['alreadysubmitted'] = 'This diagnostic stage has already been submitted.';
$string['answer'] = 'Answer';
$string['attemptstage'] = 'Stage';
$string['change'] = 'Change';
$string['changed'] = 'Changed';
$string['changedanswers'] = 'Changed responses';
$string['comparison'] = 'Initial × post-study comparison';
$string['completed'] = 'Completed';
$string['completiondiagnostic'] = 'Student must submit the initial diagnosis';
$string['completiondiagnostic_desc'] = 'Submit the initial diagnostic response';
$string['completionpercent'] = 'Required watched percentage';
$string['completionpercent_desc'] = 'Watch at least {$a}% of the diagnostic video';
$string['completionpercent_help'] = 'Require this percentage of the diagnostic video to be watched for activity completion. Use 0 to disable the viewing-percentage rule.';
$string['correctanswer'] = 'Expected/correct answer';
$string['correctanswer_help'] = 'Optional. For choices, enter the exact option text. For open responses, exact normalized text matching is used only when this field is filled.';
$string['deletequestion'] = 'Delete question';
$string['deletequestionconfirm'] = 'Delete this diagnostic question? Existing responses to it will also be removed.';
$string['details'] = 'Details';
$string['diagnosticresult'] = 'Diagnostic result';
$string['diagnosticsettings'] = 'Diagnostic flow';
$string['editquestion'] = 'Edit question';
$string['expectedend'] = 'Expected end time (seconds)';
$string['expectedstart'] = 'Expected start time (seconds)';
$string['explanationpublished'] = 'Publish study content';
$string['explanationpublished_help'] = 'Disable this to keep the explanatory stage hidden until the teacher is ready to release it.';
$string['explanationsource'] = 'Explanatory video source';
$string['explanationvideofile'] = 'Explanatory video file';
$string['explanationvideourl'] = 'Explanatory video URL';
$string['exportcsv'] = 'Export CSV';
$string['final'] = 'Post-study';
$string['finalresponse'] = 'Post-study response';
$string['finalresult'] = 'Post-study result';
$string['generalsettings'] = 'Diagnostic video';
$string['initial'] = 'Initial';
$string['initialdiagnosis'] = 'Initial diagnosis';
$string['initialinstructions'] = 'Answer based on what you know now, before seeing the explanation.';
$string['initialresponse'] = 'Initial response';
$string['initialresult'] = 'Initial result';
$string['initialsaved'] = 'Your initial diagnosis has been saved.';
$string['inprogress'] = 'In progress';
$string['invalidquestion'] = 'Invalid diagnostic question.';
$string['invalidstage'] = 'Invalid diagnostic stage.';
$string['invalidvideo'] = 'The configured video source could not be resolved.';
$string['lastaccess'] = 'Last tracking update';
$string['lastposition'] = 'Last position';
$string['managequestions'] = 'Manage questions';
$string['markcurrent'] = 'Use current video time';
$string['markend'] = 'Mark end';
$string['markstart'] = 'Mark start';
$string['materials'] = 'Complementary materials';
$string['modulename'] = 'Video Diagnostic';
$string['modulename_help'] = 'Use a video situation to capture prior knowledge, release explanation or study material, and compare an initial response with a later response.';
$string['modulenameplural'] = 'Video Diagnostics';
$string['movedown'] = 'Move down';
$string['moveup'] = 'Move up';
$string['no'] = 'No';
$string['noquestions'] = 'No diagnostic questions have been created yet.';
$string['nostudents'] = 'No enrolled students were found.';
$string['notstarted'] = 'Not started';
$string['notsubmitted'] = 'Not submitted';
$string['options'] = 'Choice options';
$string['options_help'] = 'Enter one option per line.';
$string['pluginadministration'] = 'Video Diagnostic administration';
$string['pluginname'] = 'Video Diagnostic';
$string['postinstructions'] = 'Answer again after studying the released explanation and materials.';
$string['postsaved'] = 'Your post-study diagnosis has been saved.';
$string['poststudydiagnosis'] = 'Post-study diagnosis';
$string['privacy:attemptpath'] = 'Stage {$a}';
$string['privacy:diagnosticpath'] = 'Video Diagnostic: {$a}';
$string['privacy:metadata:videodiagnostic_attempts'] = 'Stores the student\'s initial and post-study diagnostic attempts.';
$string['privacy:metadata:videodiagnostic_attempts:score'] = 'Automatically calculated diagnostic result when scoring information exists.';
$string['privacy:metadata:videodiagnostic_attempts:stage'] = 'Whether the attempt is initial or post-study.';
$string['privacy:metadata:videodiagnostic_attempts:timesubmitted'] = 'The time when the attempt was submitted.';
$string['privacy:metadata:videodiagnostic_attempts:userid'] = 'The user who submitted the diagnostic attempt.';
$string['privacy:metadata:videodiagnostic_progress'] = 'Stores viewing progress for the diagnostic video.';
$string['privacy:metadata:videodiagnostic_progress:lastposition'] = 'The last observed playback position.';
$string['privacy:metadata:videodiagnostic_progress:percent'] = 'The percentage of unique video content watched.';
$string['privacy:metadata:videodiagnostic_progress:userid'] = 'The user whose viewing progress is stored.';
$string['privacy:metadata:videodiagnostic_progress:watchedsegments'] = 'The merged video segments that were observed as watched.';
$string['privacy:metadata:videodiagnostic_responses'] = 'Stores responses to diagnostic questions.';
$string['privacy:metadata:videodiagnostic_responses:answertext'] = 'The textual or selected response.';
$string['privacy:metadata:videodiagnostic_responses:endtime'] = 'The selected video interval end.';
$string['privacy:metadata:videodiagnostic_responses:starttime'] = 'The selected video moment or interval start.';
$string['progress'] = 'Progress';
$string['qtypechoice'] = 'Choice';
$string['qtypeinterval'] = 'Mark a video interval';
$string['qtypemarker'] = 'Mark one moment in the video';
$string['qtypetext'] = 'Open response';
$string['question'] = 'Question';
$string['questionadded'] = 'Question added.';
$string['questiondeleted'] = 'Question deleted.';
$string['questiontext'] = 'Question';
$string['questiontype'] = 'Question type';
$string['questionupdated'] = 'Question updated.';
$string['releaseafterinitial'] = 'Release study content only after the initial diagnosis';
$string['releaseafterinitial_help'] = 'When enabled, students must submit the initial diagnosis before seeing the explanation, solution, comments, materials, and explanatory video.';
$string['report'] = 'Report';
$string['reports'] = 'Reports';
$string['required'] = 'Required response';
$string['reset'] = 'Reset';
$string['resetconfirm'] = 'Reset this student\'s diagnostic attempts and video progress?';
$string['resetdone'] = 'Student diagnostic data was reset.';
$string['score'] = 'Result';
$string['scoreunavailable'] = 'Not automatically scored';
$string['seconds'] = 'seconds';
$string['selectedsegment'] = 'Selected video moment/interval';
$string['solution'] = 'Correct solution / explanation';
$string['sourcenone'] = 'No video';
$string['sourceupload'] = 'Uploaded video';
$string['sourceurl'] = 'Direct video URL';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['status'] = 'Status';
$string['student'] = 'Student';
$string['studycontentnotavailable'] = 'The post-study stage is not available yet.';
$string['studylocked'] = 'Submit the initial diagnosis to unlock the explanatory stage.';
$string['studymaterials'] = 'Study content';
$string['studynotpublished'] = 'The explanatory stage has not yet been published by the teacher.';
$string['studysettings'] = 'Study and explanation';
$string['submitdiagnosis'] = 'Submit diagnosis';
$string['submitpostdiagnosis'] = 'Submit post-study response';
$string['submitted'] = 'Submitted';
$string['teachercomments'] = 'Teacher comments';
$string['timecode'] = 'Timecode';
$string['timelinedescription'] = 'Segments shown on the timeline were actually played; skipped regions remain unmarked.';
$string['tolerance'] = 'Time tolerance (seconds)';
$string['unchanged'] = 'Unchanged';
$string['videodiagnostic:addinstance'] = 'Add a new Video Diagnostic activity';
$string['videodiagnostic:exportreport'] = 'Export diagnostic reports';
$string['videodiagnostic:managequestions'] = 'Manage diagnostic questions';
$string['videodiagnostic:resetresponses'] = 'Reset diagnostic responses';
$string['videodiagnostic:view'] = 'View Video Diagnostic';
$string['videodiagnostic:viewreport'] = 'View diagnostic reports';
$string['videofile'] = 'Video file';
$string['videosource'] = 'Video source';
$string['videourl'] = 'Video URL';
$string['videourl_help'] = 'Use a direct media URL, YouTube URL, or Vimeo URL according to the selected source.';
$string['viewdetails'] = 'View details';
$string['watchprogress'] = 'Watched';
$string['watchtimeline'] = 'Viewing timeline';
$string['weight'] = 'Diagnostic weight';
$string['yes'] = 'Yes';
