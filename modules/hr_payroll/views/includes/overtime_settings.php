<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
	<div class="col-md-12">
		<h4 class="no-margin font-bold h4-color"><i class="fa fa-clock-o" aria-hidden="true"></i> <?php echo _l('hrp_overtime_settings'); ?></h4>
		<hr class="hr-color">
	</div>
</div>

<?php echo form_open(admin_url('hr_payroll/save_overtime_settings'), ['id' => 'overtime_settings_form']); ?>
<div class="row">
	<div class="col-md-3">
		<?php echo render_input('hrp_ot_morning_rate', 'hrp_ot_morning_rate', get_hr_payroll_option('hrp_ot_morning_rate'), 'number', ['step' => '0.01', 'min' => '0']); ?>
	</div>
	<div class="col-md-3">
		<?php echo render_input('hrp_ot_night_rate', 'hrp_ot_night_rate', get_hr_payroll_option('hrp_ot_night_rate'), 'number', ['step' => '0.01', 'min' => '0']); ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<div class="checkbox checkbox-primary">
			<input type="checkbox" id="hrp_ot_cap_enabled" name="hrp_ot_cap_enabled" value="1" <?php if (get_hr_payroll_option('hrp_ot_cap_enabled') == 1) { echo 'checked'; } ?>>
			<label for="hrp_ot_cap_enabled"><?php echo _l('hrp_ot_cap_enabled'); ?></label>
		</div>
		<div class="checkbox checkbox-primary">
			<input type="checkbox" id="hrp_ot_separate_columns" name="hrp_ot_separate_columns" value="1" <?php if (get_hr_payroll_option('hrp_ot_separate_columns') == 1) { echo 'checked'; } ?>>
			<label for="hrp_ot_separate_columns"><?php echo _l('hrp_ot_separate_columns'); ?></label>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<button type="submit" class="btn btn-info pull-right"><?php echo _l('submit'); ?></button>
	</div>
</div>
<?php echo form_close(); ?>

<hr>

<?php
// name lookups for the cap tables
$department_names = [];
foreach ($departments as $department) {
	$department_names[$department['departmentid']] = $department['name'];
}
$staff_names = [];
foreach ($staffs as $staff_row) {
	$staff_names[$staff_row['staffid']] = $staff_row['firstname'] . ' ' . $staff_row['lastname'];
}
?>

<div class="row">
	<div class="col-md-6">
		<h5 class="font-bold"><?php echo _l('hrp_ot_cap_department'); ?></h5>
		<?php echo form_open(admin_url('hr_payroll/save_ot_cap_limit')); ?>
		<input type="hidden" name="rel_type" value="department">
		<div class="row">
			<div class="col-md-6">
				<?php echo render_select('rel_id', $departments, ['departmentid', 'name'], 'department', '', [], [], '', '', false); ?>
			</div>
			<div class="col-md-4">
				<?php echo render_input('max_hours', 'hrp_ot_max_hours', '', 'number', ['step' => '0.25', 'min' => '0', 'required' => true]); ?>
			</div>
			<div class="col-md-2">
				<button type="submit" class="btn btn-info mtop25"><?php echo _l('submit'); ?></button>
			</div>
		</div>
		<?php echo form_close(); ?>
		<table class="table table-striped">
			<thead>
				<tr><th><?php echo _l('department'); ?></th><th><?php echo _l('hrp_ot_max_hours'); ?></th><th></th></tr>
			</thead>
			<tbody>
				<?php foreach ($department_caps as $cap) { ?>
					<tr>
						<td><?php echo html_escape(isset($department_names[$cap['rel_id']]) ? $department_names[$cap['rel_id']] : $cap['rel_id']); ?></td>
						<td><?php echo html_escape($cap['max_hours']); ?></td>
						<td><a href="<?php echo admin_url('hr_payroll/delete_ot_cap_limit/' . $cap['id']); ?>" class="text-danger _delete"><i class="fa fa-remove"></i></a></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

	<div class="col-md-6">
		<h5 class="font-bold"><?php echo _l('hrp_ot_cap_staff'); ?></h5>
		<?php echo form_open(admin_url('hr_payroll/save_ot_cap_limit')); ?>
		<input type="hidden" name="rel_type" value="staff">
		<div class="row">
			<div class="col-md-6">
				<?php
				$staff_options = [];
				foreach ($staff_names as $staff_id => $staff_name) {
					$staff_options[] = ['id' => $staff_id, 'name' => $staff_name];
				}
				echo render_select('rel_id', $staff_options, ['id', 'name'], 'staff', '', [], [], '', '', false);
				?>
			</div>
			<div class="col-md-4">
				<?php echo render_input('max_hours', 'hrp_ot_max_hours', '', 'number', ['step' => '0.25', 'min' => '0', 'required' => true]); ?>
			</div>
			<div class="col-md-2">
				<button type="submit" class="btn btn-info mtop25"><?php echo _l('submit'); ?></button>
			</div>
		</div>
		<?php echo form_close(); ?>
		<table class="table table-striped">
			<thead>
				<tr><th><?php echo _l('staff'); ?></th><th><?php echo _l('hrp_ot_max_hours'); ?></th><th></th></tr>
			</thead>
			<tbody>
				<?php foreach ($staff_caps as $cap) { ?>
					<tr>
						<td><?php echo html_escape(isset($staff_names[$cap['rel_id']]) ? $staff_names[$cap['rel_id']] : $cap['rel_id']); ?></td>
						<td><?php echo html_escape($cap['max_hours']); ?></td>
						<td><a href="<?php echo admin_url('hr_payroll/delete_ot_cap_limit/' . $cap['id']); ?>" class="text-danger _delete"><i class="fa fa-remove"></i></a></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>
