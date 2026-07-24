<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\storage;

/**
 * Result of poll_processing(): where the asset is in the provider's own
 * transcode/verification pipeline. The offload state machine reads .state.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processing_status {
    /** Still uploading/ingesting on the provider side. */
    const PENDING = 'pending';
    /** Transcoding / processing in progress. */
    const PROCESSING = 'processing';
    /** Ready to play. */
    const COMPLETE = 'complete';
    /** Provider reported a hard error. */
    const ERROR = 'error';

    /** @var string One of the constants above. */
    public string $state;
    /** @var int 0-100 best-effort progress. */
    public int $percent;
    /** @var string|null Provider message on error. */
    public ?string $message;

    public function __construct(string $state, int $percent = 0, ?string $message = null) {
        $this->state = $state;
        $this->percent = $percent;
        $this->message = $message;
    }

    public function is_complete(): bool {
        return $this->state === self::COMPLETE;
    }
    public function is_error(): bool {
        return $this->state === self::ERROR;
    }
}
