<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\storage;

/**
 * Immutable description of a recording to be offloaded.
 *
 * Passed to storage_provider::begin_upload(). Adding a field here is
 * backwards-safe because every driver receives the whole object.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_request {
    /** @var string Title to give the stored asset. */
    public string $title;
    /** @var string|null Description/notes. */
    public ?string $description;
    /** @var string|null Direct source URL for pull upload (with token if needed). */
    public ?string $sourceurl;
    /** @var int|null Expected size in bytes (for stream/quota checks), null if unknown. */
    public ?int $sizebytes;
    /** @var string|null Preferred destination folder id. */
    public ?string $folderid;
    /** @var string|null MIME type, e.g. 'video/mp4'. */
    public ?string $mimetype;
    /** @var int Recording pipeline row id (for logging/idempotency). */
    public int $recordingid;

    public function __construct(
        string $title,
        int $recordingid,
        ?string $sourceurl = null,
        ?int $sizebytes = null,
        ?string $folderid = null,
        ?string $mimetype = 'video/mp4',
        ?string $description = null
    ) {
        $this->title = $title;
        $this->recordingid = $recordingid;
        $this->sourceurl = $sourceurl;
        $this->sizebytes = $sizebytes;
        $this->folderid = $folderid;
        $this->mimetype = $mimetype;
        $this->description = $description;
    }
}
