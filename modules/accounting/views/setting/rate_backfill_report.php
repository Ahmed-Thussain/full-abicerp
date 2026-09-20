<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="panel_s">
        <div class="panel-body">
          <h4 class="no-margin font-bold"><?php echo html_escape($title); ?></h4>
          <hr />
          <table class="table table-bordered">
            <thead>
              <tr>
                <th><?php echo _l('acc_history_id'); ?></th>
                <th><?php echo _l('acc_rate_rule'); ?></th>
                <th><?php echo _l('pur_currency_rate'); ?></th>
                <th><?php echo _l('currency'); ?></th>
                <th><?php echo _l('date'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row) { ?>
                <tr>
                  <td><?php echo (int) $row['history_id']; ?></td>
                  <td><?php echo html_escape($row['rule']); ?></td>
                  <td><?php echo html_escape($row['rate']); ?></td>
                  <td><?php echo html_escape($row['sec_currency']); ?></td>
                  <td><?php echo html_escape($row['history_date']); ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
