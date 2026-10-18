<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$secondary = $data_report['secondary'];
$column_count = $secondary !== null ? 14 : 8;
$amount_keys = ['open_dr', 'open_cr', 'mov_dr', 'mov_cr', 'close_dr', 'close_cr'];
$node_id_of = [];
$account_at_level = [];
?>
<div id="accordion">
  <div class="card">
    <?php $this->load->view('report/includes/report_currency_bar'); ?>
    <table class="tree">
      <tbody>
        <tr>
          <td colspan="<?php echo $column_count; ?>">
            <h3 class="text-center"><?php echo html_escape(get_option('companyname')); ?></h3>
          </td>
        </tr>
        <tr>
          <td colspan="<?php echo $column_count; ?>">
            <h4 class="text-center"><?php echo _l('trial_balance'); ?></h4>
          </td>
        </tr>
        <tr>
          <td colspan="<?php echo $column_count; ?>">
            <p class="text-center"><?php echo _d($data_report['from_date']).' - '._d($data_report['to_date']); ?></p>
          </td>
        </tr>
        <?php if (!empty($report_currency_info)) { ?>
        <tr>
          <td colspan="<?php echo $column_count; ?>">
            <p class="text-center"><?php echo _l('acc_report_currency').': '.html_escape($currency->name); ?></p>
          </td>
        </tr>
        <?php } ?>
        <tr class="tr_header text-bold">
          <td><?php echo _l('acc_tb_code'); ?></td>
          <td><?php echo _l('acc_account'); ?></td>
          <td colspan="2" class="th_total"><?php echo _l('acc_tb_opening'); ?></td>
          <td colspan="2" class="th_total"><?php echo _l('acc_tb_movement'); ?></td>
          <td colspan="2" class="th_total"><?php echo _l('acc_tb_closing'); ?></td>
          <?php if ($secondary !== null) { ?>
          <td colspan="6" class="th_total"><?php echo html_escape($secondary['name']); ?></td>
          <?php } ?>
        </tr>
        <tr class="tr_header text-bold">
          <td></td>
          <td></td>
          <?php for ($pair = 0; $pair < 3; $pair++) { ?>
          <td class="th_total"><?php echo _l('debit'); ?></td>
          <td class="th_total"><?php echo _l('credit'); ?></td>
          <?php } ?>
          <?php if ($secondary !== null) { ?>
          <?php foreach (['acc_tb_opening', 'acc_tb_movement', 'acc_tb_closing'] as $label) { ?>
          <td class="th_total"><?php echo _l($label).' '._l('debit'); ?></td>
          <td class="th_total"><?php echo _l($label).' '._l('credit'); ?></td>
          <?php } ?>
          <?php } ?>
        </tr>
        <?php foreach ($data_report['rows'] as $index => $row) {
            $node_id = $index + 1;
            $node_id_of[$row['id']] = $node_id;
            $account_at_level[$row['level']] = $row['id'];
            $parent_node_id = $row['level'] > 0 ? $node_id_of[$account_at_level[$row['level'] - 1]] : null;
            $register_url = admin_url('accounting/user_register_view/'.$row['id'].'?from_date='.$data_report['from_date'].'&to_date='.$data_report['to_date']);
        ?>
        <tr data-node-id="<?php echo $node_id; ?>"<?php if ($parent_node_id !== null) { ?> data-node-pid="<?php echo $parent_node_id; ?>"<?php } ?><?php if ($row['has_children']) { ?> class="tr_parent text-bold"<?php } ?>>
          <td><?php echo html_escape($row['number']); ?></td>
          <td><?php echo str_repeat('&nbsp;', $row['level'] * 4); ?><a href="<?php echo html_escape($register_url); ?>"><?php echo html_escape($row['name']); ?></a></td>
          <?php foreach ($amount_keys as $key) { ?>
          <td class="total_amount"><?php echo $row[$key] != 0 ? app_format_money($row[$key], $currency->name) : ''; ?></td>
          <?php } ?>
          <?php if ($secondary !== null) { ?>
          <?php foreach ($amount_keys as $key) { ?>
          <td class="total_amount"><?php echo $row['s_'.$key] != 0 ? app_format_money($row['s_'.$key], $secondary['name']) : ''; ?></td>
          <?php } ?>
          <?php } ?>
        </tr>
        <?php } ?>
        <tr class="tr_total">
          <td colspan="2"><?php echo _l('total'); ?></td>
          <?php foreach ($amount_keys as $key) { ?>
          <td class="total_amount"><?php echo app_format_money($data_report['total'][$key], $currency->name); ?></td>
          <?php } ?>
          <?php if ($secondary !== null) { ?>
          <?php foreach ($amount_keys as $key) { ?>
          <td class="total_amount"><?php echo app_format_money($data_report['total']['s_'.$key], $secondary['name']); ?></td>
          <?php } ?>
          <?php } ?>
        </tr>
      </tbody>
    </table>
  </div>
</div>
