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
            <?php if (isset($currencies) && isset($currency)) { ?>
            <div class="col-md-3">
              <label for="cf_currency"><?php echo _l('acc_report_currency'); ?></label>
              <select id="cf_currency" class="selectpicker" data-width="100%">
                <option value="<?php echo html_escape($currency->id); ?>"><?php echo html_escape($currency->name); ?></option>
                <?php foreach ($currencies as $__cf_currency_option) {
                  $__cf_option_id = is_array($__cf_currency_option) ? $__cf_currency_option['id'] : $__cf_currency_option->id;
                  $__cf_option_name = is_array($__cf_currency_option) ? $__cf_currency_option['name'] : $__cf_currency_option->name;
                  if ((string) $__cf_option_id === (string) $currency->id) {
                    continue;
                  } ?>
                  <option value="<?php echo html_escape($__cf_option_id); ?>"><?php echo html_escape($__cf_option_name); ?></option>
                <?php } ?>
              </select>
            </div>
            <?php } ?>
            <div class="col-md-6">
              <div class="pull-right mtop25">
                <a href="#" class="btn btn-default" id="cf_export_pdf"><i class="fa fa-file-pdf-o"></i> <?php echo _l('export_to_pdf'); ?></a>
                <a href="#" class="btn btn-default" id="cf_export_excel"><i class="fa fa-file-excel-o"></i> <?php echo _l('export_to_excel'); ?></a>
              </div>
            </div>
          </div>
          <hr />
          <div class="table-responsive" id="cf_print">
            <h4 class="text-center" id="cf_title"><?php echo html_escape(get_option('companyname')); ?> - <?php echo _l($title); ?></h4>
            <p class="text-center" id="cf_currency_line" style="display:none;"></p>
            <div class="alert alert-danger" id="cf_missing_banner" style="display:none;"></div>
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
    var cf_currency = $('#cf_currency').length ? $('#cf_currency').val() : '';
    $.post(admin_url + 'accounting/cashflow_monthly_data', {year: year, report_currency: cf_currency}, function (res) {
      var body = '', foot = '', m;
      var incomplete_mark = (res.missing_dates && res.missing_dates.length) ? '<?php echo _l('acc_report_incomplete_total'); ?>' : '';
      $.each(res.data, function (i, row) {
        body += '<tr><td>' + esc(row.account_name) + '</td>';
        for (m = 1; m <= 12; m++) { body += '<td class="text-right">' + fmt(row['m' + m]) + '</td>'; }
        body += '<td class="text-right"><strong>' + fmt(row.total) + '</strong></td></tr>';
      });
      foot = '<tr><td><strong><?php echo _l('total'); ?></strong></td>';
      for (m = 1; m <= 12; m++) { foot += '<td class="text-right"><strong>' + fmt(res.month_totals[m]) + incomplete_mark + '</strong></td>'; }
      foot += '<td class="text-right"><strong>' + fmt(res.total) + incomplete_mark + '</strong></td></tr>';
      $('#cf_table tbody').html(body);
      $('#cf_table tfoot').html(foot);
      if (res.report_currency) {
        $('#cf_currency_line').text('<?php echo _l('acc_report_currency'); ?>: ' + res.report_currency.name).show();
      } else {
        $('#cf_currency_line').hide();
      }
      if (res.missing_dates && res.missing_dates.length && res.report_currency) {
        var banner = '<?php echo addslashes(sprintf(_l('acc_report_missing_rate_banner'), '%BASE%', '%CUR%', '%N%', '%FROM%')); ?>';
        banner = banner.replace('%BASE%', res.report_currency.base_name).replace('%CUR%', res.report_currency.name).replace('%N%', res.missing_dates.length).replace('%FROM%', res.missing_dates[0]);
        banner += ' <a href="<?php echo admin_url('accounting/setting?group=currency_rates'); ?>"><?php echo _l('acc_report_add_rate'); ?></a>';
        $('#cf_missing_banner').html(banner).show();
      } else {
        $('#cf_missing_banner').hide();
      }
    }, 'json');
  }
  $(function () {
    $('#cf_year').on('change', load);
    $('#cf_currency').on('change', load);
    $('#cf_export_excel').on('click', function (e) {
      e.preventDefault();
      var url = admin_url + 'accounting/cashflow_monthly_export/' + $('#cf_year').val();
      if ($('#cf_currency').length && $('#cf_currency').val()) {
        url += '/' + $('#cf_currency').val();
      }
      window.location.href = url;
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
