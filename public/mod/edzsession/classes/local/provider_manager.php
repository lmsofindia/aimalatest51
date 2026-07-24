<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local;

use mod_edzsession\local\storage\storage_provider;
use mod_edzsession\local\storage\null_storage_provider;
use mod_edzsession\local\meeting\meeting_provider;

/**
 * The single registry both extension points resolve through.
 *
 * ADDING A PROVIDER = add one line to STORAGE_DRIVERS or MEETING_DRIVERS and
 * drop the class. Nothing else in the plugin changes. That constraint is the
 * product feature: per-customer storage/meeting backends with no core edits.
 *
 * v2 seam: replace the const maps with core_component sub-plugin discovery
 * (mod_edzsession_store / _meet). The interfaces stay identical, so drivers
 * written today keep working.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider_manager {

    /** @var array<string,class-string<storage_provider>> */
    const STORAGE_DRIVERS = [
        'vimeo' => \mod_edzsession\local\storage\provider\vimeo_provider::class,
        's3'    => \mod_edzsession\local\storage\provider\s3_provider::class,
        // 'drive'   => ...   <- future: one line + one class, zero core edits.
        // 'youtube' => ...
        // 'bunny'   => ...
    ];

    /** @var array<string,class-string<meeting_provider>> */
    const MEETING_DRIVERS = [
        'zoom' => \mod_edzsession\local\meeting\provider\zoom_provider::class,
        // 'bbb'   => ...   <- future.
        // 'teams' => ...
    ];

    // ---- Storage ----------------------------------------------------------

    /** @return array<string,string> name => display name (incl. 'none'). */
    public static function storage_provider_menu(): array {
        $menu = ['none' => get_string('provider_none', 'mod_edzsession')];
        foreach (self::STORAGE_DRIVERS as $name => $class) {
            $menu[$name] = $class::get_display_name();
        }
        return $menu;
    }

    /** @return array<string,class-string<storage_provider>> */
    public static function storage_providers(): array {
        return self::STORAGE_DRIVERS;
    }

    /** Instantiate a storage provider by name; 'none'/unknown => null object. */
    public static function get_storage(?string $name): storage_provider {
        if ($name === null || $name === '' || $name === 'none' || !isset(self::STORAGE_DRIVERS[$name])) {
            return new null_storage_provider();
        }
        $class = self::STORAGE_DRIVERS[$name];
        return new $class();
    }

    /** The site default storage provider (from config), as an object. */
    public static function default_storage(): storage_provider {
        $name = get_config('mod_edzsession', 'defaultstorageprovider');
        return self::get_storage($name ?: 'none');
    }

    /**
     * Resolve the provider for a specific activity: per-activity override wins,
     * else site default. Callers never branch on provider name.
     */
    public static function storage_for_activity(\stdClass $edzsession): storage_provider {
        return self::get_storage(self::storage_name_for_activity($edzsession));
    }

    /** The resolved storage provider machine name for an activity ('vimeo'|'none'|...). */
    public static function storage_name_for_activity(\stdClass $edzsession): string {
        if (!empty($edzsession->storageprovider)) {
            return $edzsession->storageprovider;
        }
        return (string) (get_config('mod_edzsession', 'defaultstorageprovider') ?: 'none');
    }

    // ---- Meeting ----------------------------------------------------------

    /** @return array<string,string> name => display name. */
    public static function meeting_provider_menu(): array {
        $menu = [];
        foreach (self::MEETING_DRIVERS as $name => $class) {
            $menu[$name] = $class::get_display_name();
        }
        return $menu;
    }

    /** @return array<string,class-string<meeting_provider>> */
    public static function meeting_providers(): array {
        return self::MEETING_DRIVERS;
    }

    public static function get_meeting(?string $name): meeting_provider {
        $name = $name ?: get_config('mod_edzsession', 'defaultmeetingprovider') ?: 'zoom';
        if (!isset(self::MEETING_DRIVERS[$name])) {
            throw new \moodle_exception('unknownmeetingprovider', 'mod_edzsession', '', $name);
        }
        $class = self::MEETING_DRIVERS[$name];
        return new $class();
    }

    public static function default_meeting(): meeting_provider {
        return self::get_meeting(null);
    }
}
