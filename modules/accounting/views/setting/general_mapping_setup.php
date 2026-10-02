<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 

  $acc_invoice_automatic_conversion = get_option('acc_invoice_automatic_conversion');
  $acc_payment_automatic_conversion = get_option('acc_payment_automatic_conversion');
  $acc_expense_automatic_conversion = get_option('acc_expense_automatic_conversion');
  $acc_tax_automatic_conversion = get_option('acc_tax_automatic_conversion');
  $acc_payment_expense_automatic_conversion = get_option('acc_payment_expense_automatic_conversion');
  $acc_payment_sale_automatic_conversion = get_option('acc_payment_sale_automatic_conversion');

  $acc_invoice_payment_account = get_option('acc_invoice_payment_account');
  $acc_invoice_deposit_to = get_option('acc_invoice_deposit_to');
  $acc_invoice_discount_payment_account = get_option('acc_invoice_discount_payment_account');
  $acc_invoice_discount_deposit_to = get_option('acc_invoice_discount_deposit_to');
  $acc_payment_payment_account = get_option('acc_payment_payment_account');
  $acc_payment_deposit_to = get_option('acc_payment_deposit_to');
  $acc_expense_payment_payment_account = get_option('acc_expense_payment_payment_account');
  $acc_expense_payment_deposit_to = get_option('acc_expense_payment_deposit_to');
  
  $acc_expense_payment_account = get_option('acc_expense_payment_account');
  $acc_expense_deposit_to = get_option('acc_expense_deposit_to');
  $acc_tax_payment_account = get_option('acc_tax_payment_account');
  $acc_tax_deposit_to = get_option('acc_tax_deposit_to');
  $acc_expense_tax_payment_account = get_option('acc_expense_tax_payment_account');
  $acc_expense_tax_deposit_to = get_option('acc_expense_tax_deposit_to');

  $acc_active_payment_mode_mapping = get_option('acc_active_payment_mode_mapping');
  $acc_active_expense_category_mapping = get_option('acc_active_expense_category_mapping');

  $acc_credit_note_mapping_mode = get_option('acc_credit_note_mapping_mode');
  $acc_credit_note_automatic_conversion = get_option('acc_credit_note_automatic_conversion');
  $acc_credit_note_payment_account = get_option('acc_credit_note_payment_account');
  $acc_credit_note_deposit_to = get_option('acc_credit_note_deposit_to');

  $acc_credit_note_refund_automatic_conversion = get_option('acc_credit_note_refund_automatic_conversion');
  $acc_credit_note_refund_payment_account = get_option('acc_credit_note_refund_payment_account');
  $acc_credit_note_refund_deposit_to = get_option('acc_credit_note_refund_deposit_to');

  $acc_debit_note_refund_payment_account = get_option('acc_debit_note_refund_payment_account');
  $acc_debit_note_refund_deposit_to = get_option('acc_debit_note_refund_deposit_to');

  $acc_pur_tax_payment_account = get_option('acc_pur_tax_payment_account');
  $acc_pur_tax_deposit_to = get_option('acc_pur_tax_deposit_to');
  
  $acc_check_deposit_to = get_option('acc_check_deposit_to');
?>

