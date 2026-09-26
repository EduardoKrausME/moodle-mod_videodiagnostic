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
 * Activity settings form.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videodiagnostic\video_helper;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Video Diagnostic settings form.
 */
class mod_videodiagnostic_mod_form extends moodleform_mod {
    /**
     * Defines activity settings.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $sources = video_helper::source_options(false);
        $mform->addElement('select', 'videosource', get_string('videosource', 'mod_videodiagnostic'), $sources);
        $mform->setDefault('videosource', 'upload');
        $mform->addElement(
            'filemanager',
            'video_filemanager',
            get_string('videofile', 'mod_videodiagnostic'),
            null,
            ['subdirs' => false, 'accepted_types' => ['video', '.m3u8']]
        );
        $mform->hideIf('video_filemanager', 'videosource', 'neq', 'upload');
        $mform->addElement('text', 'videourl', get_string('videourl', 'mod_videodiagnostic'), ['size' => 80]);
        $mform->setType('videourl', PARAM_URL);
        $mform->addHelpButton('videourl', 'videourl', 'mod_videodiagnostic');
        $mform->hideIf('videourl', 'videosource', 'eq', 'upload');

        $mform->addElement('html', '<h3>' . get_string('diagnosticsettings', 'mod_videodiagnostic') . '</h3>');
        $mform->addElement('advcheckbox', 'releaseafterinitial', get_string('releaseafterinitial', 'mod_videodiagnostic'));
        $mform->addHelpButton('releaseafterinitial', 'releaseafterinitial', 'mod_videodiagnostic');
        $mform->setDefault('releaseafterinitial', 1);
        $mform->addElement('advcheckbox', 'explanationpublished', get_string('explanationpublished', 'mod_videodiagnostic'));
        $mform->addHelpButton('explanationpublished', 'explanationpublished', 'mod_videodiagnostic');
        $mform->setDefault('explanationpublished', 1);
        $mform->addElement('advcheckbox', 'allowpostattempt', get_string('allowpostattempt', 'mod_videodiagnostic'));
        $mform->addHelpButton('allowpostattempt', 'allowpostattempt', 'mod_videodiagnostic');
        $mform->setDefault('allowpostattempt', 1);

        $mform->addElement('html', '<h3>' . get_string('studysettings', 'mod_videodiagnostic') . '</h3>');
        $mform->addElement(
            'select',
            'explanationsource',
            get_string('explanationsource', 'mod_videodiagnostic'),
            video_helper::source_options(true)
        );
        $mform->setDefault('explanationsource', 'none');
        $mform->addElement(
            'filemanager',
            'explanationvideo_filemanager',
            get_string('explanationvideofile', 'mod_videodiagnostic'),
            null,
            ['subdirs' => false, 'accepted_types' => ['video', '.m3u8']]
        );
        $mform->hideIf('explanationvideo_filemanager', 'explanationsource', 'neq', 'upload');
        $mform->addElement('text', 'explanationvideourl', get_string('explanationvideourl', 'mod_videodiagnostic'), ['size' => 80]);
        $mform->setType('explanationvideourl', PARAM_URL);
        $mform->hideIf('explanationvideourl', 'explanationsource', 'in', ['none', 'upload']);

        $editoroptions = ['subdirs' => true, 'maxfiles' => -1, 'maxbytes' => 0, 'context' => $this->context];
        $mform->addElement('editor', 'solution_editor',
            get_string('solution', 'mod_videodiagnostic'), null, $editoroptions);
        $mform->setType('solution_editor', PARAM_RAW);
        $mform->addElement('editor', 'teachercomments_editor',
            get_string('teachercomments', 'mod_videodiagnostic'), null, $editoroptions);
        $mform->setType('teachercomments_editor', PARAM_RAW);
        $mform->addElement('editor', 'materials_editor',
            get_string('materials', 'mod_videodiagnostic'), null, $editoroptions);
        $mform->setType('materials_editor', PARAM_RAW);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Adds custom completion rules.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $suffix = $this->get_suffix();
        $diagnosticel = 'completiondiagnostic' . $suffix;
        $percentel = 'completionpercent' . $suffix;

        $mform->addElement('checkbox', $diagnosticel, '', get_string('completiondiagnostic', 'mod_videodiagnostic'));
        $mform->setDefault($diagnosticel, 1);
        $mform->addElement('text', $percentel, get_string('completionpercent', 'mod_videodiagnostic'), ['size' => 6]);
        $mform->setType($percentel, PARAM_FLOAT);
        $mform->setDefault($percentel, 0);
        $mform->addHelpButton($percentel, 'completionpercent', 'mod_videodiagnostic');

        return [$diagnosticel, $percentel];
    }

    /**
     * Checks whether at least one custom completion rule is enabled.
     *
     * @param array $data Submitted form data.
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        $suffix = $this->get_suffix();
        return !empty($data['completiondiagnostic' . $suffix])
            || ((float)($data['completionpercent' . $suffix] ?? 0) > 0);
    }

    /**
     * Normalises custom completion fields after submission.
     *
     * @param stdClass $data Submitted form data.
     * @return void
     */
    public function data_postprocessing($data): void {
        parent::data_postprocessing($data);
        $suffix = $this->get_suffix();
        $diagnosticel = 'completiondiagnostic' . $suffix;
        if (!isset($data->{$diagnosticel})) {
            $data->{$diagnosticel} = 0;
        }
    }

