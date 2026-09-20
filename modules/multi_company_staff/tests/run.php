<?php

// Run with: php modules/multi_company_staff/tests/run.php
define('BASEPATH', 'cli');
require __DIR__ . '/../helpers/mcs_core_helper.php';

$failures = 0;

function check($name, $condition)
{
    global $failures;
    echo ($condition ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$condition) {
        $failures++;
    }
}

// --- tokens ---
$key   = 'test-key-123';
$now   = 1000000;
$token = mcs_token_make($key, 'Sam@Example.com', 'shrk', $now);

$ok = mcs_token_read($key, $token, 'shrk', $now + 10);
check('token: valid token is accepted', is_array($ok));
check('token: email is lowercased', is_array($ok) && $ok['email'] === 'sam@example.com');
check('token: has a nonce', is_array($ok) && strlen($ok['nonce']) === 16);
check('token: expired after 60s', mcs_token_read($key, $token, 'shrk', $now + 61) === false);
check('token: wrong company is refused', mcs_token_read($key, $token, 'mr_gaa', $now + 10) === false);
check('token: wrong key is refused', mcs_token_read('other-key', $token, 'shrk', $now + 10) === false);

$parts    = explode('.', $token);
$tampered = strrev($parts[0]) . '.' . $parts[1];
check('token: tampered payload is refused', mcs_token_read($key, $tampered, 'shrk', $now + 10) === false);
check('token: garbage is refused', mcs_token_read($key, 'nonsense', 'shrk', $now) === false);

$token2 = mcs_token_make($key, 'sam@example.com', 'shrk', $now);
check('token: each token has a different nonce', $token !== $token2);

// --- role match ---
$roles = [['roleid' => '1', 'name' => 'Employee'], ['roleid' => '2', 'name' => ' Accountant ']];
check('role: exact name', mcs_find_role_id($roles, 'Employee') === 1);
check('role: ignores case and spaces', mcs_find_role_id($roles, 'accountant') === 2);
check('role: missing name gives null', mcs_find_role_id($roles, 'Manager') === null);
check('role: empty list gives null', mcs_find_role_id([], 'Employee') === null);

// --- access diff ---
$plan = mcs_plan_access_changes(['a', 'b', 'c'], ['b', 'd']);
check('plan: add', $plan['add'] === ['a', 'c']);
check('plan: keep', $plan['keep'] === ['b']);
check('plan: remove', $plan['remove'] === ['d']);
$plan = mcs_plan_access_changes(['a', 'a'], []);
check('plan: duplicates ignored', $plan['add'] === ['a'] && $plan['keep'] === [] && $plan['remove'] === []);

echo $failures === 0 ? "ALL PASSED\n" : "$failures FAILED\n";
exit($failures === 0 ? 0 : 1);
