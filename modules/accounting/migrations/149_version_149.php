<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_149 extends App_module_migration
{
    public function up()
    {
        $CI      = &get_instance();
        $history = db_prefix() . 'acc_account_history';

        if (!$CI->db->field_exists('doc_rate', $history) || !$CI->db->field_exists('sec_currency', $history)) {
            return;
        }

        $CI->load->model('currencies_model');
        $base      = $CI->currencies_model->get_base_currency();
        $base_name = $base ? strtoupper($base->name) : '';

        // Step 1: document currency = base currency, rate 1.
        $CI->db->query('UPDATE `' . $history . '`
            SET `doc_currency` = ?, `doc_rate` = 1, `doc_debit` = `debit`, `doc_credit` = `credit`
            WHERE `doc_rate` IS NULL', [$base_name]);

        // Step 2: secondary currency, only when the feature is on and configured.
        $secondary_id = get_option('acc_secondary_currency_id');
        if (get_option('acc_enable_secondary_currency') != 1 || $secondary_id == '' || !$base) {
            return;
        }
        $secondary = $CI->currencies_model->get($secondary_id);
        if (!$secondary) {
            return;
        }
        $sec_name = strtoupper($secondary->name);

        $current      = $CI->db->where('from_currency_name', $base_name)
            ->where('to_currency_name', $sec_name)
            ->get(db_prefix() . 'currency_rates')->row();
        $current_rate = $current ? (float) $current->to_currency_rate : null;

        while (true) {
            $rows = $CI->db->select('id, date, debit, credit')
                ->where('sec_currency IS NULL', null, false)
                ->order_by('id', 'ASC')
                ->limit(1000)
                ->get($history)->result_array();
            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $log = $CI->db->where('from_currency_name', $base_name)
                    ->where('to_currency_name', $sec_name)
                    ->where('date <=', $row['date'])
                    ->order_by('date', 'DESC')
                    ->limit(1)
                    ->get(db_prefix() . 'currency_rate_logs')->row();

                if ($log) {
                    $rate = (float) $log->to_currency_rate;
                    $rule = 'log';
                } elseif ($current_rate !== null) {
                    $rate = $current_rate;
                    $rule = 'current';
                } else {
                    // No rate at all: mark the row (so the loop ends) without amounts.
                    $CI->db->where('id', $row['id'])->update($history, ['sec_currency' => $sec_name]);
                    continue;
                }

                $CI->db->where('id', $row['id'])->update($history, [
                    'sec_currency' => $sec_name,
                    'sec_debit'    => round($row['debit'] * $rate, 4),
                    'sec_credit'   => round($row['credit'] * $rate, 4),
                ]);
                $CI->db->insert(db_prefix() . 'acc_rate_backfill_report', [
                    'history_id'   => $row['id'],
                    'rule'         => $rule,
                    'rate'         => $rate,
                    'sec_currency' => $sec_name,
                ]);
            }
        }
    }
}
