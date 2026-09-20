<?php

defined('BASEPATH') or exit('No direct script access allowed');

function mcs_table($name)
{
    $prefix = function_exists('perfex_saas_master_db_prefix') ? perfex_saas_master_db_prefix() : 'tbl';

    return $prefix . 'mcs_' . $name;
}

function mcs_company_prefix($slug)
{
    return perfex_saas_tenant_db_prefix($slug);
}

function mcs_current_slug()
{
    return defined('PERFEX_SAAS_TENANT_SLUG') ? PERFEX_SAAS_TENANT_SLUG : '';
}

/**
 * Active companies owned by the same client as the current company, keyed by slug.
 */
function mcs_sibling_rows()
{
    if (!function_exists('perfex_saas_is_tenant') || !perfex_saas_is_tenant()) {
        return [];
    }

    $CI   = &get_instance();
    $rows = $CI->db->get_where(perfex_saas_table('companies'), [
        'clientid' => perfex_saas_tenant()->clientid,
        'status'   => 'active',
    ])->result();

    $out = [];
    foreach ($rows as $row) {
        $out[$row->slug] = $row;
    }

    return $out;
}

function mcs_sibling_names()
{
    $names = [];
    foreach (mcs_sibling_rows() as $slug => $row) {
        $names[$slug] = $row->name;
    }

    return $names;
}

function mcs_can_manage()
{
    return function_exists('perfex_saas_is_tenant')
        && perfex_saas_is_tenant()
        && is_admin()
        && count(mcs_sibling_rows()) > 1;
}

function mcs_access_slugs($email)
{
    $CI   = &get_instance();
    $rows = $CI->db->get_where(mcs_table('staff_access'), ['email' => strtolower($email)])->result();

    return array_map(function ($row) {
        return $row->company_slug;
    }, $rows);
}

function mcs_save_access_row($email, $slug, $staff_id, $origin_slug)
{
    $CI       = &get_instance();
    $table    = mcs_table('staff_access');
    $email    = strtolower($email);
    $now      = date('Y-m-d H:i:s');
    $existing = $CI->db->get_where($table, ['email' => $email, 'company_slug' => $slug])->row();

    if ($existing) {
        $CI->db->where('id', $existing->id)->update($table, ['staff_id' => $staff_id, 'updated_at' => $now]);

        return;
    }

    $CI->db->insert($table, [
        'clientid'     => (int) perfex_saas_tenant()->clientid,
        'email'        => $email,
        'company_slug' => $slug,
        'staff_id'     => (int) $staff_id,
        'origin_slug'  => $origin_slug,
        'created_at'   => $now,
        'updated_at'   => $now,
    ]);
}

function mcs_delete_access_row($email, $slug)
{
    $CI = &get_instance();
    $CI->db->delete(mcs_table('staff_access'), ['email' => strtolower($email), 'company_slug' => $slug]);
}

/**
 * Mark a token nonce as used. Returns true only the first time.
 */
function mcs_use_nonce($nonce)
{
    $CI    = &get_instance();
    $table = mcs_table('used_nonce');

    $CI->db->query("DELETE FROM `$table` WHERE `used_at` < ?", [date('Y-m-d H:i:s', time() - 86400)]);
    $CI->db->query("INSERT IGNORE INTO `$table` (`nonce`, `used_at`) VALUES (?, ?)", [$nonce, date('Y-m-d H:i:s')]);

    return $CI->db->affected_rows() === 1;
}

/**
 * Check the ticked companies can receive this person.
 * Administrators need nothing; others need a role that also exists (by name) in every ticked company.
 *
 * @return string|null error message, or null when fine
 */
function mcs_role_error(array $target_slugs, $is_admin, $role_id)
{
    $others = array_values(array_diff($target_slugs, [mcs_current_slug()]));
    if (empty($others) || $is_admin) {
        return null;
    }

    $CI   = &get_instance();
    $role = $role_id ? $CI->db->get_where(db_prefix() . 'roles', ['roleid' => (int) $role_id])->row() : null;
    if (!$role) {
        return 'Pick a role for this staff member (or tick Administrator) before giving access to other companies.';
    }

    $names = mcs_sibling_names();
    foreach ($others as $slug) {
        $roles = $CI->db->get(mcs_company_prefix($slug) . 'roles')->result_array();
        if (mcs_find_role_id($roles, $role->name) === null) {
            $company = isset($names[$slug]) ? $names[$slug] : $slug;

            return 'The role "' . $role->name . '" does not exist in company "' . $company . '". Create it there first or pick another role.';
        }
    }

    return null;
}

/**
 * Replace a company staff member's permissions with the ones stored on that company's role.
 */
function mcs_copy_role_permissions($slug, $staff_id, $role_id)
{
    $CI     = &get_instance();
    $prefix = mcs_company_prefix($slug);

    $CI->db->delete($prefix . 'staff_permissions', ['staff_id' => (int) $staff_id]);
    if (!$role_id) {
        return;
    }

    $role        = $CI->db->get_where($prefix . 'roles', ['roleid' => (int) $role_id])->row();
    $permissions = $role ? @unserialize($role->permissions) : [];
    if (!is_array($permissions)) {
        return;
    }

    foreach ($permissions as $feature => $capabilities) {
        foreach ((array) $capabilities as $capability) {
            $CI->db->insert($prefix . 'staff_permissions', [
                'staff_id'   => (int) $staff_id,
                'feature'    => $feature,
                'capability' => $capability,
            ]);
        }
    }
}

