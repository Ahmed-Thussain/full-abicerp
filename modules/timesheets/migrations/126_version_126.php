<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_126 extends App_module_migration
{
	public function up()
	{
		add_option('ts_leave_carry_enabled', 0);
		add_option('ts_leave_carry_years', 3);
	}
}
