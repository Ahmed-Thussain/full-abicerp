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

hooks()->add_filter('before_create_staff_member', 'mcs_before_save_staff', 10, 1);
hooks()->add_filter('before_update_staff_member', 'mcs_before_save_staff', 10, 2);
hooks()->add_action('staff_member_created', 'mcs_after_staff_saved');
hooks()->add_action('staff_member_updated', 'mcs_after_staff_saved');
hooks()->add_action('staff_member_profile_updated', 'mcs_after_password_change');
hooks()->add_filter('before_staff_change_password', 'mcs_defer_password_sync', 10, 2);
hooks()->add_action('after_staff_status_change', 'mcs_after_status_change');
hooks()->add_action('before_delete_staff_member', 'mcs_before_staff_delete');
hooks()->add_action('staff_member_deleted', 'mcs_after_staff_deleted');

/**
 * Take our form fields out of the data, and stop the save when a ticked company has no matching role.
 */
function mcs_before_save_staff($data, $id = null)
{
    $GLOBALS['mcs_pending'] = null;

    if (!isset($data['mcs_present'])) {
        return $data;
    }

    $ticked = isset($data['mcs_companies']) ? (array) $data['mcs_companies'] : [];
    unset($data['mcs_present'], $data['mcs_companies']);

    if (!mcs_can_manage()) {
        return $data;
    }

    $error = mcs_role_error($ticked, !empty($data['administrator']), isset($data['role']) ? (int) $data['role'] : 0);
    if ($error) {
        set_alert('danger', $error);
        redirect(admin_url('staff/member' . ($id ? '/' . $id : '')));
    }

    $GLOBALS['mcs_pending'] = $ticked;

    return $data;
}

function mcs_after_staff_saved($staff_id)
{
    if (isset($GLOBALS['mcs_pending']) && is_array($GLOBALS['mcs_pending'])) {
        mcs_sync_staff_access($staff_id, $GLOBALS['mcs_pending']);
    }
    $GLOBALS['mcs_pending'] = null;
}

function mcs_after_password_change($staff_id)
{
    mcs_sync_password($staff_id);
}

function mcs_defer_password_sync($data, $userid)
{
    register_shutdown_function(function () use ($userid) {
        mcs_sync_password($userid);
    });

    return $data;
}

function mcs_after_status_change($staff_id)
{
    mcs_sync_status($staff_id);
}

function mcs_before_staff_delete($data)
{
    $CI  = &get_instance();
    $row = $CI->db->get_where(db_prefix() . 'staff', ['staffid' => (int) $data['id']])->row();

    $GLOBALS['mcs_deleting_email'] = $row ? $row->email : null;
}

function mcs_after_staff_deleted($data)
{
    if (!empty($GLOBALS['mcs_deleting_email'])) {
        mcs_forget_deleted_staff($GLOBALS['mcs_deleting_email']);
    }
    $GLOBALS['mcs_deleting_email'] = null;
}

hooks()->add_action('admin_navbar_start', 'mcs_render_switcher');

/**
 * Company dropdown for non-administrator staff. Same markup as the perfex_saas one,
 * which only shows for administrators.
 */
