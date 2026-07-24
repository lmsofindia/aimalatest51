<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\storage;

/**
 * Opaque handle returned by begin_upload() and threaded through push_chunk()
 * / finalize_upload(). Each provider stores whatever it needs in $data.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_handle {
    /** Upload was started as a provider-side pull job. */
    const MODE_PULL = 'pull';
    /** Upload is a byte stream we push (tus / multipart). */
    const MODE_STREAM = 'stream';

    /** @var string One of MODE_*. */
    public string $mode;
    /** @var string|null Provider asset/video id if already known. */
    public ?string $assetid;
    /** @var string|null Upload/session endpoint (tus url, multipart uploadId, ...). */
    public ?string $uploadref;
    /** @var int Bytes pushed so far (stream mode). */
    public int $offset = 0;
    /** @var array Provider-specific scratch data (e.g. S3 part etags). */
    public array $data = [];

    public function __construct(string $mode, ?string $assetid = null, ?string $uploadref = null) {
        $this->mode = $mode;
        $this->assetid = $assetid;
        $this->uploadref = $uploadref;
    }
}
