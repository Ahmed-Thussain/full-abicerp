<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// Shared layout for the journal entry view (screen/print) and PDF.
// Expects: $journal_entry, $account_name, $attachments, $currency, $company_logo, $for_pdf
$je        = $journal_entry;
$img_ext   = ['jpg', 'jpeg', 'png'];
$logo_file = ($company_logo != '') ? FCPATH . 'uploads/company/' . $company_logo : '';
$att_dir   = ACCOUTING_MODULE_UPLOAD_FOLDER . '/journal_entry/' . $je->id . '/';
$total_debit = 0;
$total_credit = 0;
// dompdf cannot shape Arabic: pre-join the letters for the PDF only
$pdf_text  = function ($text) use ($for_pdf) { return $for_pdf ? acc_pdf_arabic($text) : $text; };
$class_row = $this->db->where('id', (int) ($je->acc_class ?? 0))->get(db_prefix() . 'acc_class')->row_array();
$class_name = $class_row ? acc_class_display_name($class_row) : '';
$to_data_uri = function ($path) {
    return file_exists($path) ? 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path)) : '';
};
?>
<div class="je-print-doc">
<table class="noborder" style="width:100%;border:none;">
  <tr>
    <td style="border:none;">
      <?php if ($logo_file != '' && file_exists($logo_file)) { ?>
        <img src="<?php echo $for_pdf ? $to_data_uri($logo_file) : base_url('uploads/company/' . html_escape($company_logo)); ?>" style="max-height:70px;max-width:200px;">
      <?php } ?>
    </td>
    <td style="border:none;text-align:right;"><h3><?php echo html_escape($pdf_text(get_option('companyname'))); ?></h3></td>
  </tr>
</table>
<h4><?php echo _l('journal_entry'); ?></h4>
<table class="noborder" style="border:none;">
  <tr><td style="border:none;"><b><?php echo _l('number'); ?>:</b> <?php echo html_escape($je->number); ?></td>
      <td style="border:none;"><b><?php echo _l('journal_date'); ?>:</b> <?php echo _d($je->journal_date); ?></td>
      <td style="border:none;"><b><?php echo _l('reference'); ?>:</b> <?php echo html_escape($je->reference); ?></td>
      <td style="border:none;"><?php if ($class_name != '') { ?><b><?php echo _l('acc_class'); ?>:</b> <?php echo html_escape($pdf_text($class_name)); ?><?php } ?></td></tr>
  <?php
    // the exchange rate only means something for a foreign-currency entry
    $je_base_currency = get_base_currency();
    $je_is_foreign = isset($je->doc_currency) && $je->doc_currency != '' && $je_base_currency && strtoupper($je->doc_currency) != strtoupper($je_base_currency->name);
  ?>
  <?php if ($je_is_foreign) { ?>
  <tr><td style="border:none;"><b><?php echo _l('currency'); ?>:</b> <?php echo html_escape($je->doc_currency); ?></td>
      <td style="border:none;" colspan="2"><?php if (isset($je->doc_rate)) { ?><b><?php echo _l('acc_rate'); ?>:</b> <?php echo html_escape($je->doc_rate); ?><?php } ?></td></tr>
  <?php } ?>
</table>
<p><b><?php echo _l('description'); ?>:</b><br><?php echo nl2br(html_escape($pdf_text(strip_tags(new_html_entity_decode($je->description ?? ''))))); ?></p>

<table class="table table-bordered je-lines" style="width:100%;border-collapse:collapse;">
  <thead>
    <tr>
      <th><?php echo _l('acc_account'); ?></th>
      <th><?php echo _l('description'); ?></th>
      <th class="num"><?php echo _l('debit'); ?></th>
      <th class="num"><?php echo _l('credit'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($je->details as $detail) {
        $total_debit += $detail['debit'];
        $total_credit += $detail['credit']; ?>
    <tr>
      <td><?php echo html_escape($pdf_text(isset($account_name[$detail['account']]) ? $account_name[$detail['account']] : $detail['account'])); ?></td>
      <td><?php echo html_escape($pdf_text($detail['description'])); ?></td>
      <td class="num"><?php echo $detail['debit'] > 0 ? app_format_money($detail['debit'], $currency->name) : ''; ?></td>
      <td class="num"><?php echo $detail['credit'] > 0 ? app_format_money($detail['credit'], $currency->name) : ''; ?></td>
    </tr>
    <?php } ?>
    <tr>
      <td colspan="2"><b><?php echo _l('total'); ?></b></td>
      <td class="num"><b><?php echo app_format_money($total_debit, $currency->name); ?></b></td>
      <td class="num"><b><?php echo app_format_money($total_credit, $currency->name); ?></b></td>
    </tr>
  </tbody>
</table>

<div class="je-attachments">
<h5><?php echo _l('acc_attachments'); ?></h5>
<?php if (count($attachments) == 0) { ?>
  <p>-</p>
<?php } else { ?>
  <ul style="list-style:none;padding:0;">
    <?php foreach ($attachments as $file) {
        $ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
        $is_img = in_array($ext, $img_ext); ?>
      <li class="je-att-item" style="margin-bottom:8px;">
        <?php if ($for_pdf) { ?>
          <?php if ($is_img && file_exists($att_dir . $file['file_name'])) { ?>
            <div><?php echo html_escape($file['file_name']); ?></div>
            <img src="<?php echo $to_data_uri($att_dir . $file['file_name']); ?>" style="max-width:100%;">
          <?php } else { ?>
            <?php echo html_escape($file['file_name']); ?>
          <?php } ?>
        <?php } else { ?>
          <?php echo html_escape($file['file_name']); ?>
          <span class="hide-on-print">
            <a href="<?php echo admin_url('accounting/download_journal_entry_file/' . $file['id'] . '/1'); ?>" target="_blank"><?php echo _l('acc_view'); ?></a> |
            <a href="<?php echo admin_url('accounting/download_journal_entry_file/' . $file['id']); ?>"><?php echo _l('acc_download'); ?></a>
          </span>
          <?php if ($is_img) { ?>
            <div class="visible-print-block" style="display:none;"><img src="<?php echo admin_url('accounting/download_journal_entry_file/' . $file['id'] . '/1'); ?>" style="max-width:100%;"></div>
          <?php } ?>
        <?php } ?>
      </li>
    <?php } ?>
  </ul>
<?php } ?>
</div>
</div>
