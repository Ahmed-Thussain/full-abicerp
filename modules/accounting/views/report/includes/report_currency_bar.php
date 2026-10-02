<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (isset($currencies) && isset($currency)) { ?>
<div class="col-md-3">
  <?php
  $__report_currency_options = [];
  $__report_currency_options[] = ['id' => $currency->id, 'name' => $currency->name];
  foreach ($currencies as $__report_currency_option) {
      $__option_id = is_array($__report_currency_option) ? $__report_currency_option['id'] : $__report_currency_option->id;
      $__option_name = is_array($__report_currency_option) ? $__report_currency_option['name'] : $__report_currency_option->name;
      if ((string) $__option_id === (string) $currency->id) {
          continue;
      }
      $__report_currency_options[] = ['id' => $__option_id, 'name' => $__option_name];
  }
  $__report_currency_selected = isset($report_currency) && $report_currency !== '' ? $report_currency : $currency->id;
  echo render_select('report_currency', $__report_currency_options, array('id', 'name'), 'acc_report_currency', $__report_currency_selected, array(), array(), '', '', false);
  ?>
</div>
<?php } ?>
<?php if (!empty($missing_rate_dates) && !empty($report_currency_info)) { ?>
<div class="alert alert-danger">
  <?php echo sprintf(_l('acc_report_missing_rate_banner'), $report_currency_info['base_name'], $report_currency_info['name'], count($missing_rate_dates), $missing_rate_dates[0]); ?>
  <a href="<?php echo admin_url('accounting/setting?group=currency_rates'); ?>"><?php echo _l('acc_report_add_rate'); ?></a>
</div>
<?php } ?>
