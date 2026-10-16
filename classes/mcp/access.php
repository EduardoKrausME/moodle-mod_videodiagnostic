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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Resolve Moodle module contexts and enforce Video Diagnostic MCP privacy.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videodiagnostic\mcp;

use context_module;
use local_mcp\security\authenticated_identity;
use moodle_exception;
use stdClass;

/**
 * Require the original module capabilities and restrict reports by enrollment and groups.
 */
final class access {
    /**
     * Load a real activity from the caller-supplied course module ID.
     *
     * @param int $cmid Course module ID.
     * @param authenticated_identity $identity Connected Moodle user.
     * @param string $capability Required module permission.
     * @return array
     */
    public static function activity(int $cmid, authenticated_identity $identity, string $capability): array {
        global $DB;
        $cm = get_coursemodule_from_id("videodiagnostic", $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id, MUST_EXIST);
        require_capability($capability, $context, $identity->userid);
        $activity = $DB->get_record("videodiagnostic", ["id" => $cm->instance], "*", MUST_EXIST);
        return [$cm, $context, $activity];
    }

    /**
     * Check permissions for one enrolled learner, including separate group boundaries.
     *
     * @param stdClass $cm Course module.
     * @param context_module $context Module context.
     * @param stdClass $activity Video Diagnostic.
     * @param int $userid Target learner.
     * @param authenticated_identity $identity Connected Moodle user.
     * @return stdClass Enrolled learner.
     */
    public static function student(
        stdClass $cm, context_module $context, stdClass $activity,
        int $userid, authenticated_identity $identity
    ): stdClass {
        global $DB;
        $student = $DB->get_record("user", ["id" => $userid, "deleted" => 0], "*", MUST_EXIST);
        if (!is_enrolled($context, $student) ||
                has_capability("mod/videodiagnostic:managequestions", $context, $userid)) {
            throw new moodle_exception("mcpstudentnotaccessible", "mod_videodiagnostic");
        }
        if (!self::allowed_group($cm, $context, $userid, $identity)) {
            throw new moodle_exception("mcpstudentnotaccessible", "mod_videodiagnostic");
        }
        return $student;
    }

    /**
     * Separate groups prevent disclosure of students from another group.
     *
     * @param stdClass $cm Course module.
     * @param context_module $context Module context.
     * @param int $userid Target learner.
     * @param authenticated_identity $identity Connected Moodle user.
     * @return bool
     */
    public static function allowed_group(
        stdClass $cm, context_module $context, int $userid, authenticated_identity $identity
    ): bool {
        if (groups_get_activity_groupmode($cm) !== SEPARATEGROUPS ||
                has_capability("moodle/site:accessallgroups", $context, $identity->userid)) {
            return true;
        }
        $memberships = groups_get_user_groups((int)$cm->course, $identity->userid);
        $allowed = [];
        foreach ($memberships as $groupids) {
            foreach ($groupids as $groupid) {
                $allowed[(int)$groupid] = true;
            }
        }
        foreach (array_keys($allowed) as $groupid) {
            if (groups_is_member($groupid, $userid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Resolve the module context from the requested course-module identifier.
     *
     * @param int $cmid Course module.
     * @return context_module
     */
    public static function context(int $cmid): context_module {
        get_coursemodule_from_id("videodiagnostic", $cmid, 0, false, MUST_EXIST);
        return context_module::instance($cmid, MUST_EXIST);
    }
}
