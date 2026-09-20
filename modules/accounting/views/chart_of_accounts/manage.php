<?php init_head();?>
<style>
  .accounts-tree, .accounts-tree ul.tree-children {
    list-style: none;
    margin: 0;
    padding: 0;
    padding-inline-start: 22px;
  }
  #accounts-tree-root {
    padding-inline-start: 0;
  }
  .accounts-tree .tree-row {
    display: flex;
    align-items: center;
    padding: 6px 4px;
    border-bottom: 1px solid #f1f1f1;
  }
  .accounts-tree .tree-row:hover {
    background: #f9f9f9;
  }
  .accounts-tree .tree-toggle {
    width: 18px;
    flex: 0 0 18px;
    cursor: pointer;
    color: #999;
    text-align: center;
  }
  .accounts-tree .tree-toggle.no-children {
    visibility: hidden;
  }
  .accounts-tree .tree-icon {
    width: 20px;
    flex: 0 0 20px;
    text-align: center;
    color: #8c9bb5;
  }
  .accounts-tree .tree-class > .tree-row .tree-icon {
    color: #4c6ef5;
  }
  .accounts-tree .tree-icon .fa-circle-o,
  .accounts-tree .tree-icon .fa-dot-circle-o {
    color: #9aa5b1;
    font-size: 9px;
  }
  .accounts-tree .tree-name {
    cursor: pointer;
    flex: 0 1 auto;
    color: #333;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .accounts-tree .tree-name:hover {
    color: #2b7dbc;
    text-decoration: underline;
  }
  .accounts-tree .tree-name.inactive-account {
    color: #aaa;
    text-decoration: line-through;
  }
  .accounts-tree .tree-leader {
    flex: 1 1 auto;
    min-width: 12px;
    height: 0;
    margin: 0 8px;
    align-self: center;
    border-bottom: 1px dotted #ccc;
  }
  .accounts-tree .tree-meta {
    display: flex;
    align-items: center;
    flex: 0 0 auto;
  }
  .accounts-tree .tree-meta > span {
    margin-left: 15px;
    white-space: nowrap;
  }
  .accounts-tree .tree-type {
    color: #999;
    font-size: 12px;
  }
  .accounts-tree .tree-node.collapsed > ul.tree-children {
    display: none;
  }
  .accounts-tree .tree-group > .tree-row .tree-name {
    cursor: default;
    font-weight: 600;
    color: #4c6ef5;
  }
  .accounts-tree .tree-group > .tree-row .tree-name:hover {
    color: #4c6ef5;
    text-decoration: none;
  }
  .accounts-tree .tree-group > .tree-row {
    cursor: pointer;
  }
  .accounts-tree .tree-class > .tree-row {
    background: #f7f8fb;
  }
  .accounts-tree .tree-class > .tree-row .tree-name {
    font-size: 14px;
  }
  .accounts-tree .tree-group > .tree-row .tree-balance {
    font-weight: 600;
  }
  .accounts-tree .tree-actions {
    flex: 0 0 auto;
    margin: 0 10px;
    white-space: nowrap;
    opacity: 0;
    transition: opacity .12s ease-in-out;
  }
  .accounts-tree .tree-row:hover .tree-actions,
  .accounts-tree .tree-actions:focus-within {
    opacity: 1;
  }
  .accounts-tree .tree-btn {
    display: inline-block;
    width: 24px;
    height: 24px;
    line-height: 24px;
    margin: 0 1px;
    border-radius: 4px;
    text-align: center;
    font-size: 12px;
    color: #6b7688;
    background: #eef0f4;
  }
  .accounts-tree .tree-btn:hover,
  .accounts-tree .tree-btn:focus {
    color: #fff;
    background: #4c6ef5;
    text-decoration: none;
  }
  .accounts-tree .tree-btn-status.is-active {
    color: #fff;
    background: #37b24d;
  }
  .accounts-tree .tree-btn-status.is-active:hover {
    background: #2f9e44;
  }
  .accounts-tree .tree-btn-delete:hover {
    background: #e03131;
  }
