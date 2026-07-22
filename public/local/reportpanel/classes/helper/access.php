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

namespace local_reportpanel\helper;

/**
 * Central access + card-visibility logic for the report panel.
 *
 * "Full mode" (local/reportpanel:viewall) = admins and HR managers: all-user data,
 * selection forms and the user search. Otherwise "self mode": the viewer sees only
 * their own records and reduced cards.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access {

    /** @var array Shipped default URLs for external cards (must match settings.php). */
    const DEFAULT_URLS = [
        'leaderboard' => '/blocks/xp/index.php/ladder/1',
        'logreport'   => '/report/log/index.php?id=0',
        'teamreports' => '/local/edzteams/index.php',
        'badges'      => '/badges/mybadges.php',
    ];

    /**
     * System context shortcut.
     *
     * @return \context_system
     */
    public static function context(): \context_system {
        return \context_system::instance();
    }

    /**
     * Is the current user in full mode (admin / HR manager)?
     *
     * @return bool
     */
    public static function is_full_mode(): bool {
        return has_capability('local/reportpanel:viewall', self::context());
    }

    /**
     * Has the current user any of the given capabilities at system context?
     * Capabilities that are not defined on this site (e.g. a plugin isn't installed)
     * are skipped rather than throwing.
     *
     * @param string[] $caps
     * @return bool
     */
    public static function has_any_cap(array $caps): bool {
        $context = self::context();
        foreach ($caps as $cap) {
            if (get_capability_info($cap) && has_capability($cap, $context)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Require that the viewer may see this target user's data. In self mode the only
     * allowed target is the viewer; full mode may view anyone.
     *
     * @param int $targetuserid
     * @return int the userid that may actually be viewed
     */
    public static function resolve_target_user(int $targetuserid): int {
        global $USER;
        if (self::is_full_mode()) {
            return $targetuserid > 0 ? $targetuserid : 0;
        }
        return (int)$USER->id;
    }

    /**
     * Build the ordered, visibility-filtered card list for the panel grid.
     *
     * @return array list of card contexts for the Mustache template
     */
    public static function cards(): array {
        // Definition table: key, icon (FontAwesome), internal page?, and an optional
        // 'requires' list — the capability(ies) the TARGET page actually enforces.
        // A card is shown only if the viewer holds (one of) those capabilities, so we
        // never display a card that would lead to "access denied". Cards with no
        // 'requires' are available to everyone who can view the panel (self-service).
        $defs = [
            [
                'key' => 'leaderboard', 'icon' => 'fa-trophy', 'internal' => false,
            ],
            [
                'key' => 'quizreports', 'icon' => 'fa-square-poll-vertical',
                'internal' => true, 'page' => 'quiz.php',
            ],
            [
                'key' => 'certreports', 'icon' => 'fa-certificate',
                'internal' => true, 'page' => 'certificate.php',
            ],
            [
                'key' => 'consolidated', 'icon' => 'fa-id-card',
                'internal' => true, 'page' => 'consolidated.php',
            ],
            [
                'key' => 'courseconsolidated', 'icon' => 'fa-chart-pie',
                'internal' => true, 'page' => 'courseconsolidated.php',
                'requires' => ['local/reportpanel:viewall'],
            ],
            [
                'key' => 'engagement', 'icon' => 'fa-clock',
                'internal' => true, 'page' => 'engagement.php',
                'requires' => ['local/reportpanel:viewall'],
            ],
            [
                'key' => 'rankings', 'icon' => 'fa-ranking-star',
                'internal' => true, 'page' => 'rankings.php',
                'requires' => ['local/reportpanel:viewall'],
            ],
            [
                'key' => 'teamreports', 'icon' => 'fa-users', 'internal' => false,
                'requires' => ['local/edzteams:viewteam', 'local/edzteams:viewall'],
            ],
            [
                'key' => 'badges', 'icon' => 'fa-award', 'internal' => false,
            ],
            [
                'key' => 'overview', 'icon' => 'fa-chart-line', 'internal' => true,
                'page' => 'overview.php', 'requires' => ['local/reportpanel:viewall'],
            ],
            [
                'key' => 'logreport', 'icon' => 'fa-list-ul', 'internal' => false,
                'requires' => ['report/log:view', 'moodle/site:viewreports'],
            ],
        ];

        // Which section each card belongs to on the hub.
        $groups = [
            'leaderboard' => 'self', 'quizreports' => 'self', 'certreports' => 'self',
            'consolidated' => 'self', 'badges' => 'self',
            'courseconsolidated' => 'manager', 'engagement' => 'manager', 'rankings' => 'manager',
            'overview' => 'manager', 'teamreports' => 'manager', 'logreport' => 'manager',
        ];

        $cards = [];
        foreach ($defs as $def) {
            // Capability gate: only show the card if the viewer can actually open it.
            if (!empty($def['requires']) && !self::has_any_cap($def['requires'])) {
                continue;
            }
            $key = $def['key'];
            if (!empty($def['internal'])) {
                $url = new \moodle_url('/local/reportpanel/' . $def['page']);
            } else {
                // External card: honour the per-card enable toggle and configured URL.
                // Unset config (fresh install) defaults to enabled with the shipped URL.
                $enable = get_config('local_reportpanel', 'enable_' . $key);
                $enabled = ($enable === false) ? true : (bool)(int)$enable;
                if (!$enabled) {
                    continue;
                }
                $raw = trim((string)get_config('local_reportpanel', 'url_' . $key));
                if ($raw === '') {
                    $raw = self::DEFAULT_URLS[$key] ?? '';
                }
                if ($raw === '') {
                    continue;
                }
                $url = self::make_url($raw);
            }
            $cards[] = [
                'key' => $key,
                'title' => get_string('card_' . $key, 'local_reportpanel'),
                'desc' => get_string('carddesc_' . $key, 'local_reportpanel'),
                'icon' => $def['icon'],
                'url' => $url->out(false),
                'group' => $groups[$key] ?? 'self',
            ];
        }
        return $cards;
    }

    /**
     * Turn a configured URL (relative path or absolute) into a moodle_url.
     *
     * @param string $raw
     * @return \moodle_url
     */
    protected static function make_url(string $raw): \moodle_url {
        if (preg_match('#^https?://#i', $raw)) {
            return new \moodle_url($raw);
        }
        // Relative to wwwroot; preserve any query string.
        return new \moodle_url($raw);
    }
}
