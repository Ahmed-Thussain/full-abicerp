<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
  @media print {
    .hide-on-print, .btn, #header, #setup-menu-wrapper, .sidebar { display: none !important; }
    .visible-print-block { display: block !important; }
  }
</style>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="panel_s">
        <div class="panel-body">
          <div class="hide-on-print mbot15">
            <a href="#" onclick="window.print(); return false;" class="btn btn-default"><?php echo _l('acc_print'); ?></a>
            <a href="<?php echo admin_url('accounting/journal_entry_pdf/' . $journal_entry->id); ?>" target="_blank" class="btn btn-default"><?php echo _l('acc_export_pdf'); ?></a>
            <a href="<?php echo admin_url('accounting/journal_entry_export/' . $journal_entry->id); ?>" class="btn btn-default"><?php echo _l('acc_export_excel'); ?></a>
            <?php if (has_permission('accounting_journal_entry', '', 'edit')) { ?>
              <a href="<?php echo admin_url('accounting/new_journal_entry/' . $journal_entry->id); ?>" class="btn btn-info"><?php echo _l('edit'); ?></a>
            <?php } ?>
            <a href="<?php echo admin_url('accounting/journal_entry'); ?>" class="btn btn-default"><?php echo _l('back'); ?></a>
          </div>
          <?php $this->load->view('journal_entry/view_body', array_merge($this->load->get_vars(), ['for_pdf' => false])); ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
</body>
</html>
