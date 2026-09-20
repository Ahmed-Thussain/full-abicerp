<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Multi Company Staff
Description: Give one staff member access to several companies of the same owner and switch between them.
Version: 1.0.0
Requires at least: 2.3.*
*/

define('MCS_MODULE_NAME', 'multi_company_staff');

$CI = &get_instance();
$CI->load->helper(MCS_MODULE_NAME . '/mcs_core');
$CI->load->helper(MCS_MODULE_NAME . '/mcs_db');

register_activation_hook(MCS_MODULE_NAME, 'mcs_module_activation_hook');

function mcs_module_activation_hook()
{
    require_once __DIR__ . '/install.php';
}

// Hooks are added by later tasks, each one next to the function it calls.
