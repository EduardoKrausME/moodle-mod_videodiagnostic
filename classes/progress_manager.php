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

namespace mod_videodiagnostic;

use completion_info;
use stdClass;

/**
 * Maintains server-authoritative watched-segment progress.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progress_manager {
    /**
     * Loads or creates a progress row.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @return stdClass
     */
    public function get_or_create(int $activityid, int $userid): stdClass {
        global $DB;

        $record = $DB->get_record('videodiagnostic_progress', [
            'videodiagnosticid' => $activityid,
            'userid' => $userid,
        ]);
        if ($record) {
            return $record;
        }
        $now = time();
        $record = (object)[
            'videodiagnosticid' => $activityid,
            'userid' => $userid,
            'duration' => 0,
            'lastposition' => 0,
            'uniquewatched' => 0,
            'totalwatchtime' => 0,
            'percent' => 0,
            'watchedsegments' => '[]',
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        try {
            $record->id = $DB->insert_record('videodiagnostic_progress', $record);
        } catch (\dml_write_exception $e) {
            $record = $DB->get_record('videodiagnostic_progress', [
                'videodiagnosticid' => $activityid,
                'userid' => $userid,
            ], '*', MUST_EXIST);
        }
        return $record;
    }

    /**
     * Updates progress from a small contiguous playback segment.
     *
     * @param stdClass $activity Activity record.
     * @param stdClass $cm Course module.
     * @param int $userid User id.
     * @param float $position Current position.
     * @param float $duration Duration.
     * @param float $segmentstart Segment start.
     * @param float $segmentend Segment end.
     * @return stdClass
     */
    public function update(
        stdClass $activity,
        stdClass $cm,
        int $userid,
        float $position,
        float $duration,
        float $segmentstart,
        float $segmentend
    ): stdClass {
        global $DB;

        $progress = $this->get_or_create($activity->id, $userid);
        $now = time();

        $reportedduration = max(0.0, min(86400.0, $duration));
        $storedduration = max(0.0, (float)$progress->duration);
        $initialduration = $storedduration <= 0 && $reportedduration > 0;

        // The first valid duration becomes authoritative for this progress row.
        // Later client-reported values cannot shrink or replace it.
        if ($initialduration) {
            $storedduration = $reportedduration;
        }

        $position = max(0.0, $storedduration > 0 ? min($storedduration, $position) : $position);
        $segmentstart = max(0.0, $segmentstart);
        $segmentend = max(0.0, $segmentend);

        $segments = $this->decode_segments((string)$progress->watchedsegments);
        $acceptedlength = 0.0;
        if (!$initialduration && $storedduration > 0 && $segmentend > $segmentstart) {
            $segmentend = min($storedduration, $segmentend);
            $segmentstart = min($segmentstart, $segmentend);
            $length = $segmentend - $segmentstart;

            // A heartbeat cannot claim more watched time than has actually elapsed on the server.
            // A small allowance absorbs timer jitter and integer timestamp precision.
            $elapsed = max(0.0, (float)($now - (int)$progress->timemodified));
            $maxaccepted = min(15.0, $elapsed + 2.0);
            if ($length > 0 && $length <= $maxaccepted) {
                $segments[] = [$segmentstart, $segmentend];
                $acceptedlength = $length;
            }
        }

        $segments = $this->merge_segments($segments, $storedduration);
        $unique = 0.0;
        foreach ($segments as $segment) {
            $unique += max(0.0, $segment[1] - $segment[0]);
        }

        $progress->duration = $storedduration;
        $progress->lastposition = $position;
        $progress->uniquewatched = round($unique, 3);
        $progress->totalwatchtime = round((float)$progress->totalwatchtime + $acceptedlength, 3);
        $progress->percent = $progress->duration > 0
            ? round(min(100, ($progress->uniquewatched / $progress->duration) * 100), 2)
            : 0;
        $progress->watchedsegments = json_encode($segments, JSON_UNESCAPED_SLASHES);
        $progress->timemodified = $now;
        $DB->update_record('videodiagnostic_progress', $progress);

        $completion = new completion_info(get_course($activity->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
        }
        return $progress;
    }

    /**
     * Decodes stored segments.
     *
     * @param string $json JSON.
     * @return array
     */
    private function decode_segments(string $json): array {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $segments = [];
        foreach ($decoded as $segment) {
            if (is_array($segment) && count($segment) === 2 && is_numeric($segment[0]) && is_numeric($segment[1])) {
                $segments[] = [(float)$segment[0], (float)$segment[1]];
            }
        }
        return $segments;
    }

    /**
     * Merges overlapping and adjacent watched segments.
     *
     * @param array $segments Segments.
     * @param float $duration Duration.
     * @return array
     */
    private function merge_segments(array $segments, float $duration): array {
        if (!$segments) {
            return [];
        }
        usort($segments, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($segments as $segment) {
            $start = max(0.0, (float)$segment[0]);
            $end = max($start, (float)$segment[1]);
            if ($duration > 0) {
                $start = min($duration, $start);
                $end = min($duration, $end);
            }
            if ($end <= $start) {
                continue;
            }
            if (!$merged || $start > $merged[count($merged) - 1][1] + 0.35) {
                $merged[] = [$start, $end];
            } else {
                $last = count($merged) - 1;
                $merged[$last][1] = max($merged[$last][1], $end);
            }
        }
        return $merged;
    }
}
