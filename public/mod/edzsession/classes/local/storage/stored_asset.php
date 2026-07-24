<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\storage;

/**
 * A persisted asset on a storage provider (a Vimeo video, an S3 object, ...).
 * Serialisable to/from the edzsession_recording.assetid + embedjson columns.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stored_asset {
    /** @var string Provider machine name that owns this asset. */
    public string $provider;
    /** @var string Provider asset id (video id / object key). */
    public string $assetid;
    /** @var string|null Folder/project/prefix the asset lives in. */
    public ?string $folderid;
    /** @var array Provider-specific metadata (uri, links, etags...). */
    public array $meta;

    public function __construct(string $provider, string $assetid, ?string $folderid = null, array $meta = []) {
        $this->provider = $provider;
        $this->assetid = $assetid;
        $this->folderid = $folderid;
        $this->meta = $meta;
    }

    /** @return string JSON for the DB column. */
    public function to_json(): string {
        return json_encode([
            'provider' => $this->provider,
            'assetid' => $this->assetid,
            'folderid' => $this->folderid,
            'meta' => $this->meta,
        ]);
    }

    /** Rebuild from the stored JSON. */
    public static function from_json(string $json): stored_asset {
        $d = json_decode($json, true) ?: [];
        return new self($d['provider'] ?? '', $d['assetid'] ?? '', $d['folderid'] ?? null, $d['meta'] ?? []);
    }
}