<?php if(isset($pending_conversions) && is_array($pending_conversions) && count($pending_conversions) > 0){ ?>
<div class="row">
  <div class="col-md-12">
    <h4><?php echo _l('acc_pending_not_posted'); ?></h4>
    <div class="div_content">
      <table class="table table-striped no-margin">
        <tbody>
          <?php foreach($pending_conversions as $pending){ ?>
          <tr id="pending-type-row-<?php echo htmlspecialchars($pending['type'], ENT_QUOTES, 'UTF-8'); ?>">
            <td><?php echo _l($pending['label']); ?></td>
            <td><span class="badge" id="pending-count-<?php echo htmlspecialchars($pending['type'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo (int)$pending['count']; ?></span></td>
            <td class="text-right">
              <button type="button" class="btn btn-default btn-sm" onclick="open_pending_convert('<?php echo htmlspecialchars($pending['type'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($pending['group'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($pending['tab'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(_l($pending['label']), ENT_QUOTES, 'UTF-8'); ?>')"><?php echo _l('acc_pending_view'); ?></button>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <hr>
  </div>
</div>
<div class="modal fade" id="pending-convert-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="pending-convert-title"></h4>
         </div>
         <div class="modal-body">
            <div class="checkbox">
               <input type="checkbox" id="pending-select-all">
               <label for="pending-select-all"><?php echo _l('acc_pending_select_all'); ?></label>
            </div>
            <table class="table table-striped">
               <thead>
                  <tr>
                     <th></th>
                     <th><?php echo _l('acc_pending_date'); ?></th>
                     <th><?php echo _l('acc_pending_reference'); ?></th>
                     <th><?php echo _l('acc_pending_amount'); ?></th>
                     <th></th>
                     <th></th>
                  </tr>
               </thead>
               <tbody id="pending-convert-rows">
               </tbody>
            </table>
         </div>
         <div class="modal-footer">
            <?php if(has_permission('accounting_setting', '', 'edit') || is_admin()){ ?>
            <button type="button" class="btn btn-default" onclick="convert_pending_selected(false);"><?php echo _l('acc_pending_convert_selected'); ?></button>
            <button type="button" class="btn btn-default" onclick="convert_pending_selected(true);"><?php echo _l('acc_pending_convert_all'); ?></button>
            <?php } ?>
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
         </div>
      </div>
   </div>
</div>
<script>
var pending_convert_type = '';
var pending_convert_group = '';
var pending_convert_tab = '';
var pending_no_mapping = {};
var pending_text_no_mapping = <?php echo json_encode(_l('acc_pending_no_mapping')); ?>;
var pending_text_set_accounts = <?php echo json_encode(_l('acc_pending_set_accounts')); ?>;
var pending_text_no_pending = <?php echo json_encode(_l('acc_pending_no_pending')); ?>;
var pending_text_confirm_all = <?php echo json_encode(_l('acc_pending_convert_all_confirm')); ?>;

function pending_esc(value){
   if(value === null || value === undefined){
      return '';
   }
   return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function open_pending_convert(type, group, tab, label){
   pending_convert_type = type;
   pending_convert_group = group;
   pending_convert_tab = tab;
   pending_no_mapping = {};
   jQuery('#pending-convert-title').text(label);
   jQuery('#pending-select-all').prop('checked', false);
   load_pending_rows();
   jQuery('#pending-convert-modal').modal('show');
}

function load_pending_rows(){
   jQuery.get(admin_url + 'accounting/pending_rows', {type: pending_convert_type, limit: 500}).done(function(response){
      if(typeof response === 'string'){
         response = JSON.parse(response);
      }
      var rows = response.rows || [];
      var html = '';
      if(rows.length === 0){
         html = '<tr><td colspan="6">' + pending_esc(pending_text_no_pending) + '</td></tr>';
      }
      jQuery.each(rows, function(i, row){
         var transaction_url = admin_url + 'accounting/transaction?group=' + encodeURIComponent(pending_convert_group);
         if(pending_convert_tab !== ''){
            transaction_url += '&tab=' + encodeURIComponent(pending_convert_tab);
         }
         html += '<tr>' +
            '<td><div class="checkbox"><input type="checkbox" class="pending-row-check" value="' + parseInt(row.id, 10) + '"><label></label></div></td>' +
            '<td>' + pending_esc(row.date) + '</td>' +
            '<td>' + pending_esc(row.ref) + '</td>' +
            '<td>' + pending_esc(row.amount) + ' ' + pending_esc(row.currency_name) + '</td>' +
            '<td>' + (pending_no_mapping[row.id] ? '<span class="label label-danger">' + pending_esc(pending_text_no_mapping) + '</span>' : '') + '</td>' +
            '<td><a href="' + transaction_url + '" target="_blank">' + pending_esc(pending_text_set_accounts) + '</a></td>' +
         '</tr>';
      });
      jQuery('#pending-convert-rows').html(html);
      jQuery('#pending-select-all').prop('checked', false);
   });
}

jQuery('#pending-select-all').on('change', function(){
   jQuery('.pending-row-check').prop('checked', jQuery(this).prop('checked'));
});

function convert_pending_selected(all){
   var data = {type: pending_convert_type};
   if(all){
      if(!confirm(pending_text_confirm_all)){
         return;
      }
      data.all = 1;
   }else{
      var ids = [];
      jQuery('.pending-row-check:checked').each(function(){
         ids.push(jQuery(this).val());
      });
      if(ids.length === 0){
         return;
      }
      data.ids = ids;
   }
   jQuery.post(admin_url + 'accounting/convert_pending', data).done(function(response){
      if(typeof response === 'string'){
         response = JSON.parse(response);
      }
      if(response.success){
         alert_float('success', response.message);
         pending_no_mapping = {};
         jQuery.each(response.no_mapping || [], function(i, id){
            pending_no_mapping[id] = true;
         });
         var badge = jQuery('#pending-count-' + pending_convert_type);
         if(badge.length){
            var remaining = parseInt(badge.text(), 10) - parseInt(response.posted, 10);
            if(remaining <= 0){
               jQuery('#pending-type-row-' + pending_convert_type).remove();
               jQuery('#pending-convert-modal').modal('hide');
               return;
            }
            badge.text(remaining);
         }
         load_pending_rows();
      }
   });
}
</script>
<?php } ?>

<?php echo form_hidden('acc_debit_note_refund_payment_account', $acc_debit_note_refund_payment_account); ?>
<?php echo form_hidden('acc_debit_note_refund_deposit_to', $acc_debit_note_refund_deposit_to); ?>
 
<?php echo form_open(admin_url('accounting/update_automatic_conversion'),array('id'=>'general-settings-form')); ?>
<div class="row">
  <div class="col-md-12">
    <h4><?php echo _l('automatic_conversion'); ?> <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('automatic_conversion_note'); ?>"></i></h4>
      <div class="div_content">
        <div class="row">
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-6 border-right">
                <h5 class="title mbot5"><?php echo _l('invoice') ?></h5>
              </div>
              <div class="col-md-6 mtop5">
                  <div class="onoffswitch">
                      <input type="checkbox" id="acc_invoice_automatic_conversion" data-perm-id="3" class="onoffswitch-checkbox" <?php if($acc_invoice_automatic_conversion == '1'){echo 'checked';} ?>  value="1" name="acc_invoice_automatic_conversion">
                      <label class="onoffswitch-label" for="acc_invoice_automatic_conversion"></label>
                  </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row <?php if($acc_invoice_automatic_conversion == 0){echo 'hide';} ?>" id="div_invoice_automatic_conversion">
          <div class="col-md-12">
            <h5><?php echo _l('default_for_all_item'); ?></h5>
          </div>
          <div class="col-md-6">
            <?php echo render_select('acc_invoice_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_invoice_payment_account,array(),array(),'','',false); ?>
          </div>
          <div class="col-md-6">
            <?php echo render_select('acc_invoice_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_invoice_deposit_to,array(),array(),'','',false); ?>
          </div>
          <div class="col-md-12">
            <h5><?php echo _l('discount'); ?></h5>
          </div>
          <div class="col-md-6">
            <?php echo render_select('acc_invoice_discount_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_invoice_discount_payment_account,array(),array(),'','',false); ?>
          </div>
          <div class="col-md-6">
            <?php echo render_select('acc_invoice_discount_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_invoice_discount_deposit_to,array(),array(),'','',false); ?>
          </div>
        </div>
      </div>
      <div class="div_content">
        <div class="row">
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-6">
                <h5 class="title mbot5"><?php echo _l('payment') ?></h5>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="row">
            <div class="col-md-12">
              <div class="col-md-6">
                <div class="row">
                  <div class="col-md-6 border-right">
                    <h5><?php echo _l('sales'); ?></h5>
                  </div>
                  <div class="col-md-6 mtop5">
                      <div class="onoffswitch">
                          <input type="checkbox" id="acc_payment_automatic_conversion" data-perm-id="3" class="onoffswitch-checkbox" <?php if($acc_payment_automatic_conversion == '1'){echo 'checked';} ?>  value="1" name="acc_payment_automatic_conversion">
                          <label class="onoffswitch-label" for="acc_payment_automatic_conversion"></label>
                      </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="<?php if($acc_payment_automatic_conversion == 0){echo 'hide';} ?>" id="div_payment_automatic_conversion">
            <div class="col-md-6">
              <?php echo render_select('acc_payment_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_payment_payment_account,array(),array(),'','',false); ?>
            </div>
            <div class="col-md-6">
              <?php echo render_select('acc_payment_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_payment_deposit_to,array(),array(),'','',false); ?>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="col-md-6">
                <div class="row">
                  <div class="col-md-6 border-right">
                    <h5><?php echo _l('expenses'); ?></h5>
                  </div>
                  <div class="col-md-6 mtop5">
                      <div class="onoffswitch">
                          <input type="checkbox" id="acc_payment_expense_automatic_conversion" data-perm-id="3" class="onoffswitch-checkbox" <?php if($acc_payment_expense_automatic_conversion == '1'){echo 'checked';} ?>  value="1" name="acc_payment_expense_automatic_conversion">
                          <label class="onoffswitch-label" for="acc_payment_expense_automatic_conversion"></label>
                      </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="<?php if($acc_payment_expense_automatic_conversion == 0){echo 'hide';} ?>" id="div_payment_expense_automatic_conversion">
            <div class="col-md-6">
              <?php echo render_select('acc_expense_payment_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_expense_payment_payment_account,array(),array(),'','',false); ?>
            </div>
            <div class="col-md-6">
              <?php echo render_select('acc_expense_payment_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_expense_payment_deposit_to,array(),array(),'','',false); ?>
            </div>
          </div>
        </div>
      </div>
      <div class="div_content">
        <div class="row">
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-6">
                <h5 class="title mbot5"><?php echo _l('credit_note') ?></h5>
              </div>
            </div>
          </div>
          <div class="col-md-6">
          <?php
              $credit_note_mapping_mode = [
                          1 => ['id' => 'on_create', 'name' => _l('map_credit_note_when_creating_it')],
                          2 => ['id' => 'on_apply', 'name' => _l('map_credit_note_when_applying_it_to_an_invoice')],
                        ];
               echo render_select('acc_credit_note_mapping_mode', $credit_note_mapping_mode, array('id', 'name'), '', $acc_credit_note_mapping_mode, array(), array(), '', '', false); ?>
          </div>
        </div>
        <div class="row">
          <div class="row">
            <div class="col-md-12">
              <div class="col-md-6">
                <div class="row">
                  <div class="col-md-6 border-right">
                    <h5 class="credit_note_label <?php if($acc_credit_note_mapping_mode == 'on_apply'){echo 'hide';} ?>"><?php echo _l('credit_note'); ?></h5>
                    <h5 class="invoices_credited_label <?php if($acc_credit_note_mapping_mode == 'on_create'){echo 'hide';} ?>"><?php echo _l('invoices_credited'); ?></h5>
                  </div>
                  <div class="col-md-6 mtop5">
                      <div class="onoffswitch">
                          <input type="checkbox" id="acc_credit_note_automatic_conversion" data-perm-id="3" class="onoffswitch-checkbox" <?php if($acc_credit_note_automatic_conversion == '1'){echo 'checked';} ?>  value="1" name="acc_credit_note_automatic_conversion">
                          <label class="onoffswitch-label" for="acc_credit_note_automatic_conversion"></label>
                      </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="<?php if($acc_credit_note_automatic_conversion == 0){echo 'hide';} ?>" id="div_credit_note_automatic_conversion">
            <div class="col-md-6">
              <?php echo render_select('acc_credit_note_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_credit_note_payment_account,array(),array(),'','',false); ?>
            </div>
            <div class="col-md-6">
              <?php echo render_select('acc_credit_note_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_credit_note_deposit_to,array(),array(),'','',false); ?>
            </div>
          </div>
          <div class="row div_credit_note_refund">
            <div class="col-md-12">
              <div class="col-md-6">
                <div class="row">
                  <div class="col-md-6 border-right">
                    <h5><?php echo _l('refund'); ?></h5>
                  </div>
                  <div class="col-md-6 mtop5">
                      <div class="onoffswitch">
                          <input type="checkbox" id="acc_credit_note_refund_automatic_conversion" data-perm-id="3" class="onoffswitch-checkbox" <?php if($acc_credit_note_refund_automatic_conversion == '1'){echo 'checked';} ?>  value="1" name="acc_credit_note_refund_automatic_conversion">
                          <label class="onoffswitch-label" for="acc_credit_note_refund_automatic_conversion"></label>
                      </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="<?php if($acc_credit_note_refund_automatic_conversion == 0){echo 'hide';} ?> div_credit_note_refund" id="div_credit_note_refund_automatic_conversion">
            <div class="col-md-6">
              <?php echo render_select('acc_credit_note_refund_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_credit_note_refund_payment_account,array(),array(),'','',false); ?>
            </div>
            <div class="col-md-6">
              <?php echo render_select('acc_credit_note_refund_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_credit_note_refund_deposit_to,array(),array(),'','',false); ?>
            </div>
          </div>
        </div>
      </div>
      <?php if(isset($expense_default_account_warning) && $expense_default_account_warning){ ?>
      <div class="alert alert-danger"><?php echo _l('acc_expense_default_account_warning'); ?></div>
      <?php } ?>
      <div class="div_content">
        <div class="row">
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-6 border-right">
                <h5 class="title mbot5"><?php echo _l('expense_default') ?></h5>
              </div>
              <div class="col-md-6 mtop5">
                  <div class="onoffswitch">
                      <input type="checkbox" id="acc_expense_automatic_conversion" data-perm-id="3" class="onoffswitch-checkbox" <?php if($acc_expense_automatic_conversion == '1'){echo 'checked';} ?>  value="1" name="acc_expense_automatic_conversion">
                      <label class="onoffswitch-label" for="acc_expense_automatic_conversion"></label>
                  </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row <?php if($acc_expense_automatic_conversion == 0){echo 'hide';} ?>" id="div_expense_automatic_conversion">
          <div class="col-md-6">
            <?php echo render_select('acc_expense_payment_account',$accounts,array('id','name', 'account_type_name'),'acc_expense_paid_from_credit',$acc_expense_payment_account,array(),array(),'','',false); ?>
          </div>
          <div class="col-md-6">
            <?php echo render_select('acc_expense_deposit_to',$accounts,array('id','name', 'account_type_name'),'acc_expense_account_debit',$acc_expense_deposit_to,array(),array(),'','',false); ?>
          </div>
        </div>
      </div>
      <div class="div_content">
        <div class="row">
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-6 border-right">
                <h5 class="title mbot5"><?php echo _l('tax_default') ?></h5>
              </div>
              <div class="col-md-6 mtop5">
                  <div class="onoffswitch">
                      <input type="checkbox" id="acc_tax_automatic_conversion" data-perm-id="3" class="onoffswitch-checkbox" <?php if($acc_tax_automatic_conversion == '1'){echo 'checked';} ?>  value="1" name="acc_tax_automatic_conversion">
                      <label class="onoffswitch-label" for="acc_tax_automatic_conversion"></label>
                  </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row <?php if($acc_tax_automatic_conversion == 0){echo 'hide';} ?>" id="div_tax_automatic_conversion">
          <div class="col-md-12">
            <h5><?php echo _l('sales'); ?></h5>
          </div>
          <div class="col-md-6">
            <?php echo render_select('acc_tax_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_tax_payment_account,array(),array(),'','',false); ?>
          </div>
          <div class="col-md-6">
            <?php echo render_select('acc_tax_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_tax_deposit_to,array(),array(),'','',false); ?>
          </div>
          <div class="col-md-12">
            <h5><?php echo _l('expenses'); ?></h5>
          </div>
          <div class="col-md-6">
            <?php echo render_select('acc_expense_tax_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_expense_tax_payment_account,array(),array(),'','',false); ?>
          </div>
          <div class="col-md-6">
            <?php echo render_select('acc_expense_tax_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_expense_tax_deposit_to,array(),array(),'','',false); ?>
          </div>
        </div>
      </div>
      <div class="div_content">
        <div class="row">
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-6 border-right">
                <h5 class="title mbot5"><?php echo _l('check') ?></h5>
              </div>
              <div class="col-md-6 mtop5">
                  
              </div>
            </div>
          </div>
        </div>
        <div class="row" id="div_expense_automatic_conversion">
          <div class="col-md-6">
            <?php echo render_select('acc_check_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_check_deposit_to,array(),array(),'','',false); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
<hr>
  <div class="col-md-12">
    <button type="submit" class="btn btn-info pull-right"><?php echo _l('submit'); ?></button>
  </div>
<?php echo form_close(); ?>
<div class="row">
  <div class="col-md-12">
    <hr>
  </div>
</div>
  <h4 class="no-margin font-bold"><?php echo _l('item_group_mapping_setup'); ?></h4>
<hr>
<a href="#" onclick="add_item_group_automatic(); return false;" class="btn btn-info mr-4 button-margin-r-b" title="<?php echo _l('add') ?> ">
  <?php echo _l('add'); ?>
</a>
<hr>
<table class="table table-item-group-automatic">
  <thead>
    <th>#</th>
    <th><?php echo _l('item_group_name'); ?></th>
  </thead>
  <tbody>
  </tbody>
</table>
<div class="row">
  <div class="col-md-12">
    <hr>
  </div>
</div>
  <h4 class="no-margin font-bold"><?php echo _l('item_mapping_setup'); ?></h4>
<hr>
<a href="#" onclick="add_item_automatic(); return false;" class="btn btn-info mr-4 button-margin-r-b" title="<?php echo _l('add') ?> ">
  <?php echo _l('add'); ?>
</a>
<hr>
<table class="table table-item-automatic">
  <thead>
    <th><?php echo _l('invoice_items_list_description'); ?></th>
    <th><?php echo _l('invoice_items_list_rate'); ?></th>
    <th><?php echo _l('item_group_name'); ?></th>
  </thead>
  <tbody>
  </tbody>
</table>
<div class="row">
  <div class="col-md-12">
    <hr>
  </div>
  <div class="col-md-6">
    <div class="row">
      <div class="col-md-6 border-right">
        <h4 class="no-margin font-bold"><?php echo _l('payment_mode_mapping'); ?></h4>
      </div>
      <div class="col-md-6">
          <div class="onoffswitch">
              <input type="checkbox" id="acc_active_payment_mode_mapping" data-perm-id="3" class="onoffswitch-checkbox" <?php if($acc_active_payment_mode_mapping == '1'){echo 'checked';} ?>  value="1" name="acc_active_payment_mode_mapping" data-switch-url="<?php echo admin_url('accounting/change_active_payment_mode_mapping') ?>" data-id="0">
              <label class="onoffswitch-label" for="acc_active_payment_mode_mapping"></label>
          </div>
      </div>
    </div>
  </div>
</div>
<hr>
<a href="#" onclick="add_payment_mode_mapping(); return false;" class="btn btn-info mr-4 button-margin-r-b" title="<?php echo _l('add') ?> ">
  <?php echo _l('add'); ?>
</a>
<hr>
<table class="table table-payment-mode-mapping">
  <thead>
    <th><?php echo _l('name'); ?></th>
    <th><?php echo _l('description'); ?></th>
  </thead>
  <tbody>
  </tbody>
</table>
<div class="row">
  <div class="col-md-12">
    <hr>
  </div>
  <div class="col-md-6">
    <div class="row">
      <div class="col-md-6 border-right">
        <h4 class="no-margin font-bold"><?php echo _l('expense_category_mapping'); ?></h4>
      </div>
      <div class="col-md-6">
          <div class="onoffswitch">
              <input type="checkbox" id="acc_active_expense_category_mapping" data-perm-id="3" class="onoffswitch-checkbox" <?php if($acc_active_expense_category_mapping == '1'){echo 'checked';} ?>  value="1" name="acc_active_expense_category_mapping" data-switch-url="<?php echo admin_url('accounting/change_active_expense_category_mapping') ?>" data-id="0">
              <label class="onoffswitch-label" for="acc_active_expense_category_mapping"></label>
          </div>
      </div>
    </div>
  </div>
</div>
<hr>
<a href="#" onclick="add_expense_category_mapping(); return false;" class="btn btn-info mr-4 button-margin-r-b" title="<?php echo _l('add') ?> ">
  <?php echo _l('add'); ?>
</a>
<hr>
<table class="table table-expense-category-mapping">
  <thead>
    <th><?php echo _l('id'); ?></th>
    <th><?php echo _l('name'); ?></th>
    <th><?php echo _l('description'); ?></th>
    <th><?php echo _l('acc_preferred_payment_method'); ?></th>
  </thead>
  <tbody>
  </tbody>
</table>
<div class="row">
  <div class="col-md-12">
    <hr>
  </div>
</div>
  <h4 class="no-margin font-bold"><?php echo _l('tax_mapping_setup'); ?></h4>
<hr>
<a href="#" onclick="add_tax_mapping(); return false;" class="btn btn-info mr-4 button-margin-r-b" title="<?php echo _l('add') ?> ">
  <?php echo _l('add'); ?>
</a>
<hr>
<table class="table table-tax-mapping">
  <thead>
    <th><?php echo _l('id'); ?></th>
    <th><?php echo _l('tax_dt_name'); ?></th>
    <th><?php echo _l('tax_dt_rate'); ?></th>
  </thead>
  <tbody>
  </tbody>
</table>

<?php $arrAtt = array();
      $arrAtt['data-type']='currency';
?>
<div class="modal fade" id="item-automatic-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _l('item_mapping_setup')?></h4>
         </div>
         <?php echo form_open_multipart(admin_url('accounting/item_automatic'),array('id'=>'item-automatic-form'));?>
         <?php echo form_hidden('id'); ?>
         <div class="modal-body">
              <?php echo render_select('item[]',$items,array('id','description', 'sku_code'),'acc_item', '', array('multiple' => true, 'data-actions-box' => true), array(), '', '', false); ?>
              <?php echo render_select('inventory_asset_account',$accounts,array('id','name','account_type_name'),'inventory_asset_account', '37', array(), array(), '', '', false); ?>
              <?php echo render_select('income_account',$accounts,array('id','name','account_type_name'),'income_account', '69', array(), array(), '', '', false); ?>
              <?php echo render_select('expense_account',$accounts,array('id','name','account_type_name'),'expense_account', '16', array(), array(), '', '', false); ?>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info btn-submit"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>  
      </div>
   </div>
</div>

<div class="modal fade" id="edit-item-automatic-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _l('item_mapping_setup')?></h4>
         </div>
         <?php echo form_open_multipart(admin_url('accounting/item_automatic'),array('id'=>'edit-item-automatic-form'));?>
         <?php echo form_hidden('id'); ?>
         
         <div class="modal-body">
              <?php echo render_select('item_id',$_items,array('id','description', 'sku_code'),'acc_item', '',array('disabled' => true), array(), '', '', false); ?>
              <?php echo render_select('inventory_asset_account',$accounts,array('id','name','account_type_name'),'inventory_asset_account', '37', array(), array(), '', '', false); ?>
              <?php echo render_select('income_account',$accounts,array('id','name','account_type_name'),'income_account', '69', array(), array(), '', '', false); ?>
              <?php echo render_select('expense_account',$accounts,array('id','name','account_type_name'),'expense_account', '16', array(), array(), '', '', false); ?>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info btn-submit"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>  
      </div>
   </div>
</div>

<div class="modal fade" id="item-group-automatic-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _l('item_group_mapping_setup')?></h4>
         </div>
         <?php echo form_open_multipart(admin_url('accounting/item_group_automatic'),array('id'=>'item-group-automatic-form'));?>
         <?php echo form_hidden('id'); ?>
         <div class="modal-body">
              <?php echo render_select('item_group[]',$item_groups,array('id','name'),'acc_item_group', '', array('multiple' => true, 'data-actions-box' => true), array(), '', '', false); ?>
              <?php echo render_select('inventory_asset_account',$accounts,array('id','name','account_type_name'),'inventory_asset_account', '37', array(), array(), '', '', false); ?>
              <?php echo render_select('income_account',$accounts,array('id','name','account_type_name'),'income_account', '69', array(), array(), '', '', false); ?>
              <?php echo render_select('expense_account',$accounts,array('id','name','account_type_name'),'expense_account', '16', array(), array(), '', '', false); ?>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info btn-submit"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>  
      </div>
   </div>
</div>

<div class="modal fade" id="edit-item-group-automatic-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _l('item_group_mapping_setup')?></h4>
         </div>
         <?php echo form_open_multipart(admin_url('accounting/item_group_automatic'),array('id'=>'edit-item-group-automatic-form'));?>
         <?php echo form_hidden('id'); ?>
         
         <div class="modal-body">
              <?php echo render_select('item_group_id',$_item_groups,array('id','name'),'acc_item_group', '',array('disabled' => true), array(), '', '', false); ?>
              <?php echo render_select('inventory_asset_account',$accounts,array('id','name','account_type_name'),'inventory_asset_account', '37', array(), array(), '', '', false); ?>
              <?php echo render_select('income_account',$accounts,array('id','name','account_type_name'),'income_account', '69', array(), array(), '', '', false); ?>
              <?php echo render_select('expense_account',$accounts,array('id','name','account_type_name'),'expense_account', '16', array(), array(), '', '', false); ?>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info btn-submit"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>  
      </div>
   </div>
</div>

<div class="modal fade" id="tax-mapping-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _l('tax_mapping_setup')?></h4>
         </div>
         <?php echo form_open_multipart(admin_url('accounting/tax_mapping'),array('id'=>'tax-mapping-form'));?>
         <?php echo form_hidden('id'); ?>
         <div class="modal-body">
              <?php echo render_select('tax[]',$taxes,array('id','name', 'taxrate'),'tax', '', array('multiple' => true, 'data-actions-box' => true), array(), '', '', false); ?>
              <div class="row">
                <div class="col-md-12">
                  <h5><?php echo _l('sales'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_tax_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_tax_deposit_to,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-12">
                  <h5><?php echo _l('expenses'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('expense_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_expense_tax_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('expense_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_expense_tax_deposit_to,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-12">
                  <h5><?php echo _l('purchase'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('purchase_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_pur_tax_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('purchase_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_pur_tax_deposit_to,array(),array(),'','',false); ?>
                </div>
              </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info btn-submit"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>  
      </div>
   </div>
</div>

<div class="modal fade" id="edit-tax-mapping-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _l('tax_mapping_setup')?></h4>
         </div>
         <?php echo form_open_multipart(admin_url('accounting/tax_mapping'),array('id'=>'edit-tax-mapping-form'));?>
         <?php echo form_hidden('id'); ?>
         
         <div class="modal-body">
              <?php echo render_select('tax_id',$_taxes,array('id','name', 'taxrate'),'tax', '',array('disabled' => true), array(), '', '', false); ?>
              <div class="col-md-12">
                  <h5><?php echo _l('sales'); ?></h5>
                </div>
              <div class="row">
                <div class="col-md-6">
                  <?php echo render_select('payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_tax_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_tax_deposit_to,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-12">
                  <h5><?php echo _l('expenses'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('expense_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_expense_tax_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('expense_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_expense_tax_deposit_to,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-12">
                  <h5><?php echo _l('purchase'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('purchase_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_pur_tax_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('purchase_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_pur_tax_deposit_to,array(),array(),'','',false); ?>
                </div>
              </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info btn-submit"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>  
      </div>
   </div>
</div>

<div class="modal fade" id="expense-category-mapping-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _l('expense_category_mapping_setup')?></h4>
         </div>
         <?php echo form_open_multipart(admin_url('accounting/expense_category_mapping'),array('id'=>'expense-category-mapping-form'));?>
         <?php echo form_hidden('id'); ?>
         <div class="modal-body">
              <?php echo render_select('category[]',$categories,array('id','name'),'expense_category', '', array('multiple' => true, 'data-actions-box' => true), array(), '', '', false); ?>
              <div class="row">
                <div class="col-md-6">
                  <?php echo render_select('payment_account',$accounts,array('id','name', 'account_type_name'),'acc_expense_paid_from_credit',$acc_expense_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('deposit_to',$accounts,array('id','name', 'account_type_name'),'acc_expense_account_debit',$acc_expense_deposit_to,array(),array(),'','',false); ?>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="preferred_payment_method" <?php if($acc_expense_deposit_to == '1'){echo 'checked';} ?> id="preferred_payment_method" value="1">
                    <label for="preferred_payment_method"><?php echo _l('acc_preferred_payment_method'); ?></label>
                  </div>
                </div>
              </div>
              <div class="row list-payment-method-mapping">
                
              </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info btn-submit"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>  
      </div>
   </div>
</div>

<div class="modal fade" id="edit-expense-category-mapping-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _l('expense_category_mapping_setup')?></h4>
         </div>
         <?php echo form_open_multipart(admin_url('accounting/expense_category_mapping'),array('id'=>'edit-expense-category-mapping-form'));?>
         <?php echo form_hidden('id'); ?>
         
         <div class="modal-body">
              <?php echo render_select('category_id',$_categories,array('id','name'),'expense_category', '',array('disabled' => true), array(), '', '', false); ?>
              <div class="row">
                <div class="col-md-6">
                  <?php echo render_select('payment_account',$accounts,array('id','name', 'account_type_name'),'acc_expense_paid_from_credit',$acc_expense_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('deposit_to',$accounts,array('id','name', 'account_type_name'),'acc_expense_account_debit',$acc_expense_deposit_to,array(),array(),'','',false); ?>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="preferred_payment_method" id="edit_preferred_payment_method" value="1">
                    <label for="edit_preferred_payment_method"><?php echo _l('acc_preferred_payment_method'); ?></label>
                  </div>
                </div>
              </div>
              <div class="row list-payment-method-mapping">
                
              </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info btn-submit"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>  
      </div>
   </div>
</div>


<div class="modal fade" id="payment-mode-mapping-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _l('payment_mode_mapping')?></h4>
         </div>
         <?php echo form_open_multipart(admin_url('accounting/payment_mode_mapping'),array('id'=>'payment-mode-mapping-form'));?>
         <?php echo form_hidden('id'); ?>
         <div class="modal-body">
              <?php echo render_select('payment_mode[]',$payment_modes,array('id','name'),'payment_mode', '', array('multiple' => true, 'data-actions-box' => true), array(), '', '', false); ?>
              <div class="row">
                <div class="col-md-12">
                  <h5><?php echo _l('sales'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_payment_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_payment_deposit_to,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-12">
                  <h5><?php echo _l('expenses'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('expense_payment_account',$accounts,array('id','name', 'account_type_name'),'acc_expense_paid_from_credit',$acc_expense_payment_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('expense_deposit_to',$accounts,array('id','name', 'account_type_name'),'acc_expense_account_debit',$acc_expense_payment_deposit_to,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-12">
                  <h5><?php echo _l('credit_note_refund'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('credit_note_refund_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_credit_note_refund_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('credit_note_refund_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_credit_note_refund_deposit_to,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-12">
                  <h5><?php echo _l('debit_note_refund'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('debit_note_refund_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_debit_note_refund_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('debit_note_refund_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_debit_note_refund_deposit_to,array(),array(),'','',false); ?>
                </div>
              </div>
         </div>


         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info btn-submit"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>  
      </div>
   </div>
</div>

<div class="modal fade" id="edit-payment-mode-mapping-modal">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo _l('payment_mode_mapping')?></h4>
         </div>
         <?php echo form_open_multipart(admin_url('accounting/payment_mode_mapping'),array('id'=>'edit-payment-mode-mapping-form'));?>
         <?php echo form_hidden('id'); ?>
         
         <div class="modal-body">
              <?php echo render_select('payment_mode_id',$_payment_modes,array('id','name'),'payment_mode', '',array('disabled' => true), array(), '', '', false); ?>
              <div class="row">
                <div class="col-md-6">
                  <?php echo render_select('payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_payment_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_payment_deposit_to,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-12">
                  <h5><?php echo _l('expenses'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('expense_payment_account',$accounts,array('id','name', 'account_type_name'),'acc_expense_paid_from_credit',$acc_expense_payment_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('expense_deposit_to',$accounts,array('id','name', 'account_type_name'),'acc_expense_account_debit',$acc_expense_payment_deposit_to,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-12">
                  <h5><?php echo _l('credit_note_refund'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('credit_note_refund_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_credit_note_refund_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('credit_note_refund_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_credit_note_refund_deposit_to,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-12">
                  <h5><?php echo _l('debit_note_refund'); ?></h5>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('debit_note_refund_payment_account',$accounts,array('id','name', 'account_type_name'),'payment_account',$acc_debit_note_refund_payment_account,array(),array(),'','',false); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_select('debit_note_refund_deposit_to',$accounts,array('id','name', 'account_type_name'),'deposit_to',$acc_debit_note_refund_deposit_to,array(),array(),'','',false); ?>
                </div>
              </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info btn-submit"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>  
      </div>
   </div>
</div>