function mcs_render_switcher()
{
    if (!function_exists('perfex_saas_is_tenant') || !perfex_saas_is_tenant() || is_admin()) {
        return;
    }

    $CI    = &get_instance();
    $staff = get_staff(get_staff_user_id());
    if (!$staff) {
        return;
    }

    mcs_ensure_access($staff->email);
    $slugs    = mcs_access_slugs($staff->email);
    $siblings = mcs_sibling_rows();
    $current  = mcs_current_slug();

    $allowed = array_values(array_filter($slugs, function ($slug) use ($siblings) {
        return isset($siblings[$slug]);
    }));
    if (count($allowed) < 2) {
        return;
    }

    echo '<li class="icon header-tenant-switch tw-h-full tw-relative ltr:tw-mr-1.5 rtl:tw-ml-1.5" data-toggle="tooltip" data-title="' . _l('perfex_saas_switch_app') . '">
            <a href="#" id="tenant-switch" class="!tw-px-0 tw-group" data-toggle="dropdown" aria-expanded="true">
                <span class="tw-ring-1 tw-ring-offset-1 tw-ring-primary-500 tw-rounded-lg tw-px-3 tw-py-2 tw-inline-block"> <i class="fa fa-random tw-mr-1"></i>' . html_escape(perfex_saas_tenant()->name) . '</span>
            </a>
            <ul class="dropdown-menu animated fadeIn" id="tenant-switch-list">';

    foreach ($allowed as $slug) {
        $url = $slug === $current ? '#' : admin_url('multi_company_staff/mcs_switch/go/' . $slug);
        echo '<li><a href="' . html_escape($url) . '">' . html_escape($siblings[$slug]->name) . '</a></li>';
    }

    echo '</ul></li>';
}

hooks()->add_action('admin_init', 'mcs_intercept_company_switch');
hooks()->add_action('app_admin_footer', 'mcs_filter_switch_list');

/**
 * The SaaS dropdown (shown to administrators) links to billing/my_account?redirect=<company url>&target=tenant,
 * which would log the person in as the target company's first admin. Take that request over and log the
 * same person in through our one-time token instead.
 */
function mcs_intercept_company_switch()
{
    if (!function_exists('perfex_saas_is_tenant') || !perfex_saas_is_tenant()) {
        return;
    }

    $CI = &get_instance();
    if (strpos($CI->uri->uri_string(), 'billing/my_account') === false) {
        return;
    }
    if ($CI->input->get('target') !== 'tenant') {
        return;
    }

    $redirect = rtrim((string) $CI->input->get('redirect'), '/');
    if ($redirect === '') {
        return;
    }

    foreach (mcs_sibling_rows() as $slug => $row) {
        $row->package_invoice = perfex_saas_tenant()->package_invoice;
        if (rtrim(perfex_saas_tenant_base_url($row), '/') !== $redirect) {
            continue;
        }

        $url = mcs_switch_target_url($slug);
        if ($url === null) {
            set_alert('danger', 'You do not have an account in that company, so you cannot switch to it.');
            redirect(admin_url());
        }
        redirect($url);
    }
}

/**
 * Remove the companies this person has no account in from the SaaS dropdown.
 */
function mcs_filter_switch_list()
{
    if (!function_exists('perfex_saas_is_tenant') || !perfex_saas_is_tenant() || !is_admin()) {
        return;
    }

    $staff = get_staff(get_staff_user_id());
    if (!$staff) {
        return;
    }

    mcs_ensure_access($staff->email);
    $allowed_slugs = mcs_access_slugs($staff->email);

    $allowed = [];
    $all     = [];
    foreach (mcs_sibling_rows() as $slug => $row) {
        $row->package_invoice = perfex_saas_tenant()->package_invoice;
        $url                  = rtrim(perfex_saas_tenant_base_url($row), '/');
        $all[]                = $url;
        if (in_array($slug, $allowed_slugs, true)) {
            $allowed[] = $url;
        }
    }

    echo '<script>(function () {
        var allowed = ' . json_encode($allowed) . ', companies = ' . json_encode($all) . ';
        var list = document.getElementById("tenant-switch-list");
        if (!list) { return; }
        Array.prototype.slice.call(list.querySelectorAll("li")).forEach(function (li) {
            var a = li.querySelector("a");
            if (!a) { return; }
            var m = (a.getAttribute("href") || "").match(/[?&]redirect=([^&]+)/);
            if (!m) { return; }
            var url = decodeURIComponent(m[1]).replace(/\/+$/, "");
            if (companies.indexOf(url) !== -1 && allowed.indexOf(url) === -1) {
                li.parentNode.removeChild(li);
            }
        });
    })();</script>';
}


