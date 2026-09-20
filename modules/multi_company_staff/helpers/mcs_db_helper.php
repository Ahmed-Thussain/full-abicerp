<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Every query for master tables and for other companies goes through perfex_saas_raw_query() (PDO) with
 * explicit table names. Inside a company, CodeIgniter's $db is bound to that company and prepends that
 * company's table prefix to every table name, so it cannot be used for those.
 */

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
 * perfex_saas_raw_query() binds parameters by array key, and PDO positional (?) placeholders start at 1,
 * so a plain list never binds. Turn "?" into named placeholders (:p0, :p1, ...) first.
 * The SQL passed in must not contain a literal "?" inside a string.
 *
 * @return array [sql, params]
 */
function mcs_named($sql, array $params)
{
    $i   = 0;
    $sql = preg_replace_callback('/\?/', function () use (&$i) {
        return ':p' . $i++;
    }, $sql);

    $named = [];
    foreach (array_values($params) as $index => $value) {
        $named[':p' . $index] = $value;
    }

    return [$sql, $named];
}

/**
 * Query on the main (master) database.
 *
 * @return mixed rows (array of objects/arrays) when $return is true
 */
function mcs_master_query($sql, array $params = [], $return = false)
{
    list($sql, $params) = mcs_named($sql, $params);

    return perfex_saas_raw_query($sql, [], $return, true, null, false, true, $params);
}

/**
 * Query on the database a given company lives in.
 */
function mcs_company_query($slug, $sql, array $params = [], $return = false)
{
    list($sql, $params) = mcs_named($sql, $params);

    return perfex_saas_raw_query($sql, mcs_company_dsn($slug), $return, true, null, false, true, $params);
}

function mcs_company_dsn($slug)
{
    $rows  = mcs_sibling_rows();
    $plain = '';
    if (isset($rows[$slug]) && !empty($rows[$slug]->dsn)) {
        $plain = get_instance()->encryption->decrypt($rows[$slug]->dsn);
    }

    if (empty($plain)) {
        return perfex_saas_master_dsn();
    }

    return perfex_saas_get_company_dsn((object) ['dsn' => $plain, 'slug' => $slug]);
}

/**
 * Active companies owned by the same client as the current company, keyed by slug.
 */
function mcs_sibling_rows()
{
    static $cache = null;

    if (!function_exists('perfex_saas_is_tenant') || !perfex_saas_is_tenant()) {
        return [];
    }
    if ($cache !== null) {
        return $cache;
    }

    $rows = mcs_master_query(
        'SELECT * FROM `' . perfex_saas_table('companies') . '` WHERE `clientid` = ? AND `status` = ?',
        [perfex_saas_tenant()->clientid, 'active'],
        true
    );

    $cache = [];
    foreach ((array) $rows as $row) {
        $row               = (object) $row;
        $cache[$row->slug] = $row;
    }

    return $cache;
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
    $rows = mcs_master_query(
        'SELECT `company_slug` FROM `' . mcs_table('staff_access') . '` WHERE `email` = ?',
        [strtolower($email)],
        true
    );

    return array_map(function ($row) {
        return ((object) $row)->company_slug;
    }, (array) $rows);
}

function mcs_save_access_row($email, $slug, $staff_id, $origin_slug)
{
    $now = date('Y-m-d H:i:s');

    mcs_master_query(
        'INSERT INTO `' . mcs_table('staff_access') . '` (`clientid`, `email`, `company_slug`, `staff_id`, `origin_slug`, `created_at`, `updated_at`)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE `staff_id` = VALUES(`staff_id`), `updated_at` = VALUES(`updated_at`)',
        [(int) perfex_saas_tenant()->clientid, strtolower($email), $slug, (int) $staff_id, $origin_slug, $now, $now]
    );
}

function mcs_delete_access_row($email, $slug)
{
    mcs_master_query(
        'DELETE FROM `' . mcs_table('staff_access') . '` WHERE `email` = ? AND `company_slug` = ?',
        [strtolower($email), $slug]
    );
}

/**
 * Mark a token nonce as used. Returns true only for the request that claimed it first.
 */
function mcs_use_nonce($nonce)
{
    $table = mcs_table('used_nonce');
    $claim = bin2hex(random_bytes(8));

    mcs_master_query("DELETE FROM `$table` WHERE `used_at` < ?", [date('Y-m-d H:i:s', time() - 86400)]);
    mcs_master_query("INSERT IGNORE INTO `$table` (`nonce`, `claim`, `used_at`) VALUES (?, ?, ?)", [$nonce, $claim, date('Y-m-d H:i:s')]);
    $rows = mcs_master_query("SELECT `claim` FROM `$table` WHERE `nonce` = ?", [$nonce], true);

    return !empty($rows) && ((object) $rows[0])->claim === $claim;
}

/**
 * Roles of a company as a list of ['roleid' => .., 'name' => ..].
 */
function mcs_company_roles($slug)
{
    $rows = mcs_company_query($slug, 'SELECT `roleid`, `name` FROM `' . mcs_company_prefix($slug) . 'roles`', [], true);

    return array_map(function ($row) {
        return (array) $row;
    }, (array) $rows);
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
        if (mcs_find_role_id(mcs_company_roles($slug), $role->name) === null) {
            $company = isset($names[$slug]) ? $names[$slug] : $slug;

            $clean = function ($text) {
                return str_replace(['"', '\\', "\r", "\n"], ["'", '/', ' ', ' '], (string) $text);
            };

            return 'The role \'' . $clean($role->name) . '\' does not exist in company \'' . $clean($company) . '\'. Create it there first or pick another role.';
        }
    }

    return null;
}

/**
 * Replace a company staff member's permissions with the ones stored on that company's role.
 */
