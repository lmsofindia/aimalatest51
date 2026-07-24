<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\meeting;

/**
 * One credential set from the multi-account vault. Secrets arrive already
 * decrypted (the vault owns encryption); this object is never persisted as-is
 * and must never be logged.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class account {
    /** @var int Vault row id. */
    public int $id;
    /** @var string Friendly name shown in mod_form. */
    public string $name;
    /** @var string Meeting provider machine name (e.g. 'zoom'). */
    public string $provider;
    /** @var string Provider account id. */
    public string $accountid;
    /** @var string OAuth client id. */
    public string $clientid;
    /** @var string OAuth client secret (decrypted). */
    public string $clientsecret;
    /** @var string|null Webhook verification token (decrypted). */
    public ?string $verificationtoken;

    public function __construct(
        int $id,
        string $name,
        string $provider,
        string $accountid,
        string $clientid,
        string $clientsecret,
        ?string $verificationtoken = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->provider = $provider;
        $this->accountid = $accountid;
        $this->clientid = $clientid;
        $this->clientsecret = $clientsecret;
        $this->verificationtoken = $verificationtoken;
    }
}