</style>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="panel_s">
        <div class="panel-body">
          <h4 class="no-margin font-bold"><?php echo _l($title); ?></h4>
          <hr />
          <div>
            <a href="#" class="btn btn-info add-new-account mbot15 <?php if(!has_permission('accounting_chart_of_accounts', '', 'create')){echo 'hide';} ?>"><?php echo _l('add'); ?></a>
            <a href="<?php echo admin_url('accounting/accounts_import'); ?>" class="btn btn-success mbot15 <?php if(!has_permission('accounting_chart_of_accounts', '', 'create')){echo 'hide';} ?>"><?php echo _l('import_account'); ?></a>
          </div>
          <div class="row">
            <div class="col-md-3">
              <?php echo render_select('ft_account',$accounts,array('id','name', 'account_type_name'),'acc_account', '', array('multiple' => true, 'data-actions-box' => true), array(), '', '', false); ?>
            </div>
            <div class="col-md-3">
              <?php echo render_select('ft_parent_account',$accounts,array('id','name', 'account_type_name'),'parent_account', '', array('multiple' => true, 'data-actions-box' => true), array(), '', '', false); ?>
            </div>
            <div class="col-md-3">
              <?php echo render_select('ft_type',$account_types,array('id','name'),'type', '', array('multiple' => true, 'data-actions-box' => true), array(), '', '', false); ?>
            </div>
            <div class="col-md-3">
              <?php echo render_select('ft_detail_type',$detail_types,array('id','name'),'detail_type', '', array('multiple' => true, 'data-actions-box' => true), array(), '', '', false); ?>
            </div>
            <div class="col-md-3">
              <?php $active = [ 
                    1 => ['id' => 'all', 'name' => _l('all')],
                    2 => ['id' => 'yes', 'name' => _l('is_active_export')],
                    3 => ['id' => 'no', 'name' => _l('is_not_active_export')],
                  ]; 
                  ?>
                  <?php echo render_select('ft_active',$active,array('id','name'),'staff_dt_active', 'yes', array(), array(), '', '', false); ?>
            </div>
          </div>
          <hr>
          <div class="mbot15">
            <div class="btn-group" role="group" id="accounts-view-switch">
              <button type="button" class="btn btn-default active" data-view="tree"><i class="fa fa-sitemap"></i> <?php echo _l('tree_view'); ?></button>
              <button type="button" class="btn btn-default" data-view="list"><i class="fa fa-list"></i> <?php echo _l('list_view'); ?></button>
            </div>
            <div class="btn-group" id="accounts-tree-actions" role="group">
              <button type="button" class="btn btn-default" id="accounts-tree-expand-all"><i class="fa fa-plus-square-o"></i> <?php echo _l('expand_all'); ?></button>
              <button type="button" class="btn btn-default" id="accounts-tree-collapse-all"><i class="fa fa-minus-square-o"></i> <?php echo _l('collapse_all'); ?></button>
            </div>
          </div>
          <div id="accounts-list-view" style="display:none;">
            <a href="#" data-toggle="modal" data-target="#accounts_bulk_actions" class="hide bulk-actions-btn table-btn" data-table=".table-accounts"><?php echo _l('bulk_actions'); ?></a>
            <table class="table table-accounts">
              <thead>
                <th><span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="accounts"><label></label></div></th>
                <th><?php echo _l('id'); ?></th>
                <th><?php echo _l('name'); ?></th>
                <th><?php echo _l('parent_account'); ?></th>
                <th><?php echo _l('type'); ?></th>
                <th><?php echo _l('detail_type'); ?></th>
                <th><?php echo _l('primary_balance'); ?></th>
                <th><?php echo _l('bank_balance'); ?></th>
                <?php if (get_option('acc_enable_secondary_currency') == 1) { ?>
                <th><?php echo _l('acc_secondary_currency_amount'); ?></th>
                <?php } ?>
                <th><?php echo _l('staff_dt_active'); ?></th>
                <th><?php echo _l('options'); ?></th>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
          <div id="accounts-tree-view">
            <ul class="accounts-tree" id="accounts-tree-root"></ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $arrAtt = array();
      $arrAtt['data-type']='currency';
