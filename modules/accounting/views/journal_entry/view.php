<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link href="<?php echo module_dir_url(ACCOUNTING_MODULE_NAME, 'assets/css/journal_entry_print.css'); ?>?v=1" rel="stylesheet" type="text/css" />
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="panel_s">
        <div class="panel-body">
          <div class="hide-on-print mbot15">
            <div class="btn-group">
              <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-print"></i><?php if (is_mobile()) { echo ' PDF'; } ?></a>
              <ul class="dropdown-menu dropdown-menu-left">
                <li><a href="<?php echo admin_url('accounting/journal_entry_pdf/' . $journal_entry->id); ?>" target="_blank"><?php echo _l('export_to_pdf'); ?></a></li>
                <li><a href="<?php echo admin_url('accounting/journal_entry_export/' . $journal_entry->id); ?>"><?php echo _l('export_to_excel'); ?></a></li>
              </ul>
            </div>
            <?php if (has_permission('accounting_journal_entry', '', 'edit')) { ?>
              <a href="<?php echo admin_url('accounting/new_journal_entry/' . $journal_entry->id); ?>" class="btn btn-info"><?php echo _l('edit'); ?></a>
            <?php } ?>
            <a href="<?php echo admin_url('accounting/journal_entry'); ?>" class="btn btn-default"><?php echo _l('back'); ?></a>
          </div>
          <?php $this->load->view('report/convert_style'); ?>
          <div class="page" id="DivIdToPrint">
            <div style="flex:1;">
          <?php $this->load->view('journal_entry/view_body', array_merge($this->load->get_vars(), ['for_pdf' => false])); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
</body>
</html>
