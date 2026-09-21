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
 * tracker.js
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/notification'], function (Ajax, Notification) {
    const states = new Map();
    let youtubePromise = null;
    let vimeoPromise = null;

    const loadScript = (url, test) => new Promise((resolve, reject) => {
        if (test()) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = url;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = reject;
        document.head.appendChild(script);
    });

    const loadYouTube = () => {
        if (window.YT && window.YT.Player) {
            return Promise.resolve();
        }
        if (!youtubePromise) {
            youtubePromise = new Promise((resolve, reject) => {
                const previous = window.onYouTubeIframeAPIReady;
                window.onYouTubeIframeAPIReady = () => {
                    if (typeof previous === 'function') {
                        previous();
                    }
                    resolve();
                };
                loadScript('https://www.youtube.com/iframe_api', () => window.YT && window.YT.Player).catch(reject);
            });
        }
        return youtubePromise;
    };

    const loadVimeo = () => {
        if (window.Vimeo && window.Vimeo.Player) {
            return Promise.resolve();
        }
        if (!vimeoPromise) {
            vimeoPromise = loadScript('https://player.vimeo.com/api/player.js', () => window.Vimeo && window.Vimeo.Player);
        }
        return vimeoPromise;
    };

    const updateUi = (state, percent) => {
        const root = document.getElementById(state.config.playerid);
        if (!root) {
            return;
        }
        const bar = root.querySelector('[data-vd-progressbar]');
        const text = root.querySelector('[data-vd-progresstext]');
        const clean = Math.max(0, Math.min(100, Number(percent) || 0));
        if (bar) {
            bar.style.width = clean + '%';
            bar.parentElement.setAttribute('aria-valuenow', String(clean));
        }
        if (text) {
            text.textContent = clean.toFixed(1) + '%';
        }
    };

    const send = (state, position, duration, segmentStart, segmentEnd) => {
        if (!state.config.track) {
            return Promise.resolve();
        }
        return Ajax.call([{
            methodname: 'mod_videodiagnostic_update_progress',
            args: {
                id: state.config.cmid,
                position: position,
                duration: duration,
                segmentstart: segmentStart,
                segmentend: segmentEnd
            }
        }])[0].then((result) => {
            state.currentTime = result.lastposition;
            updateUi(state, result.percent);
            return result;
        }).catch(Notification.exception);
    };

    const sample = (state, position, duration, playing) => {
        position = Number(position) || 0;
        duration = Number(duration) || 0;
        state.currentTime = position;
        if (!playing) {
            state.lastSample = position;
            return;
        }
        if (state.lastSample === null) {
            state.lastSample = position;
            return;
        }
        const start = state.lastSample;
        const end = position;
        state.lastSample = position;
        const delta = end - start;
        if (delta <= 0 || delta > 15) {
            return;
        }
        state.pendingStart = state.pendingStart === null ? start : Math.min(state.pendingStart, start);
        state.pendingEnd = end;
        const now = Date.now();
        if (now - state.lastSent >= 4000) {
            const segmentStart = state.pendingStart;
            const segmentEnd = state.pendingEnd;
            state.pendingStart = null;
            state.pendingEnd = null;
            state.lastSent = now;
            send(state, position, duration, segmentStart, segmentEnd);
        }
    };

    const flush = (state, position, duration) => {
        state.currentTime = Number(position) || 0;
        if (state.pendingStart !== null && state.pendingEnd !== null) {
            const start = state.pendingStart;
            const end = state.pendingEnd;
            state.pendingStart = null;
            state.pendingEnd = null;
            state.lastSent = Date.now();
            send(state, state.currentTime, Number(duration) || 0, start, end);
        } else {
            send(state, state.currentTime, Number(duration) || 0, 0, 0);
        }
    };

    const initHtml5 = (state) => {
        const media = document.getElementById(state.config.playerid + '-media');
        if (!media) {
            return;
        }
        state.getTime = () => media.currentTime || 0;
        media.addEventListener('loadedmetadata', () => {
            if (state.config.lastposition > 0 && state.config.lastposition < media.duration - 1) {
                media.currentTime = state.config.lastposition;
            }
        });
        media.addEventListener('timeupdate', () => sample(state, media.currentTime, media.duration, !media.paused && !media.ended));
        media.addEventListener('pause', () => flush(state, media.currentTime, media.duration));
        media.addEventListener('ended', () => flush(state, media.currentTime, media.duration));
        window.addEventListener('pagehide', () => flush(state, media.currentTime, media.duration));
    };

    const initYouTube = (state) => {
        loadYouTube().then(() => {
            let interval = null;
            const player = new window.YT.Player(state.config.playerid + '-media', {
                videoId: state.config.videoid,
                playerVars: {rel: 0},
                events: {
                    onReady: () => {
                        state.getTime = () => player.getCurrentTime() || 0;
                        if (state.config.lastposition > 0) {
                            player.seekTo(state.config.lastposition, true);
                        }
                    },
                    onStateChange: (event) => {
                        if (event.data === window.YT.PlayerState.PLAYING) {
                            if (!interval) {
                                interval = window.setInterval(() => sample(
                                    state,
                                    player.getCurrentTime(),
                                    player.getDuration(),
                                    true
                                ), 1000);
                            }
                        } else {
                            if (interval) {
                                window.clearInterval(interval);
                                interval = null;
                            }
                            flush(state, player.getCurrentTime(), player.getDuration());
                        }
                    }
                }
            });
        }).catch(Notification.exception);
    };

    const initVimeo = (state) => {
        loadVimeo().then(() => {
            const iframe = document.getElementById(state.config.playerid + '-media');
            const player = new window.Vimeo.Player(iframe);
            let duration = 0;
            state.getTime = () => state.currentTime || 0;
            player.getDuration().then((value) => {
                duration = value;
            });
            if (state.config.lastposition > 0) {
                player.setCurrentTime(state.config.lastposition).catch(() => {
                });
            }
            player.on('timeupdate', (data) => {
                duration = data.duration || duration;
                sample(state, data.seconds, duration, true);
            });
            player.on('pause', (data) => flush(state, data.seconds, duration));
            player.on('ended', (data) => flush(state, data.seconds || duration, duration));
        }).catch(Notification.exception);
    };

    const bindMarkerButtons = (state) => {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-vd-mark-time]');
            if (!button) {
                return;
            }
            event.preventDefault();
            const targetName = button.getAttribute('data-vd-mark-time');
            const input = document.querySelector('[name="' + CSS.escape(targetName) + '"]');
            if (input) {
                const value = state.getTime ? state.getTime() : state.currentTime;
                input.value = (Number(value) || 0).toFixed(3);
                input.dispatchEvent(new Event('change', {bubbles: true}));
            }
        });
    };

    const init = (config) => {
        const state = {
            config: config,
            currentTime: Number(config.lastposition) || 0,
            lastSample: null,
            pendingStart: null,
            pendingEnd: null,
            lastSent: 0,
            getTime: null
        };
        states.set(config.playerid, state);
        updateUi(state, config.percent);
        if (config.source === 'upload' || config.source === 'url') {
            initHtml5(state);
        } else if (config.source === 'youtube') {
            initYouTube(state);
        } else if (config.source === 'vimeo') {
            initVimeo(state);
        }
        if (config.capturemarkers) {
            bindMarkerButtons(state);
        }
    };

    return {init: init};
});
