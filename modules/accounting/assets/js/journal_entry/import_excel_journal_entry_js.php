<script>

  var journal_import_count_ok = 0;

  var journal_import_labels = {
    result: <?php echo json_encode(_l('_Result')); ?>,
    download_sample: <?php echo json_encode(_l('download_sample')); ?>,
    please_select_file: <?php echo json_encode(_l('acc_ji_select_file')); ?>,
    xlsx_required: <?php echo json_encode(_l('acc_ji_help_1')); ?>,
    fail: <?php echo json_encode(_l('something_went_wrong')); ?>,
    confirm: <?php echo json_encode(_l('acc_ji_confirm_import', '%s')); ?>,
    total_entries: <?php echo json_encode(_l('acc_ji_total_entries')); ?>,
    total_lines: <?php echo json_encode(_l('acc_ji_total_lines')); ?>,
    total_debit: <?php echo json_encode(_l('acc_ji_total_debit')); ?>,
    total_credit: <?php echo json_encode(_l('acc_ji_total_credit')); ?>,
    count_ok: <?php echo json_encode(_l('acc_ji_count_ok')); ?>,
    count_error: <?php echo json_encode(_l('acc_ji_count_error')); ?>,
    count_duplicate: <?php echo json_encode(_l('acc_ji_status_duplicate')); ?>,
    count_posted: <?php echo json_encode(_l('acc_ji_count_posted')); ?>,
    count_failed: <?php echo json_encode(_l('acc_ji_count_failed')); ?>,
    number: <?php echo json_encode(_l('number')); ?>,
    date: <?php echo json_encode(_l('journal_date')); ?>,
    reference: <?php echo json_encode(_l('reference')); ?>,
    debit: <?php echo json_encode(_l('debit')); ?>,
    credit: <?php echo json_encode(_l('credit')); ?>,
    status: <?php echo json_encode(_l('acc_ji_status')); ?>,
    problems: <?php echo json_encode(_l('acc_ji_problems')); ?>,
    status_ok: <?php echo json_encode(_l('acc_ji_status_ok')); ?>,
    status_error: <?php echo json_encode(_l('acc_ji_status_error')); ?>,
    status_duplicate: <?php echo json_encode(_l('acc_ji_status_duplicate')); ?>,
    status_warning: <?php echo json_encode(_l('acc_ji_status_warning')); ?>,
    download_error: <?php echo json_encode(_l('download_file_error')); ?>,
    go_to_list: <?php echo json_encode(_l('acc_ji_go_to_list')); ?>,
    acc_class: <?php echo json_encode(_l('acc_class')); ?>,
    new_classes: <?php echo json_encode(_l('acc_ji_new_classes')); ?>
  };

  function journal_import_escape(value) {
    "use strict";

    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function journal_import_money(value) {
    "use strict";

    return Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }

  function journal_import_summary_item(label, value) {
    "use strict";

    return '<li><strong>' + journal_import_escape(label) + ':</strong> ' + journal_import_escape(value) + '</li>';
  }

  function journal_import_restore_buttons() {
    "use strict";

    $('#checkfile').prop('disabled', false);
    $('#importfile').prop('disabled', !(journal_import_count_ok > 0));
  }

  function journal_import_render(response, mode) {
    "use strict";

    var $result = $('#journal_import_result');
    var labels = journal_import_labels;
    var html = '';

    $result.empty();

    if (response.message && response.total_entries == 0) {
      alert_float('warning', response.message);
    }

    html += '<h4>' + labels.result + '</h4>';

    html += '<ul class="list-unstyled">';
    html += journal_import_summary_item(labels.total_entries, response.total_entries);
    html += journal_import_summary_item(labels.total_lines, response.total_lines);
    html += journal_import_summary_item(labels.total_debit, journal_import_money(response.total_debit));
    html += journal_import_summary_item(labels.total_credit, journal_import_money(response.total_credit));
    html += journal_import_summary_item(labels.count_ok, response.count_ok);
    html += journal_import_summary_item(labels.count_error, response.count_error);
    html += journal_import_summary_item(labels.count_duplicate, response.count_duplicate);
    if (mode === 'import') {
      html += journal_import_summary_item(labels.count_posted, response.count_posted);
      html += journal_import_summary_item(labels.count_failed, response.count_failed);
    }
    html += '</ul>';

    if (mode !== 'import' && response.new_classes && response.new_classes.length > 0) {
      html += '<p class="text-info">' + journal_import_escape(labels.new_classes) + ' ' + journal_import_escape(response.new_classes.join('، ')) + '</p>';
    }

    if (response.filename && /\.xlsx$/i.test(response.filename)) {
      html += '<p><a class="btn btn-warning" href="' + journal_import_escape(site_url + response.filename) + '">' + labels.download_error + '</a></p>';
    }

    if (mode === 'import') {
      html += '<p><a class="btn btn-default" href="' + journal_import_escape(admin_url + 'accounting/journal_entry') + '">' + labels.go_to_list + '</a></p>';
    }

    if (response.entries && response.entries.length > 0) {
      html += '<div class="table-responsive"><table class="table table-bordered">';
      html += '<thead><tr>';
      html += '<th>' + journal_import_escape(labels.number) + '</th>';
      html += '<th>' + journal_import_escape(labels.date) + '</th>';
      html += '<th>' + journal_import_escape(labels.reference) + '</th>';
      html += '<th>' + journal_import_escape(labels.acc_class) + '</th>';
      html += '<th>' + journal_import_escape(labels.debit) + '</th>';
      html += '<th>' + journal_import_escape(labels.credit) + '</th>';
      html += '<th>' + journal_import_escape(labels.status) + '</th>';
      html += '<th>' + journal_import_escape(labels.problems) + '</th>';
      html += '</tr></thead><tbody>';

      $.each(response.entries, function(index, entry) {
        var errors = entry.errors || [];
        var warnings = entry.warnings || [];
        var rowClass = '';
        var statusText = labels.status_ok;

        if (entry.status === 'error') {
          rowClass = 'danger';
          statusText = labels.status_error;
        } else if (entry.status === 'duplicate') {
          rowClass = 'active';
          statusText = labels.status_duplicate;
        } else if (warnings.length > 0) {
          rowClass = 'warning';
          statusText = labels.status_warning;
        }

        var problems = '<ul class="list-unstyled no-margin">';
        $.each(errors, function(i, text) {
          problems += '<li class="text-danger">' + journal_import_escape(text) + '</li>';
        });
        $.each(warnings, function(i, text) {
          problems += '<li class="text-warning">' + journal_import_escape(text) + '</li>';
        });
        problems += '</ul>';

        html += '<tr' + (rowClass ? ' class="' + rowClass + '"' : '') + '>';
        html += '<td>' + journal_import_escape(entry.number) + '</td>';
        html += '<td>' + journal_import_escape(entry.date) + '</td>';
        html += '<td>' + journal_import_escape(entry.reference) + '</td>';
        html += '<td>' + journal_import_escape(entry.class) + '</td>';
        html += '<td>' + journal_import_money(entry.debit) + '</td>';
        html += '<td>' + journal_import_money(entry.credit) + '</td>';
        html += '<td>' + journal_import_escape(statusText) + '</td>';
        html += '<td>' + problems + '</td>';
        html += '</tr>';
      });

      html += '</tbody></table></div>';
    }

    $result.html(html);
  }

  function journal_import_send(mode) {
    "use strict";

    var fileInput = $('#file_csv')[0];

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
      alert_float('warning', journal_import_labels.please_select_file);
      return;
    }

    if (fileInput.files[0].name.split('.').pop().toLowerCase() !== 'xlsx') {
      alert_float('warning', journal_import_labels.xlsx_required);
      return;
    }

    var formData = new FormData();
    formData.append('file_csv', fileInput.files[0]);
    formData.append('mode', mode);
    if (<?php echo acc_check_csrf_protection(); ?>) {
      formData.append(csrfData.token_name, csrfData.hash);
    }

    $('#checkfile, #importfile').prop('disabled', true);

    //show box loading
    var html = '';
    html += '<div class="Box">';
    html += '<span>';
    html += '<span></span>';
    html += '</span>';
    html += '</div>';
    $('#box-loading').html(html);

    $.ajax({
      url: admin_url + 'accounting/import_file_xlsx_journal_entry',
      method: 'post',
      data: formData,
      contentType: false,
      processData: false
    }).done(function(res) {
      $('#box-loading').html('');

      var response = null;
      try {
        response = typeof res === 'string' ? JSON.parse(res) : res;
      } catch (e) {
        response = null;
      }

      if (!response) {
        alert_float('danger', journal_import_labels.fail);
        journal_import_restore_buttons();
        return;
      }

      journal_import_count_ok = (mode === 'import') ? 0 : (parseInt(response.count_ok, 10) || 0);
      journal_import_render(response, mode);
      journal_import_restore_buttons();

    }).fail(function() {
      $('#box-loading').html('');
      alert_float('danger', journal_import_labels.fail);
      journal_import_restore_buttons();
    });
  }

  (function($) {

    "use strict";

    $(document).ready(function() {

      $('#dowload_file_sample').append('<a href="' + site_url + 'modules/accounting/uploads/file_sample/Sample_import_journal_entry_file_ar.xlsx" class="btn btn-primary">' + journal_import_labels.download_sample + '</a><hr>');

      $('#checkfile').on('click', function() {
        journal_import_send('preview');
      });

      $('#importfile').on('click', function() {
        var message = journal_import_labels.confirm.replace('%s', journal_import_count_ok);
        if (confirm(message)) {
          journal_import_send('import');
        }
      });

      $('#file_csv').on('change', function() {
        journal_import_count_ok = 0;
        $('#importfile').prop('disabled', true);
        $('#journal_import_result').empty();
      });
    });

  })(jQuery);

</script>
