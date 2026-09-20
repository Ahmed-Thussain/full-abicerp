<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_146 extends App_module_migration
{
    public function up()
    {
        add_option('acc_enable_secondary_currency', 0);
        add_option('acc_secondary_currency_id', '');
    }
}
