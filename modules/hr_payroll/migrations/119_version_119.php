<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_119 extends App_module_migration
{
	public function up()
	{
		$CI = &get_instance();
		$options = [
			'hrp_ot_morning_rate' => '1.35',
			'hrp_ot_night_rate' => '1.7',
			'hrp_ot_cap_enabled' => '0',
			'hrp_ot_separate_columns' => '0',
		];
		foreach ($options as $name => $val) {
			if (row_hr_payroll_options_exist('"' . $name . '"') == 0) {
				$CI->db->query('INSERT INTO `' . db_prefix() . 'hr_payroll_option` (`option_name`,`option_val`, `auto`) VALUES ("' . $name . '", "' . $val . '", "1");');
			}
		}

		if (!$CI->db->table_exists(db_prefix() . 'hrp_ot_cap_limits')) {
			$CI->db->query('CREATE TABLE `' . db_prefix() . "hrp_ot_cap_limits` (
			`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`rel_type` VARCHAR(20) NOT NULL,
			`rel_id` INT(11) NOT NULL,
			`max_hours` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
		}

		$columns = [
			['OT Morning Hours', 'ot_morning_hours', '12'],
			['OT Night Hours', 'ot_night_hours', '13'],
			['OT Discarded Hours', 'ot_discarded_hours', '14'],
			['Delay Deducted Hours', 'delay_deducted_hours', '15'],
			['OT Final Hours', 'ot_final_hours', '16'],
		];
		foreach ($columns as $col) {
			if (hr_payroll_payroll_column_exist('"' . $col[1] . '"') == 0) {
				$CI->db->query('INSERT INTO `' . db_prefix() . 'hrp_payroll_columns` (`column_key`, `taking_method`, `function_name`, `value_related_to`, `display_with_staff`, `description`, `date_created`, `staff_id_created`, `order_display`, `is_edit`) VALUES ("' . $col[0] . '", "system", "' . $col[1] . '", "", "true", "' . $col[0] . '", "' . date("Y-m-d H:i:s") . '", "' . get_staff_user_id() . '", "' . $col[2] . '", "no");');
			}
		}
	}
}
