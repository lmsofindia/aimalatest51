<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\meeting;

/**
 * A recording file the meeting provider holds, before offload. The unique
 * $uuid is what guarantees the pipeline offloads it exactly once.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_asset {
    /** @var string Globally-unique recording id (idempotency key). */
    public string $uuid;
    /** @var string File type, e.g. 'MP4', 'TRANSCRIPT', 'CHAT'. */
    public string $filetype;
    /** @var string Download URL (may need a token, see download_ref). */
    public string $downloadurl;
    /** @var int Size bytes (0 if unknown). */
    public int $sizebytes;
    /** @var int Recording start time. */
    public int $starttime;
    /** @var string|null Human label. */
    public ?string $label;

    public function __construct(
        string $uuid,
        string $filetype,
        string $downloadurl,
        int $sizebytes = 0,
        int $starttime = 0,
        ?string $label = null
    ) {
        $this->uuid = $uuid;
        $this->filetype = $filetype;
        $this->downloadurl = $downloadurl;
        $this->sizebytes = $sizebytes;
        $this->starttime = $starttime;
        $this->label = $label;
    }

    public function is_video(): bool {
        return strtoupper($this->filetype) === 'MP4' || strtoupper($this->filetype) === 'SHARED_SCREEN_WITH_SPEAKER_VIEW';
    }
    public function is_transcript(): bool {
        return in_array(strtoupper($this->filetype), ['TRANSCRIPT', 'CC', 'VTT'], true);
    }
}
