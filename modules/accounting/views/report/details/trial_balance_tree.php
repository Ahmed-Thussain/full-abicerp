<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$secondary = $data_report['secondary'];
$column_count = $secondary !== null ? 14 : 8;
$amount_keys = ['open_dr', 'open_cr', 'mov_dr', 'mov_cr', 'close_dr', 'close_cr'];
$node_id_of = [];
$account_at_level = [];
?>
<?php /* screen styles stay outside #accordion: printDiv() sends only its inner HTML to the PDF */ ?>
<style>
  /* 8 columns (14 with the secondary currency) need an A4 landscape page */
  #DivIdToPrint.page { width: 1123px; max-width: 100%; }
  .acc-tb-tree { width: 100%; font-size: 11px; }
  .acc-tb-tree td { padding: 2px 4px !important; white-space: nowrap; }
  .acc-tb-tree td h3, .acc-tb-tree td h4, .acc-tb-tree td p { white-space: normal; }
</style>
<div id="accordion">
  <div class="card">
    <?php $this->load->view('report/includes/report_currency_bar'); ?>
    <div style="overflow-x: auto;">
    <table class="tree acc-tb-tree">
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
            <p class="text-center"><span dir="ltr"><?php echo _d($data_report['from_date']).' - '._d($data_report['to_date']); ?></span></p>
          </td>
        </tr>
        <tr>
          <td colspan="<?php echo $column_count; ?>">
            <p class="text-center"><?php echo _l('acc_report_currency').': '.html_escape($currency->name); ?></p>
          </td>
        </tr>
        <tr class="tr_header text-bold" style="text-align: center;">
          <td><?php echo _l('acc_tb_code'); ?></td>
          <td><?php echo _l('acc_account'); ?></td>
          <td colspan="2" class="th_total text-center"><?php echo _l('acc_tb_opening'); ?></td>
          <td colspan="2" class="th_total text-center"><?php echo _l('acc_tb_movement'); ?></td>
          <td colspan="2" class="th_total text-center"><?php echo _l('acc_tb_closing'); ?></td>
          <?php if ($secondary !== null) { ?>
          <?php foreach (['acc_tb_opening', 'acc_tb_movement', 'acc_tb_closing'] as $label) { ?>
          <td colspan="2" class="th_total text-center"><?php echo _l($label).' ('.html_escape($secondary['name']).')'; ?></td>
          <?php } ?>
          <?php } ?>
        </tr>
        <tr class="tr_header text-bold" style="text-align: center;">
          <td></td>
          <td></td>
          <?php for ($pair = 0; $pair < 3; $pair++) { ?>
          <td class="th_total text-center"><?php echo _l('debit'); ?></td>
          <td class="th_total text-center"><?php echo _l('credit'); ?></td>
          <?php } ?>
          <?php if ($secondary !== null) { ?>
          <?php for ($pair = 0; $pair < 3; $pair++) { ?>
          <td class="th_total text-center"><?php echo _l('debit'); ?></td>
          <td class="th_total text-center"><?php echo _l('credit'); ?></td>
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
          <td class="total_amount"><?php echo $row[$key] != 0 ? app_format_number($row[$key]) : ''; ?></td>
          <?php } ?>
          <?php if ($secondary !== null) { ?>
          <?php foreach ($amount_keys as $key) { ?>
          <td class="total_amount"><?php echo $row['s_'.$key] != 0 ? app_format_number($row['s_'.$key]) : ''; ?></td>
          <?php } ?>
          <?php } ?>
        </tr>
        <?php } ?>
        <tr class="tr_total">
          <td colspan="2"><?php echo _l('total'); ?></td>
          <?php foreach ($amount_keys as $key) { ?>
          <td class="total_amount"><?php echo app_format_number($data_report['total'][$key]); ?></td>
          <?php } ?>
          <?php if ($secondary !== null) { ?>
          <?php foreach ($amount_keys as $key) { ?>
          <td class="total_amount"><?php echo app_format_number($data_report['total']['s_'.$key]); ?></td>
          <?php } ?>
          <?php } ?>
        </tr>
      </tbody>
    </table>
    </div>
  </div>
</div>