    /**
     * Prepares file areas and editors when editing an existing activity.
     *
     * @param array $defaultvalues Default values.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);
        if (empty($this->current->instance)) {
            return;
        }

        $videooptions = ['subdirs' => false, 'maxfiles' => 1, 'accepted_types' => ['video', '.m3u8']];
        $defaultvalues['video_filemanager'] = file_get_submitted_draft_itemid('video_filemanager');
        file_prepare_draft_area(
            $defaultvalues['video_filemanager'],
            $this->context->id,
            'mod_videodiagnostic',
            'video',
            0,
            $videooptions
        );
        $defaultvalues['explanationvideo_filemanager'] = file_get_submitted_draft_itemid('explanationvideo_filemanager');
        file_prepare_draft_area(
            $defaultvalues['explanationvideo_filemanager'],
            $this->context->id,
            'mod_videodiagnostic',
            'explanationvideo',
            0,
            $videooptions
        );

        $editoroptions = ['subdirs' => true, 'maxfiles' => -1, 'maxbytes' => 0, 'context' => $this->context];
        foreach (['solution', 'teachercomments', 'materials'] as $field) {
            $draftid = file_get_submitted_draft_itemid($field . '_editor');
            $text = file_prepare_draft_area(
                $draftid,
                $this->context->id,
                'mod_videodiagnostic',
                $field,
                $this->current->instance,
                $editoroptions,
                $defaultvalues[$field] ?? ''
            );
            $defaultvalues[$field . '_editor'] = [
                'text' => $text,
                'format' => $defaultvalues[$field . 'format'] ?? FORMAT_HTML,
                'itemid' => $draftid,
            ];
        }
    }

    /**
     * Validates activity settings.
     *
     * @param array $data Data.
     * @param array $files Files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (($data['videosource'] ?? '') !== 'upload' && empty($data['videourl'])) {
            $errors['videourl'] = get_string('required');
        }
        if (!in_array(($data['explanationsource'] ?? 'none'), ['none', 'upload'], true) && empty($data['explanationvideourl'])) {
            $errors['explanationvideourl'] = get_string('required');
        }
        $percent = (float)($data['completionpercent'] ?? 0);
        if ($percent < 0 || $percent > 100) {
            $errors['completionpercent'] = get_string('invaliddata', 'error');
        }
        foreach (['video_filemanager', 'explanationvideo_filemanager'] as $field) {
            $draftid = (int)($data[$field] ?? 0);
            if ($draftid > 0) {
                $draftinfo = file_get_draft_area_info($draftid);
                if ((int)$draftinfo['filecount'] > 1) {
                    $errors[$field] = get_string('errormaxfiles', 'videodiagnostic');
                }
            }
        }
        return $errors;
    }
}
