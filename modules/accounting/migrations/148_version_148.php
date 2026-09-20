<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_148 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Per-pair switch: 1 = use the live/auto rate, 0 = use manual rate periods.
        if (!$CI->db->field_exists('auto_rate', db_prefix() . 'currency_rates')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'currency_rates`
                ADD COLUMN `auto_rate` TINYINT(1) NOT NULL DEFAULT 1;');
        }

        // Manual rates with an effective window.
        if (!$CI->db->table_exists(db_prefix() . 'acc_currency_rate_periods')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'acc_currency_rate_periods` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `from_currency_name` VARCHAR(100) NULL,
                `to_currency_name` VARCHAR(100) NULL,
                `rate` DECIMAL(15,6) NOT NULL,
                `effective_from` DATETIME NOT NULL,
                `effective_to` DATETIME NULL,
                `created_by` INT NULL,
                `datecreated` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `pair_from_idx` (`from_currency_name`, `to_currency_name`, `effective_from`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ($CI->db->char_set == 'utf8mb4' ? ' COLLATE utf8mb4_general_ci' : '') . ';');
        }

        // Document / secondary currency amounts stored on each ledger line.
        $history = db_prefix() . 'acc_account_history';
        $columns = [
            'doc_currency' => 'VARCHAR(20) NULL',
            'doc_rate'     => 'DECIMAL(15,6) NULL',
            'doc_debit'    => 'DECIMAL(20,4) NULL',
            'doc_credit'   => 'DECIMAL(20,4) NULL',
            'sec_currency' => 'VARCHAR(20) NULL',
            'sec_debit'    => 'DECIMAL(20,4) NULL',
            'sec_credit'   => 'DECIMAL(20,4) NULL',
        ];
        foreach ($columns as $name => $definition) {
            if (!$CI->db->field_exists($name, $history)) {
                $CI->db->query('ALTER TABLE `' . $history . '` ADD COLUMN `' . $name . '` ' . $definition . ';');
            }
        }

        // Report of which rate the backfill used for each old ledger line.
        if (!$CI->db->table_exists(db_prefix() . 'acc_rate_backfill_report')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'acc_rate_backfill_report` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `history_id` INT NOT NULL,
                `rule` VARCHAR(20) NULL,
                `rate` DECIMAL(15,6) NULL,
                `sec_currency` VARCHAR(20) NULL,
                `datecreated` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ($CI->db->char_set == 'utf8mb4' ? ' COLLATE utf8mb4_general_ci' : '') . ';');
        }
    }
}
