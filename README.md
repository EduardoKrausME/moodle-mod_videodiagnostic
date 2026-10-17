# Video Diagnostic (`mod_videodiagnostic`)

Video Diagnostic is a Moodle activity for capturing prior knowledge from a video-based situation, releasing an
explanation or study stage, and comparing the student's initial diagnosis with a post-study response.

## Main features

- Diagnostic video from upload, direct URL, YouTube, or Vimeo.
- Open-response, choice, video-moment, and video-interval questions.
- Initial diagnostic response before the explanation is shown.
- Optional explanatory video, solution, teacher comments, and complementary materials.
- Optional post-study response using the same diagnostic questions.
- Automatic initial-versus-final comparison.
- Optional automatic scoring when expected answers or expected video ranges are configured.
- Server-side watched-segment tracking with unique watched percentage and visual watched/skipped timeline.
- Per-student and class reports, selected timecodes, answer evolution, and CSV export.
- Custom completion based on initial submission and/or watched percentage.
- Backup and restore, including user data when requested.
- Moodle Privacy API implementation.

## Tracking model

The activity periodically submits the actual playback position and a small contiguous watched segment. The server merges
accepted segments and calculates unique watched time and percentage. Large discontinuities are rejected as watched
segments, so seeking to the end does not mark skipped content as watched.

Upload and direct media URLs use the HTML5 player. YouTube and Vimeo use their respective browser player APIs for
playback position when available.

## Diagnostic scoring

Scoring is optional. Open responses are only automatically scored when an expected answer is configured. Choice
questions can use an expected option. Marker and interval questions can use expected time ranges and tolerance. If no
question has an automatic criterion, the attempt is shown as diagnostic-only rather than graded.

The plugin does not create a Moodle gradebook item. Its score is a diagnostic indicator inside the activity report.


## Optional MCP integration for ChatGPT

When [local_mcp](https://eduardokrausme.github.io/moodle-local_mcp/) is installed,
the optional `mod_videodiagnostic\mcp\provider` at `classes/mcp/provider.php`
registers the following tools automatically. No extra web server, database
migration or change to `version.php` is required.

| Tool | Scope | Purpose |
| --- | --- | --- |
| `videodiagnostic_get_activity` | READ | Inspect activity settings and question counts. |
| `videodiagnostic_list_questions` | READ | Inspect questions, answer criteria and expected video intervals. |
| `videodiagnostic_list_students` | READ | List enrolled learners' initial/final results and watched progress. |
| `videodiagnostic_get_student` | READ | Compare one learner's submitted answers, scores and watched segments. |
| `videodiagnostic_save_question` | WRITE | Create or update a question through the shared native question service. |
| `videodiagnostic_reset_student` | WRITE | Reset one learner's submitted diagnoses and viewing progress. |

All tools use a Moodle course module ID (`cmid`), enforce the authenticated user's
capabilities and keep READ free of database writes or completion updates.
`mod/videodiagnostic:viewreport` is required to view private reports.
`mod/videodiagnostic:managequestions` is required to access answer keys or
write questions. A learner reset requires both `viewreport` and
`mod/videodiagnostic:resetresponses`. Only enrolled learners in permitted
separate groups are visible. READ responses use bounded pagination;
student comparisons return up to 50 watched intervals at a time.

Both WRITE tools use the local_mcp preview and one-time confirmation protocol.
For question edits, call `videodiagnostic_list_questions` and pass its
`state_hash` as `expected_state_hash` to `videodiagnostic_save_question`.
Set `questionid` to zero or omit it to create a new question; give an existing
question ID to update it. Updating criteria does not erase learner responses or
retroactively recalculate recorded diagnostic scores.

For a reset, first call `videodiagnostic_get_student` and supply its
`state_hash` to `videodiagnostic_reset_student`. The reset is irreversible:
it removes attempts, responses and video progress and refreshes Moodle completion
using the existing `diagnostic_manager::reset_user()`. State hashes and Moodle
locks guard both write operations against stale confirmations and concurrent MCP
requests. No artificial video-progress updates, automatic grade overrides or
AI calls are exposed.

After deployment, refresh the ChatGPT MCP connector to discover these tools.

## License

GNU GPL v3 or later.

Copyright © 2026 Eduardo Kraus.
