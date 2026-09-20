<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_125 extends App_module_migration
{
	public function up()
	{
		$CI = &get_instance();
		$options = [
			'ts_ot_split_enabled' => '0',
			'ts_ot_morning_start' => '06:00',
			'ts_ot_morning_end' => '22:00',
			'ts_ot_night_start' => '22:00',
			'ts_ot_night_end' => '06:00',
			'ts_delay_allowance_enabled' => '0',
			'ts_delay_allowance_hours' => '4',
		];
		foreach ($options as $name => $val) {
			if (row_timesheets_options_exist('"' . $name . '"') == 0) {
				$CI->db->query('INSERT INTO `' . db_prefix() . 'timesheets_option` (`option_name`, `option_val`, `auto`) VALUES ("' . $name . '", "' . $val . '", "1");');
			}
		}
	}
}
