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

hooks()->add_action('staff_render_permissions', 'mcs_render_access_box');

function mcs_render_access_box()
{
    if (!mcs_can_manage()) {
        return;
    }

    $CI     = &get_instance();
    $member = $CI->load->get_var('member');

    $CI->load->view(MCS_MODULE_NAME . '/access_box', [
        'mcs_names'   => mcs_sibling_names(),
        'mcs_current' => mcs_current_slug(),
        'mcs_ticked'  => $member ? mcs_access_slugs($member->email) : [],
    ]);
}

// Later tasks add more hooks below, each one next to the function it calls.
