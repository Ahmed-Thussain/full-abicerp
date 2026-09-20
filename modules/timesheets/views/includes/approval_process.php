
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>


<div class="row">
 <div class="col-md-12" >

  <div class="_buttons">
      <a href="<?php echo admin_url('timesheets/new_approval_setting'); ?>" class="btn btn-info pull-left" ><?php echo _l('new'); ?></a>
  </div>
  <div class="clearfix"></div>
  <hr class="hr-panel-heading" />
  <div class="clearfix"></div>
  <table class="table dt-table">
    <thead>
      <th><?php echo _l('name'); ?></th>
      <th><?php echo _l('related'); ?></th>
      <th><?php echo _l('options'); ?></th>
    </thead>
    <tbody>
      <?php foreach($approval_setting as $value){ ?>
        <tr>
         <td><?php echo html_entity_decode($value['name']); ?></td>
         <td><?php
         $related = '';
         switch ($value['related']) {
           case 'additional_timesheets':
           $related = _l('additional_timesheets');
           break;
           case 'Leave':
           $related = _l('annual_leave');
           break;
           case 'maternity_leave':
           $related = _l('maternity_leave');
           break;
           case 'private_work_without_pay':
           $related = _l('private_work_without_pay');
           break;
           case 'sick_leave':
           $related = _l('sick_leave');
           break;
           case 'late':
           $related = _l('late');
           break;
           case 'early':
           $related = _l('early');
           break;
           case 'Go_out':
           $related = _l('Go_out');
           break;
           case 'Go_on_bussiness':
           $related = _l('Go_on_bussiness');
           break;
         }

         if($related == ''){
          $related = $this->timesheets_model->get_custom_leave_name_by_slug($value['related']);
        }

        echo html_entity_decode($related); ?>

      </td>
      <td>
         <a href="<?php echo admin_url('timesheets/edit_approval_setting/'.$value['id']); ?>" data-name="<?php echo html_entity_decode($value['name']); ?>" data-related="<?php echo html_entity_decode($value['related']); ?>" data-setting='<?php echo html_entity_decode($value['setting']); ?>' class="btn btn-default btn-icon"><i class="fa fa-pen-to-square"></i></a>

        <a href="<?php echo admin_url('timesheets/delete_approval_setting/'.$value['id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
    </td>
  </tr>
<?php } ?>
</tbody>
</table>
</div>
</div>
