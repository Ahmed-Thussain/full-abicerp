<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head();?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="panel_s">
        <div class="panel-body">
          <h4 class="no-margin font-bold"><?php echo _l($title); ?></h4>
          <a href="<?php echo admin_url('accounting/report'); ?>"><?php echo _l('back_to_report_list'); ?></a>
          <hr />
          <div class="row">
            <div class="col-md-3">
              <label for="cf_year"><?php echo _l('year'); ?></label>
              <select id="cf_year" class="selectpicker" data-width="100%">
                <?php foreach ($years as $y) { ?>
                  <option value="<?php echo html_escape($y); ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo html_escape($y); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="col-md-9">
              <div class="pull-right mtop25">
                <a href="#" class="btn btn-default" id="cf_export_pdf"><i class="fa fa-file-pdf-o"></i> <?php echo _l('export_to_pdf'); ?></a>
                <a href="#" class="btn btn-default" id="cf_export_excel"><i class="fa fa-file-excel-o"></i> <?php echo _l('export_to_excel'); ?></a>
              </div>
            </div>
          </div>
          <hr />
          <div class="table-responsive" id="cf_print">
            <h4 class="text-center" id="cf_title"><?php echo html_escape(get_option('companyname')); ?> - <?php echo _l($title); ?></h4>
            <table class="table table-bordered table-striped" id="cf_table">
              <thead>
                <tr>
                  <th><?php echo _l('acc_account'); ?></th>
                  <?php for ($m = 1; $m <= 12; $m++) { ?>
                    <th class="text-right"><?php echo date('M', mktime(0, 0, 0, $m, 1, 2000)); ?></th>
                  <?php } ?>
                  <th class="text-right"><?php echo _l('total'); ?></th>
                </tr>
              </thead>
              <tbody></tbody>
              <tfoot></tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php echo $this->load->view('report/includes/pdf_form'); ?>
<?php init_tail(); ?>
<script>
(function ($) {
  "use strict";
  function fmt(n) {
    return Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }
  function esc(s) {
    return $('<div>').text(s).html();
  }
  function load() {
    var year = $('#cf_year').val();
    $.post(admin_url + 'accounting/cashflow_monthly_data', {year: year}, function (res) {
      var body = '', foot = '', m;
      $.each(res.data, function (i, row) {
        body += '<tr><td>' + esc(row.account_name) + '</td>';
        for (m = 1; m <= 12; m++) { body += '<td class="text-right">' + fmt(row['m' + m]) + '</td>'; }
        body += '<td class="text-right"><strong>' + fmt(row.total) + '</strong></td></tr>';
      });
      foot = '<tr><td><strong><?php echo _l('total'); ?></strong></td>';
      for (m = 1; m <= 12; m++) { foot += '<td class="text-right"><strong>' + fmt(res.month_totals[m]) + '</strong></td>'; }
      foot += '<td class="text-right"><strong>' + fmt(res.total) + '</strong></td></tr>';
      $('#cf_table tbody').html(body);
      $('#cf_table tfoot').html(foot);
    }, 'json');
  }
  $(function () {
    $('#cf_year').on('change', load);
    $('#cf_export_excel').on('click', function (e) {
      e.preventDefault();
      window.location.href = admin_url + 'accounting/cashflow_monthly_export/' + $('#cf_year').val();
    });
    $('#cf_export_pdf').on('click', function (e) {
      e.preventDefault();
      $('input[name="html"]').val($('#cf_print').html());
      $('input[name="pdf_name"]').val('cashflow_monthly_' + $('#cf_year').val());
      $('input[name="orientation"]').val('landscape');
      $('#render_pdf-form').submit();
    });
    load();
  });
})(jQuery);
</script>
</body>
</html>
