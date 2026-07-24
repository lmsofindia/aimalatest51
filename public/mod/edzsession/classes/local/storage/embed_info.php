<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\storage;

/**
 * Everything the student view needs to render a player, provider-neutral.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class embed_info {
    /** Provider gives a full <iframe> src (Vimeo). */
    const KIND_IFRAME = 'iframe';
    /** Provider gives a direct media URL for a <video> tag (S3/CDN). */
    const KIND_VIDEO = 'video';

    /** @var string KIND_IFRAME|KIND_VIDEO. */
    public string $kind;
    /** @var string The src/url to embed. */
    public string $url;
    /** @var array Extra attributes (width, height, poster, ...). */
    public array $attrs;

    public function __construct(string $kind, string $url, array $attrs = []) {
        $this->kind = $kind;
        $this->url = $url;
        $this->attrs = $attrs;
    }
}
