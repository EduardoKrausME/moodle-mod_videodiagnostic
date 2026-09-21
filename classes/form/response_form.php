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
use stdClass;

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/formslib.php");

/**
 * Dynamic student diagnostic response form.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_form extends moodleform {
    /**
     * Defines response fields from activity questions.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $questions = $this->_customdata['questions'];
        $stage = (int)$this->_customdata['stage'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'stage', $stage);
        $mform->setType('stage', PARAM_INT);

        foreach ($questions as $question) {
            $this->add_question($mform, $question);
        }
        $label = $stage === 1
            ? get_string('submitdiagnosis', 'mod_videodiagnostic')
            : get_string('submitpostdiagnosis', 'mod_videodiagnostic');
        $this->add_action_buttons(false, $label);
    }

    /**
     * Adds one dynamic question.
     *
     * @param \MoodleQuickForm $mform Form.
     * @param stdClass $question Question.
     * @return void
     */
    private function add_question(\MoodleQuickForm $mform, stdClass $question): void {
        $name = 'q' . $question->id;
        $label = format_text($question->questiontext, $question->questionformat, ['para' => false]);
        if ($question->qtype === 'text') {
            $mform->addElement('textarea', $name, $label, ['rows' => 5, 'cols' => 70]);
            $mform->setType($name, PARAM_RAW_TRIMMED);
            if ($question->required) {
                $mform->addRule($name, get_string('required'), 'required', null, 'client');
            }
            return;
        }
        if ($question->qtype === 'choice') {
            $options = json_decode((string)$question->optionsjson, true);
            if (!is_array($options)) {
                $options = [];
            }
            $select = ['' => get_string('choosedots')];
            foreach ($options as $option) {
                $select[(string)$option] = (string)$option;
            }
            $mform->addElement('select', $name, $label, $select);
            $mform->setType($name, PARAM_TEXT);
            if ($question->required) {
                $mform->addRule($name, get_string('required'), 'required', null, 'client');
            }
            return;
        }

        $mform->addElement('static', 'label_' . $question->id, '', $label);
        $startname = $name . '_start';
        $startgroup = [];
        $startgroup[] = $mform->createElement('text', $startname, '', ['size' => 12, 'data-vd-time-input' => 'start']);
        $startgroup[] = $mform->createElement(
            'button',
            'mark_' . $question->id . '_start',
            $question->qtype === 'interval' ?
                get_string('markstart', 'mod_videodiagnostic') :
                get_string('markcurrent', 'mod_videodiagnostic'),
            ['type' => 'button', 'data-vd-mark-time' => $startname]
        );
        $mform->addGroup($startgroup, 'group_' . $startname, get_string('expectedstart', 'mod_videodiagnostic'), ' ', false);
        $mform->setType($startname, PARAM_FLOAT);
        if ($question->required) {
            $mform->addGroupRule('group_' . $startname, [$startname => [get_string('required'), 'required', null, 'client']]);
        }

        if ($question->qtype === 'interval') {
            $endname = $name . '_end';
            $endgroup = [];
            $endgroup[] = $mform->createElement('text', $endname, '', ['size' => 12, 'data-vd-time-input' => 'end']);
            $endgroup[] = $mform->createElement(
                'button',
                'mark_' . $question->id . '_end',
                get_string('markend', 'mod_videodiagnostic'),
                ['type' => 'button', 'data-vd-mark-time' => $endname]
            );
            $mform->addGroup($endgroup, 'group_' . $endname, get_string('expectedend', 'mod_videodiagnostic'), ' ', false);
            $mform->setType($endname, PARAM_FLOAT);
            if ($question->required) {
                $mform->addGroupRule('group_' . $endname, [$endname => [get_string('required'), 'required', null, 'client']]);
            }
        }
    }
}
