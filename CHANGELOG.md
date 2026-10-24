# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry describes changes verified by comparing its tag with the immediately preceding published release. Later changes on `master` are excluded.

## 1.0.8 - 2026-10-19

### Added

- Added optional integration with `local_mcp` through `mod_videodiagnostic\mcp\provider`, registering four READ tools (`videodiagnostic_get_activity`, `videodiagnostic_list_questions`, `videodiagnostic_list_students`, and `videodiagnostic_get_student`) and two WRITE tools (`videodiagnostic_save_question` and `videodiagnostic_reset_student`) when the MCP connector is installed.
- Added access and reporting helpers for MCP tools, enforcing Moodle capabilities, course enrollment, separate-group visibility, bounded result sets and protection of question answer criteria.
- Added preview-based question editing and student reset tools with expected-state SHA-256 hashes and Moodle locks. Question edits use the same native question service as the activity UI; resets use the existing diagnostic manager and refresh completion.
- Added an administrator-only, dismissible notice promoting the optional MCP integration on the activity settings form, with a Mustache template, translated English and Brazilian Portuguese strings, and responsive styling.
- Added a PHPUnit activity generator and `tests/mcp_tools_test.php` to cover question editing, state checks, read-only reports, access restrictions and safe write previews.
- Documented MCP tools, permissions, limitations and confirmation requirements in `README.md`.

### Changed

- Refactored `question.php` to save questions through the shared `mod_videodiagnostic\question_writer` service instead of duplicating database insert/update logic.
- Added release workflow concurrency settings using `github.ref`, `cancel-in-progress: false` and `queue: max`.
- Updated the plugin release identifier from `1.0.7` to `1.0.8` and its build number from `2026101501` to `2026101900`.

[Compare 1.0.7...1.0.8](https://github.com/EduardoKrausME/moodle-mod_videodiagnostic/compare/1.0.7...1.0.8)

## 1.0.7 - 2026-10-15

### Changed

- Added the plugin icon at `pix/icon.png` and reorganized the SVG logo asset as `pix/monologo.svg`.
- Updated the plugin release identifier from `1.0.6` to `1.0.7` and its build number from `2026101500` to `2026101501`.

No changes to the Video Diagnostic activity functionality were identified in this tag comparison.

[Compare 1.0.6...1.0.7](https://github.com/EduardoKrausME/moodle-mod_videodiagnostic/compare/1.0.6...1.0.7)
