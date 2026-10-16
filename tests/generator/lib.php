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
 * Moodle PHPUnit activity data generator for Video Diagnostic.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die();

/**
 * Create real Video Diagnostic activities through the Moodle module API.
 */
class mod_videodiagnostic_generator extends testing_module_generator {
    /**
     * Create a Video Diagnostic module with valid defaults for automated tests.
     *
     * @param stdClass|array|null $record Activity settings.
     * @param array|null $options Extra course module settings.
     * @return stdClass Created activity.
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;
        $record->videosource = $record->videosource ?? "url";
        $record->videourl = $record->videourl ?? "https://example.com/video.mp4";
        $record->explanationsource = $record->explanationsource ?? "none";
        $record->releaseafterinitial = $record->releaseafterinitial ?? 1;
        $record->explanationpublished = $record->explanationpublished ?? 1;
        $record->allowpostattempt = $record->allowpostattempt ?? 1;
        $record->completiondiagnostic = $record->completiondiagnostic ?? 1;
        $record->completionpercent = $record->completionpercent ?? 0;
        return parent::create_instance($record, $options);
    }
}
