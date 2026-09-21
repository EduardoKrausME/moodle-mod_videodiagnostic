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

use context_module;
use moodle_url;
use stdClass;

/**
 * Resolves diagnostic and explanatory video sources.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class video_helper {
    /**
     * Returns source options.
     *
     * @param bool $allownone Whether to include no-video option.
     * @return array
     */
    public static function source_options(bool $allownone): array {
        $options = [];
        if ($allownone) {
            $options['none'] = get_string('sourcenone', 'mod_videodiagnostic');
        }
        $options['upload'] = get_string('sourceupload', 'mod_videodiagnostic');
        $options['url'] = get_string('sourceurl', 'mod_videodiagnostic');
        $options['youtube'] = get_string('sourceyoutube', 'mod_videodiagnostic');
        $options['vimeo'] = get_string('sourcevimeo', 'mod_videodiagnostic');
        return $options;
    }

    /**
     * Builds template/player data for a video source.
     *
     * @param stdClass $activity Activity record.
     * @param context_module $context Module context.
     * @param bool $explanation Whether to resolve the explanatory video.
     * @return array
     */
    public static function player_data(stdClass $activity, context_module $context, bool $explanation = false): array {
        global $CFG;

        $source = $explanation ? $activity->explanationsource : $activity->videosource;
        $urlfield = $explanation ? 'explanationvideourl' : 'videourl';
        $filearea = $explanation ? 'explanationvideo' : 'video';

        if ($source === 'none') {
            return ['available' => false];
        }

        $data = [
            'available' => true,
            'source' => $source,
            'html5' => false,
            'youtube' => false,
            'vimeo' => false,
            'trackable' => true,
            'videourl' => '',
            'embedurl' => '',
            'videoid' => '',
        ];

        if ($source === 'upload') {
            $fs = get_file_storage();
            $files = $fs->get_area_files(
                $context->id,
                'mod_videodiagnostic',
                $filearea,
                0,
                'itemid, filepath, filename',
                false
            );
            if (!$files) {
                return ['available' => false];
            }
            $file = reset($files);
            $data['html5'] = true;
            $data['videourl'] = moodle_url::make_pluginfile_url(
                $context->id,
                'mod_videodiagnostic',
                $filearea,
                0,
                $file->get_filepath(),
                $file->get_filename(),
                false
            )->out(false);
            return $data;
        }

        $url = trim((string)($activity->{$urlfield} ?? ''));
        if ($url === '') {
            return ['available' => false];
        }

        if ($source === 'url') {
            $data['html5'] = true;
            $data['videourl'] = clean_param($url, PARAM_URL);
            return $data;
        }

        if ($source === 'youtube') {
            $videoid = self::youtube_id($url);
            if (!$videoid) {
                return ['available' => false];
            }
            $data['youtube'] = true;
            $data['videoid'] = $videoid;
            $origin = rawurlencode((new moodle_url($CFG->wwwroot))->out(false));
            $data['embedurl'] = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($videoid) .
                '?enablejsapi=1&rel=0&origin=' . $origin;
            return $data;
        }

        if ($source === 'vimeo') {
            $videoid = self::vimeo_id($url);
            if (!$videoid) {
                return ['available' => false];
            }
            $data['vimeo'] = true;
            $data['videoid'] = $videoid;
            $data['embedurl'] = 'https://player.vimeo.com/video/' . rawurlencode($videoid) . '?api=1&dnt=1';
            return $data;
        }

        return ['available' => false];
    }

    /**
     * Extracts a YouTube video id.
     *
     * @param string $url URL.
     * @return string|null
     */
    private static function youtube_id(string $url): ?string {
        $patterns = [
            '~youtu\.be/([A-Za-z0-9_-]{6,})~',
            '~youtube(?:-nocookie)?\.com/(?:watch\?[^#]*v=|embed/|shorts/)([A-Za-z0-9_-]{6,})~',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        if (preg_match('/^[A-Za-z0-9_-]{6,}$/', $url)) {
            return $url;
        }
        return null;
    }

    /**
     * Extracts a Vimeo numeric video id.
     *
     * @param string $url URL.
     * @return string|null
     */
    private static function vimeo_id(string $url): ?string {
        if (preg_match('~vimeo\.com/(?:video/)?([0-9]+)~', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/^[0-9]+$/', $url)) {
            return $url;
        }
        return null;
    }
}
