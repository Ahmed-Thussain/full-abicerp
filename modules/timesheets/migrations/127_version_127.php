<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_127 extends App_module_migration
{
	public function up()
	{
		$CI = &get_instance();
		$table = db_prefix() . 'shift_type';

		if (!$CI->db->field_exists('is_day_off', $table)) {
			$CI->db->query('ALTER TABLE `' . $table . '` ADD `is_day_off` TINYINT(1) NOT NULL DEFAULT 0');
		}
		if (!$CI->db->field_exists('day_off_symbol', $table)) {
			$CI->db->query('ALTER TABLE `' . $table . '` ADD `day_off_symbol` VARCHAR(10) NULL');
		}
	}
}
