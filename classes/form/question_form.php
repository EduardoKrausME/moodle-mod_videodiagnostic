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

namespace mod_videodiagnostic\form;

use moodleform;

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/formslib.php");

/**
 * Question editor form.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_form extends moodleform {
    /**
     * Defines form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'qid');
        $mform->setType('qid', PARAM_INT);

        $mform->addElement('editor', 'questiontext_editor', get_string('questiontext', 'mod_videodiagnostic'), null, [
            'maxfiles' => 0,
            'context' => $this->_customdata['context'],
        ]);
        $mform->setType('questiontext_editor', PARAM_RAW);
        $mform->addRule('questiontext_editor', get_string('required'), 'required', null, 'client');

        $types = [
            'text' => get_string('qtypetext', 'mod_videodiagnostic'),
            'choice' => get_string('qtypechoice', 'mod_videodiagnostic'),
            'marker' => get_string('qtypemarker', 'mod_videodiagnostic'),
            'interval' => get_string('qtypeinterval', 'mod_videodiagnostic'),
        ];
        $mform->addElement('select', 'qtype', get_string('questiontype', 'mod_videodiagnostic'), $types);
        $mform->setDefault('qtype', 'text');
        $mform->addElement('textarea', 'options', get_string('options', 'mod_videodiagnostic'), ['rows' => 6, 'cols' => 60]);
        $mform->setType('options', PARAM_TEXT);
        $mform->addHelpButton('options', 'options', 'mod_videodiagnostic');
        $mform->hideIf('options', 'qtype', 'neq', 'choice');

        $mform->addElement('text', 'correctanswer', get_string('correctanswer', 'mod_videodiagnostic'), ['size' => 70]);
        $mform->setType('correctanswer', PARAM_TEXT);
        $mform->addHelpButton('correctanswer', 'correctanswer', 'mod_videodiagnostic');
        $mform->hideIf('correctanswer', 'qtype', 'in', ['marker', 'interval']);

        $mform->addElement('text', 'expectedstart', get_string('expectedstart', 'mod_videodiagnostic'));
        $mform->setType('expectedstart', PARAM_FLOAT);
        $mform->setDefault('expectedstart', 0);
        $mform->hideIf('expectedstart', 'qtype', 'in', ['text', 'choice']);
        $mform->addElement('text', 'expectedend', get_string('expectedend', 'mod_videodiagnostic'));
        $mform->setType('expectedend', PARAM_FLOAT);
        $mform->setDefault('expectedend', 0);
        $mform->hideIf('expectedend', 'qtype', 'neq', 'interval');
        $mform->addElement('text', 'tolerance', get_string('tolerance', 'mod_videodiagnostic'));
        $mform->setType('tolerance', PARAM_FLOAT);
        $mform->setDefault('tolerance', 5);
        $mform->hideIf('tolerance', 'qtype', 'in', ['text', 'choice']);

        $mform->addElement('advcheckbox', 'required', get_string('required', 'mod_videodiagnostic'));
        $mform->setDefault('required', 1);
        $mform->addElement('text', 'weight', get_string('weight', 'mod_videodiagnostic'));
        $mform->setType('weight', PARAM_FLOAT);
        $mform->setDefault('weight', 1);

        $this->add_action_buttons();
    }

    /**
     * Validates question configuration.
     *
     * @param array $data Data.
     * @param array $files Files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (($data['qtype'] ?? '') === 'choice') {
            $options = preg_split('/\R/u', trim((string)($data['options'] ?? ''))) ?: [];
            $options = array_values(array_filter(array_map('trim', $options), static fn(string $v): bool => $v !== ''));
            if (count($options) < 2) {
                $errors['options'] = get_string('required');
            }
        }
        if (($data['qtype'] ?? '') === 'interval' && (float)($data['expectedend'] ?? 0) > 0
            && (float)($data['expectedend'] ?? 0) <= (float)($data['expectedstart'] ?? 0)) {
            $errors['expectedend'] = get_string('invaliddata');
        }
        return $errors;
    }
}