function mcs_copy_role_permissions($slug, $staff_id, $role_id)
{
    $prefix = mcs_company_prefix($slug);

    mcs_company_query($slug, 'DELETE FROM `' . $prefix . 'staff_permissions` WHERE `staff_id` = ?', [(int) $staff_id]);
    if (!$role_id) {
        return;
    }

    $rows        = mcs_company_query($slug, 'SELECT `permissions` FROM `' . $prefix . 'roles` WHERE `roleid` = ?', [(int) $role_id], true);
    $permissions = !empty($rows) ? @unserialize(((object) $rows[0])->permissions) : [];
    if (!is_array($permissions)) {
        return;
    }

    $values = [];
    $params = [];
    foreach ($permissions as $feature => $capabilities) {
        foreach ((array) $capabilities as $capability) {
            $values[] = '(?, ?, ?)';
            array_push($params, (int) $staff_id, $feature, $capability);
        }
    }
    if (empty($values)) {
        return;
    }

    mcs_company_query(
        $slug,
        'INSERT INTO `' . $prefix . 'staff_permissions` (`staff_id`, `feature`, `capability`) VALUES ' . implode(', ', $values),
        $params
    );
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
    $table = mcs_company_prefix($slug) . 'staff';

    $role_id = null;
    if (empty($src['admin'])) {
        $role_id = mcs_find_role_id(mcs_company_roles($slug), (string) $role_name);
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

    $existing = mcs_company_query($slug, "SELECT `staffid` FROM `$table` WHERE `email` = ?", [$src['email']], true);

    if (!empty($existing)) {
        $staff_id = (int) ((object) $existing[0])->staffid;
        $sets     = implode(', ', array_map(function ($column) {
            return "`$column` = ?";
        }, array_keys($fields)));
        mcs_company_query($slug, "UPDATE `$table` SET $sets WHERE `staffid` = ?", array_merge(array_values($fields), [$staff_id]));
    } else {
        $fields['datecreated'] = date('Y-m-d H:i:s');
        $columns               = implode(', ', array_map(function ($column) {
            return "`$column`";
        }, array_keys($fields)));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        mcs_company_query($slug, "INSERT INTO `$table` ($columns) VALUES ($placeholders)", array_values($fields));

        $rows     = mcs_company_query($slug, "SELECT `staffid` FROM `$table` WHERE `email` = ?", [$src['email']], true);
        $staff_id = (int) ((object) $rows[0])->staffid;
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

    foreach (array_merge($plan['add'], $plan['keep']) as $slug) {
        if ($slug === $current) {
            mcs_save_access_row($email, $slug, $staff_id, $current);
            continue;
        }
        $target_staff_id = mcs_upsert_company_staff($slug, $src, $role_name);
        mcs_save_access_row($email, $slug, $target_staff_id, $current);
    }

    foreach ($plan['remove'] as $slug) {
        mcs_company_query($slug, 'UPDATE `' . mcs_company_prefix($slug) . 'staff` SET `active` = 0 WHERE `email` = ?', [$src['email']]);
        mcs_delete_access_row($email, $slug);
    }
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
        mcs_company_query(
            $slug,
            'UPDATE `' . mcs_company_prefix($slug) . 'staff` SET `password` = ?, `last_password_change` = ? WHERE `email` = ?',
            [$src['password'], date('Y-m-d H:i:s'), $src['email']]
        );
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
        mcs_company_query(
            $slug,
            'UPDATE `' . mcs_company_prefix($slug) . 'staff` SET `active` = ? WHERE `email` = ?',
            [(int) $src['active'], $src['email']]
        );
    }
}

function mcs_forget_deleted_staff($email)
{
    foreach (mcs_other_company_slugs($email) as $slug) {
        mcs_company_query($slug, 'UPDATE `' . mcs_company_prefix($slug) . 'staff` SET `active` = 0 WHERE `email` = ?', [$email]);
    }

    mcs_master_query('DELETE FROM `' . mcs_table('staff_access') . '` WHERE `email` = ?', [strtolower($email)]);
}

/**
 * Make sure this email has access rows. A person who already has an active account with the same
 * email in sibling companies (for example the owner administrator) gets a row for each of them,
 * once. After that the rows are the source of truth.
 */
function mcs_ensure_access($email)
{
    static $done = [];

    $email = strtolower($email);
    if (isset($done[$email])) {
        return;
    }
    $done[$email] = true;

    if (!empty(mcs_access_slugs($email))) {
        return;
    }

    foreach (mcs_sibling_rows() as $slug => $row) {
        $found = mcs_company_query(
            $slug,
            'SELECT `staffid` FROM `' . mcs_company_prefix($slug) . 'staff` WHERE `email` = ? AND `active` = 1',
            [$email],
            true
        );
        if (!empty($found)) {
            mcs_save_access_row($email, $slug, (int) ((object) $found[0])->staffid, mcs_current_slug());
        }
    }
}

/**
 * URL that logs the current staff member into another company as themselves.
 *
 * @return string|null null when the person has no account in that company
 */
function mcs_switch_target_url($slug)
{
    $staff    = get_staff(get_staff_user_id());
    $siblings = mcs_sibling_rows();
    if (!$staff || !isset($siblings[$slug])) {
        return null;
    }

    mcs_ensure_access($staff->email);
    if (!in_array($slug, mcs_access_slugs($staff->email), true)) {
        return null;
    }

    $company                  = $siblings[$slug];
    $company->package_invoice = perfex_saas_tenant()->package_invoice;
    $token                    = mcs_token_make(APP_ENC_KEY, $staff->email, $slug, time());

    return perfex_saas_tenant_base_url($company, 'multi_company_staff/mcs_enter/index?token=' . urlencode($token));
}
