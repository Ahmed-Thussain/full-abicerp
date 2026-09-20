<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_147 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Secondary (Arabic) name for classes, shown when the staff member's
        // language is Arabic. Nullable: existing classes keep only their
        // first-language name and fall back to it.
        if (!$CI->db->field_exists('name_ar', db_prefix() . 'acc_class')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'acc_class`
                ADD COLUMN `name_ar` TEXT NULL AFTER `name`;');
        }

        // Class is now recorded once per journal entry instead of per line,
        // matching how bills, checks, pay bills and transfers already store it.
        if (!$CI->db->field_exists('acc_class', db_prefix() . 'acc_journal_entries')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'acc_journal_entries`
                ADD COLUMN `acc_class` INT(11) NULL DEFAULT 0;');

            // Backfill from the entry's first ledger line so existing entries
            // open with the class they were saved with.
            $CI->db->query('UPDATE `' . db_prefix() . 'acc_journal_entries` je
                SET je.acc_class = COALESCE((
                    SELECT ah.class
                    FROM `' . db_prefix() . 'acc_account_history` ah
                    WHERE ah.rel_type = "journal_entry"
                      AND ah.rel_id = je.id
                    ORDER BY ah.id ASC
                    LIMIT 1
                ), 0);');
        }
    }
}
