<?php

defined('BASEPATH') or exit('No direct script access allowed');

function mcs_b64url_encode($string)
{
    return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
}

function mcs_b64url_decode($string)
{
    return (string) base64_decode(strtr($string, '-_', '+/'));
}

/**
 * Signed, expiring token that says "this email may enter this company".
 */
function mcs_token_make($key, $email, $target_slug, $now, $ttl = 60)
{
    $payload = mcs_b64url_encode(json_encode([
        'e' => strtolower($email),
        's' => $target_slug,
        'x' => $now + $ttl,
        'n' => bin2hex(random_bytes(8)),
    ]));

    return $payload . '.' . mcs_b64url_encode(hash_hmac('sha256', $payload, $key, true));
}

/**
 * @return array|false ['email' => ..., 'nonce' => ...] when valid, otherwise false
 */
function mcs_token_read($key, $token, $expected_slug, $now)
{
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return false;
    }

    $expected_signature = mcs_b64url_encode(hash_hmac('sha256', $parts[0], $key, true));
    if (!hash_equals($expected_signature, $parts[1])) {
        return false;
    }

    $data = json_decode(mcs_b64url_decode($parts[0]), true);
    if (!is_array($data) || !isset($data['e'], $data['s'], $data['x'], $data['n'])) {
        return false;
    }

    if ($data['s'] !== $expected_slug || $data['x'] < $now) {
        return false;
    }

    return ['email' => $data['e'], 'nonce' => $data['n']];
}

/**
 * Find a role id by name (case-insensitive, trimmed).
 *
 * @param array  $roles list of ['roleid' => .., 'name' => ..]
 * @return int|null
 */
function mcs_find_role_id(array $roles, $name)
{
    $wanted = mb_strtolower(trim($name));
    foreach ($roles as $role) {
        if (mb_strtolower(trim($role['name'])) === $wanted) {
            return (int) $role['roleid'];
        }
    }

    return null;
}

/**
 * Compare the companies ticked in the form with the ones already saved.
 *
 * @return array ['add' => [...], 'keep' => [...], 'remove' => [...]]
 */
function mcs_plan_access_changes(array $ticked, array $existing)
{
    $ticked   = array_values(array_unique($ticked));
    $existing = array_values(array_unique($existing));

    return [
        'add'    => array_values(array_diff($ticked, $existing)),
        'keep'   => array_values(array_intersect($ticked, $existing)),
        'remove' => array_values(array_diff($existing, $ticked)),
    ];
}


