<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\pipeline;

/**
 * Offload pipeline states + terminal-set helpers.
 *
 * discovered -> selected -> quota_checked -> uploading -> processing
 *   -> verified -> finalized -> source_deleted        (failed = retryable stop)
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class states {
    const DISCOVERED = 'discovered';
    const SELECTED = 'selected';
    const QUOTA_CHECKED = 'quota_checked';
    const UPLOADING = 'uploading';
    const PROCESSING = 'processing';
    const VERIFIED = 'verified';
    /** Copy is finalized; waiting to decide/perform source deletion. */
    const PENDING_DELETE = 'pending_delete';
    const FINALIZED = 'finalized';
    const SOURCE_DELETED = 'source_deleted';
    const FAILED = 'failed';

    /** States that need no further work. FINALIZED = kept, source not deleted. */
    const TERMINAL = [self::FINALIZED, self::SOURCE_DELETED, self::FAILED];

    /** Max attempts on a single step before parking at FAILED. */
    const MAX_ATTEMPTS = 5;

    public static function is_terminal(string $state): bool {
        return in_array($state, self::TERMINAL, true);
    }
}
