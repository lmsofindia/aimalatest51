<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local;

use mod_edzsession\local\meeting\account;

/**
 * Encrypted CRUD over the multi-account meeting credential vault.
 *
 * Secrets (client secret, verification token) are encrypted at rest with
 * Moodle's core encryption (key in moodledata). Decrypted values only ever live
 * in memory inside an {@see account} value object and must never be logged.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class account_vault {

    /** @return \stdClass[] raw DB rows (secrets still encrypted) for listing. */
    public static function list_rows(bool $enabledonly = false): array {
        global $DB;
        $conditions = $enabledonly ? ['enabled' => 1] : [];
        return $DB->get_records('edzsession_account', $conditions, 'name ASC');
    }

    /** @return array<int,string> id => name, for form menus. */
    public static function menu(bool $enabledonly = true): array {
        $menu = [];
        foreach (self::list_rows($enabledonly) as $row) {
            $menu[$row->id] = $row->name;
        }
        return $menu;
    }

    /**
     * Load one account as a decrypted value object.
     *
     * @param int $id
     * @return account
     */
    public static function get(int $id): account {
        global $DB;
        $row = $DB->get_record('edzsession_account', ['id' => $id], '*', MUST_EXIST);
        return new account(
            (int) $row->id,
            $row->name,
            $row->provider,
            $row->accountid,
            $row->clientid,
            self::decrypt($row->clientsecretenc),
            $row->verificationtokenenc !== null && $row->verificationtokenenc !== ''
                ? self::decrypt($row->verificationtokenenc) : null
        );
    }

    /**
     * Create or update an account from form data. Secrets are encrypted here.
     * An empty secret on edit means "keep existing" (form shows a blank field).
     *
     * @param \stdClass $data form data
     * @return int account id
     */
    public static function save(\stdClass $data): int {
        global $DB;
        $now = time();

        $record = new \stdClass();
        $record->name = $data->name;
        $record->provider = $data->provider ?? 'zoom';
        $record->accountid = $data->accountid;
        $record->clientid = $data->clientid;
        $record->enabled = !empty($data->enabled) ? 1 : 0;
        $record->timemodified = $now;

        if (!empty($data->id)) {
            $record->id = (int) $data->id;
            $existing = $DB->get_record('edzsession_account', ['id' => $record->id], '*', MUST_EXIST);
            // Only re-encrypt secrets that were actually entered.
            $record->clientsecretenc = (isset($data->clientsecret) && $data->clientsecret !== '')
                ? self::encrypt($data->clientsecret) : $existing->clientsecretenc;
            $record->verificationtokenenc = (isset($data->verificationtoken) && $data->verificationtoken !== '')
                ? self::encrypt($data->verificationtoken) : $existing->verificationtokenenc;
            $DB->update_record('edzsession_account', $record);
            return $record->id;
        }

        $record->timecreated = $now;
        $record->clientsecretenc = self::encrypt($data->clientsecret ?? '');
        $record->verificationtokenenc = (isset($data->verificationtoken) && $data->verificationtoken !== '')
            ? self::encrypt($data->verificationtoken) : null;
        return (int) $DB->insert_record('edzsession_account', $record);
    }

    /** Delete an account. Activities referencing it keep their stored id (soft). */
    public static function delete(int $id): void {
        global $DB;
        $DB->delete_records('edzsession_account', ['id' => $id]);
    }

    // ---- Encryption helpers ----------------------------------------------

    private static function encrypt(string $plain): string {
        if ($plain === '') {
            return '';
        }
        // \core\encryption available since Moodle 3.11; key lives in moodledata.
        return \core\encryption::encrypt($plain);
    }

    private static function decrypt(string $cipher): string {
        if ($cipher === '') {
            return '';
        }
        return \core\encryption::decrypt($cipher);
    }
}
