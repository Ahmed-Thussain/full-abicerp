<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="panel_s">
        <div class="panel-body">
          <h4 class="no-margin font-bold"><?php echo _l('acc_manual_rates'); ?>: <?php echo html_escape($from . ' ' . _l('pur_to') . ' ' . $to); ?></h4>
          <hr />
          <a href="<?php echo admin_url('accounting/setting?group=currency_rates'); ?>" class="btn btn-default mbot15"><?php echo _l('back'); ?></a>

          <table class="table table-bordered">
            <thead>
              <tr>
                <th><?php echo _l('acc_effective_from'); ?></th>
                <th><?php echo _l('acc_effective_to'); ?></th>
                <th><?php echo _l('pur_currency_rate'); ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($periods as $period) { ?>
                <tr>
                  <td><?php echo _dt($period['effective_from']); ?></td>
                  <td><?php echo $period['effective_to'] ? _dt($period['effective_to']) : ''; ?></td>
                  <td><?php echo html_escape($period['rate']); ?></td>
                  <td>
                    <a href="<?php echo admin_url('accounting/delete_rate_period/' . $period['id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>

          <hr />
          <?php echo form_open(admin_url('accounting/add_rate_period')); ?>
          <input type="hidden" name="from_currency_name" value="<?php echo html_escape($from); ?>">
          <input type="hidden" name="to_currency_name" value="<?php echo html_escape($to); ?>">
          <div class="row">
            <div class="col-md-3">
              <?php echo render_datetime_input('effective_from', 'acc_effective_from', '', ['required' => true]); ?>
            </div>
            <div class="col-md-3">
              <div class="checkbox checkbox-primary">
                <input type="checkbox" id="set_end_date">
                <label for="set_end_date"><?php echo _l('acc_set_end_date'); ?></label>
              </div>
              <div id="effective_to_wrapper" class="hide">
                <?php echo render_datetime_input('effective_to', 'acc_effective_to'); ?>
              </div>
            </div>
            <div class="col-md-3">
              <?php echo render_input('rate', 'pur_currency_rate', '', 'number', ['step' => 'any', 'min' => '0.00000001', 'required' => true]); ?>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-info mtop25"><?php echo _l('submit'); ?></button>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
  $('#set_end_date').on('change', function() {
    $('#effective_to_wrapper').toggleClass('hide', !this.checked);
    if (!this.checked) {
      $('input[name="effective_to"]').val('');
    }
  });
</script>
