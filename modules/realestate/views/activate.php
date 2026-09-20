<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body">
			   <h4>Module License Activation</h4>
			   <hr class="hr-panel-heading">
            <?php echo rem_decrypt('stx0WawiZ7B3jEZUSOTOC0Ev3dk5rjFLrrlaCkL+xcIZFm5x4Egw5xnZLEFbv0Pm9PgmtAhpOlVBxoE8xrSiLRWzY+kqykKPUiSLVkugx9FjpBNFMSUur6YNnQ8gvKUcIP6ZXHiDX2PHmQqOed28FQvRIzYN96LaZoFtU2dY7qIWD5DKbZ+JjOoJaJk/I4M8Pf4YSBfOU8zBr4YM0upO8fmj7+67Y15i9H2VMhdgGVr0K7qP5UvAWPaIHVW33nIcVHkNg/ZJBoKqwrs+vK4WCx7pKgo56lMd+J+TXKyENbcQC8imTiHIPTqNCGeDJa+emOTDn0IxQXu2kRZvtncmWUxDw6q+O6DFuIDw0lo8OaE=');?>
			   <br><br>
			   <?php echo form_open($submit_url, ['autocomplete'=>'off', 'id'=>'verify-form']); ?>
                        <?php echo form_hidden('original_url', $original_url); ?> 
                  		<?php echo form_hidden('module_name', $module_name); ?> 
								<?php echo render_input('purchase_key', 'purchase_key', '', 'text', ['required'=>true]); ?>
                        <?php echo render_input('username', 'Envato Username', '', 'text', ['required'=>true]); ?>
                  		<button id="submit" type="submit" class="btn btn-info pull-right"><?php echo _l('submit'); ?></button>
                  	<?php echo form_close(); ?>
               </div>
            </div>
         </div>
         <div class="col-md-6">
		 </div>
      </div>
   </div>
</div>
<?php init_tail(); ?>
<script type="text/javascript">
   appValidateForm($('#verify-form'), {
        purchase_key: 'required',
        username: 'required'
    }, manage_verify_form);

   function manage_verify_form(form) {
      var data = $(form).serialize();
      var url = form.action;
      $("#submit").prop('disabled', true).prepend('<i class="fa fa-spinner fa-pulse"></i> ');
      $.post(url, data).done(function(response) {
         var response = $.parseJSON(response);
         if(!response.status){
            alert_float("danger",response.message);
         }
         if(response.status){
            alert_float("success","Activating....");
            window.location.href = response.original_url;
         }
         $("#submit").prop('disabled', false).find('i').remove();
      });
   }
</script>