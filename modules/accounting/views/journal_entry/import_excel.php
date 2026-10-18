<?php defined('BASEPATH') or exit('No direct script access allowed');
?>
<?php
$file_header = array();
$file_header[] = 'مدين جنيه';
$file_header[] = 'دائن جنيه';
$file_header[] = 'من الحساب';
$file_header[] = 'الى الحساب';
$file_header[] = 'البيان';
$file_header[] = 'المستند';
$file_header[] = 'الوثيقة';
$file_header[] = 'التاريخ';

// mirrors the downloadable sample file
$sample_rows = [
  ['1000', '', '130101 الصندوق', '', 'قيمة دفعة', '1', 'س.ق 1', '2026-01-13'],
  ['', '1000', '', '400402 ايردات متنوعة 1085', 'قيمة دفعة', '1', 'س.ق 1', '2026-01-13'],
  ['50', '', '3007 مصروفات بنكية', '', 'مصروفات بنكية', '2', 'ص.ن 1', '2026-01-14'],
  ['', '50', '', '130101 الصندوق', 'مصروفات بنكية', '2', 'ص.ن 1', '2026-01-14'],
];
?>

<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body backdrop">
            <h4 class="no-margin font-bold"><?php echo _l('acc_ji_import_journal_entry'); ?></h4>
            <hr />

            <div id="dowload_file_sample">
            </div>

            <ul>
              <li class="text-danger">1. <?php echo _l('acc_ji_help_1'); ?></li>
              <li class="text-danger">2. <?php echo _l('acc_ji_help_2'); ?></li>
              <li class="text-danger">3. <?php echo _l('acc_ji_help_3'); ?></li>
              <li class="text-danger">4. <?php echo _l('acc_ji_help_4'); ?></li>
              <li class="text-danger">5. <?php echo _l('acc_ji_help_5'); ?></li>
            </ul>
            <div class="table-responsive no-dt">
              <table class="table table-hover table-bordered">
                <thead>
                  <tr>
                    <?php foreach ($file_header as $header) { ?>
                      <th class="bold"><?php echo html_escape($header); ?></th>
                    <?php } ?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  foreach ($sample_rows as $sample_row) {
                    echo '<tr>';
                    foreach ($sample_row as $cell) {
                      echo '<td>' . ($cell === '' ? '-' : html_escape($cell)) . '</td>';
                    }
                    echo '</tr>';
                  }
                  ?>
                </tbody>
              </table>
            </div>
            <hr>

            <div class="row">
              <div class="col-md-4">
                <?php echo form_open_multipart(admin_url('accounting/import_file_xlsx_journal_entry'), array('id' => 'import_form')); ?>
                  <?php echo render_input('file_csv', 'choose_excel_file', '', 'file'); ?>

                  <div class="form-group">
                    <button id="checkfile" type="button" class="btn btn-info"><?php echo _l('acc_ji_check_file'); ?></button>
                    <button id="importfile" type="button" class="btn btn-success" disabled><?php echo _l('acc_ji_import'); ?></button>
                  </div>
                <?php echo form_close(); ?>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div id="journal_import_result"></div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- box loading -->
<div id="box-loading"></div>
<?php init_tail(); ?>

<?php require 'modules/accounting/assets/js/journal_entry/import_excel_journal_entry_js.php';?>
</body>
</html>
