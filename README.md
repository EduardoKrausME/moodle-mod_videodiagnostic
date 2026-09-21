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

## License

GNU GPL v3 or later.

Copyright © 2026 Eduardo Kraus.