/**
 * Create or update the copy of a staff account inside another company.
 *
 * @param array       $src       the source staff row (array)
 * @param string|null $role_name name of the source role (only used when the account is not an admin)
 * @return int staff id in the target company
 */
function mcs_upsert_company_staff($slug, array $src, $role_name)
{
    $CI    = &get_instance();
    $table = mcs_company_prefix($slug) . 'staff';

    $role_id = null;
    if (empty($src['admin'])) {
        $roles   = $CI->db->get(mcs_company_prefix($slug) . 'roles')->result_array();
        $role_id = mcs_find_role_id($roles, (string) $role_name);
    }

    $fields = [
        'email'            => $src['email'],
        'firstname'        => $src['firstname'],
        'lastname'         => $src['lastname'],
        'phonenumber'      => $src['phonenumber'],
        'password'         => $src['password'],
        'default_language' => $src['default_language'],
        'direction'        => $src['direction'],
        'hourly_rate'      => $src['hourly_rate'],
        'admin'            => empty($src['admin']) ? 0 : 1,
        'role'             => $role_id,
        'active'           => (int) $src['active'],
    ];

    $row = $CI->db->get_where($table, ['email' => $src['email']])->row();
    if ($row) {
        $CI->db->where('staffid', $row->staffid)->update($table, $fields);
        $staff_id = (int) $row->staffid;
    } else {
        $fields['datecreated'] = date('Y-m-d H:i:s');
        $CI->db->insert($table, $fields);
        $staff_id = (int) $CI->db->insert_id();
    }

    mcs_copy_role_permissions($slug, $staff_id, $role_id);

    return $staff_id;
}

/**
 * Make the companies where this person has an account match the ticked list.
 */
function mcs_sync_staff_access($staff_id, array $ticked_slugs)
{
    $CI  = &get_instance();
    $src = $CI->db->get_where(db_prefix() . 'staff', ['staffid' => (int) $staff_id])->row_array();
    if (!$src) {
        return;
    }

    $email   = strtolower($src['email']);
    $current = mcs_current_slug();
    $valid   = array_keys(mcs_sibling_rows());

    $ticked   = array_values(array_intersect(array_unique(array_merge([$current], $ticked_slugs)), $valid));
    $existing = mcs_access_slugs($email);
    $plan     = mcs_plan_access_changes($ticked, $existing);

    $role_name = null;
    if (empty($src['admin']) && !empty($src['role'])) {
        $role      = $CI->db->get_where(db_prefix() . 'roles', ['roleid' => (int) $src['role']])->row();
        $role_name = $role ? $role->name : null;
    }

    $CI->db->trans_start();

    foreach (array_merge($plan['add'], $plan['keep']) as $slug) {
        if ($slug === $current) {
            mcs_save_access_row($email, $slug, $staff_id, $current);
            continue;
        }
        $target_staff_id = mcs_upsert_company_staff($slug, $src, $role_name);
        mcs_save_access_row($email, $slug, $target_staff_id, $current);
    }

    foreach ($plan['remove'] as $slug) {
        $CI->db->where('email', $src['email'])->update(mcs_company_prefix($slug) . 'staff', ['active' => 0]);
        mcs_delete_access_row($email, $slug);
    }

    $CI->db->trans_complete();
}

/**
 * Other companies (not the current one) this staff member has an account in.
 */
function mcs_other_company_slugs($email)
{
    return array_values(array_diff(mcs_access_slugs($email), [mcs_current_slug()]));
}

function mcs_sync_password($staff_id)
{
    $CI  = &get_instance();
    $src = $CI->db->get_where(db_prefix() . 'staff', ['staffid' => (int) $staff_id])->row_array();
    if (!$src) {
        return;
    }

    foreach (mcs_other_company_slugs($src['email']) as $slug) {
        $CI->db->where('email', $src['email'])->update(mcs_company_prefix($slug) . 'staff', [
            'password'             => $src['password'],
            'last_password_change' => date('Y-m-d H:i:s'),
        ]);
    }
}

function mcs_sync_status($staff_id)
{
    $CI  = &get_instance();
    $src = $CI->db->get_where(db_prefix() . 'staff', ['staffid' => (int) $staff_id])->row_array();
    if (!$src) {
        return;
    }

    foreach (mcs_other_company_slugs($src['email']) as $slug) {
        $CI->db->where('email', $src['email'])->update(mcs_company_prefix($slug) . 'staff', ['active' => (int) $src['active']]);
    }
}

function mcs_forget_deleted_staff($email)
{
    $CI = &get_instance();

    foreach (mcs_other_company_slugs($email) as $slug) {
        $CI->db->where('email', $email)->update(mcs_company_prefix($slug) . 'staff', ['active' => 0]);
    }

    $CI->db->delete(mcs_table('staff_access'), ['email' => strtolower($email)]);
}