?>
<div class="modal fade" id="account-modal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php echo _l('acc_account')?></h4>
      </div>
      <?php echo form_open_multipart(admin_url('accounting/account'),array('id'=>'account-form'));?>
      <?php echo form_hidden('id'); ?>
      <?php echo form_hidden('update_balance'); ?>
      <div class="modal-body">
          <?php echo render_select('account_type_id',$account_types,array('id','name'),'account_type','',array(),array(),'','',false); ?>
          <?php echo render_select('account_detail_type_id',$detail_types,array('id','name'),'detail_type','',array(),array(),'','',false); ?>
          <p><i class="detail_type_note"><?php echo new_html_entity_decode($detail_types[0]['note']); ?></i></p>
        <?php echo render_input('name','name'); ?>
        <?php if(get_option('acc_enable_account_numbers') == 1){
           echo render_input('number','number'); 
        } ?>
        <?php echo render_select('parent_account',$accounts,array('id','name'),'parent_account'); ?>
        <div class="row hide" id="div_balance">
          <div class="col-md-6">
          <?php echo render_input('balance','balance','','text', $arrAtt); ?>
          </div>
          <div class="col-md-6">
          <?php echo render_date_input('balance_as_of','as_of'); ?>
          </div>
        </div>
        <div class="hide" id="div_bank_info">
          <?php echo render_input('bank_name','bank_name'); ?>
          <?php echo render_input('bank_account','acc_bank_account'); ?>
          <?php echo render_input('bank_routing','acc_bank_routing'); ?>
          <?php echo render_input('address_line_1','client_address'); ?>
        </div>
        <div class="row">
          <div class="col-md-12">
            <p class="bold"><?php echo _l('dt_expense_description'); ?></p>
            <?php echo render_textarea('description','','',array(),array(),'','tinymce'); ?>
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


<div class="modal fade bulk_actions" id="accounts_bulk_actions" tabindex="-1" role="dialog" data-table=".table-accounts">
   <div class="modal-dialog" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><?php echo _l('bulk_actions'); ?></h4>
         </div>
         <div class="modal-body">
            <?php if(has_permission('accounting_chart_of_accounts','','edit')){ ?>
               <div class="checkbox checkbox-info">
                  <input type="checkbox" name="mass_activate" id="mass_activate">
                  <label for="mass_activate"><?php echo _l('mass_activate'); ?></label>
               </div>
            <?php } ?>
            <?php if(has_permission('accounting_chart_of_accounts','','edit')){ ?>
               <div class="checkbox checkbox-info">
                  <input type="checkbox" name="mass_deactivate" id="mass_deactivate">
                  <label for="mass_deactivate"><?php echo _l('mass_deactivate'); ?></label>
               </div>
            <?php } ?>
            <?php if(has_permission('accounting_chart_of_accounts','','detele')){ ?>
               <div class="checkbox checkbox-danger">
                  <input type="checkbox" name="mass_delete" id="mass_delete">
                  <label for="mass_delete"><?php echo _l('mass_delete'); ?></label>
               </div>
            <?php } ?>
      </div>
      <div class="modal-footer">
         <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
         <a href="#" class="btn btn-info" onclick="bulk_action(this); return false;"><?php echo _l('confirm'); ?></a>
      </div>
   </div>
   <!-- /.modal-content -->
</div>
<!-- /.modal-dialog -->
</div>
<!-- /.modal -->
<?php init_tail(); ?>
</body>
</html>
<?php require 'modules/accounting/assets/js/chart_of_accounts/manage_js.php'; ?>
