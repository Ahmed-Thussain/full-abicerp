<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_121 extends App_module_migration
{
	public function up()
	{
		$CI = &get_instance();

		if (row_hr_payroll_options_exist('"hrp_ot_eligibility_enabled"') == 0) {
			$CI->db->query('INSERT INTO `' . db_prefix() . 'hr_payroll_option` (`option_name`,`option_val`, `auto`) VALUES ("hrp_ot_eligibility_enabled", "0", "1");');
		}

		if (!$CI->db->table_exists(db_prefix() . 'hrp_ot_eligibility')) {
			$CI->db->query('CREATE TABLE `' . db_prefix() . "hrp_ot_eligibility` (
			`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`rel_type` VARCHAR(20) NOT NULL,
			`rel_id` INT(11) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
		}
	}
}
