<?php
/**
 * Pure-function checks for hrp_ot_apply_rules().
 * Run: php modules/hr_payroll/tests/ot_result_test.php
 */
if (PHP_SAPI !== 'cli') {
	exit('CLI only');
}

define('BASEPATH', __DIR__);
if (!function_exists('hooks')) {
	// the helper file registers hooks at load time; stub them for CLI use
	function hooks()
	{
		return new class {
			public function add_action()
			{
			}
		};
	}
}
define('HR_PAYROLL_MODULE_NAME', 'hr_payroll');

require __DIR__ . '/../helpers/hr_payroll_helper.php';

$failed = 0;
function check($name, $actual, $expected)
{
	global $failed;
	$ok = true;
	foreach ($expected as $key => $value) {
		if (!isset($actual[$key]) || abs($actual[$key] - $value) > 0.001) {
			$ok = false;
		}
	}
	if (!$ok) {
		$failed++;
	}
	echo ($ok ? 'PASS' : 'FAIL') . ' - ' . $name . ($ok ? '' : ' got ' . json_encode($actual)) . PHP_EOL;
}

// US1: 10 morning, 10 night, cap 12 -> night 10, morning 2, discarded 8
check('US1 cap keeps night first', hrp_ot_apply_rules(10, 10, 0, true, 12), [
	'night_paid' => 10, 'morning_paid' => 2, 'discarded' => 8, 'final_total' => 12,
]);

// US2: eligible, 7 late hours, allowance 4 (excess 3), 20 OT hours -> 3 deducted, 17 remain
check('US2 delay deducted, no cap', hrp_ot_apply_rules(10, 10, 3, true, null), [
	'delay_deducted' => 3, 'final_total' => 17, 'morning_paid' => 7, 'night_paid' => 10, 'discarded' => 0,
]);

// US2: the cap is applied to the 17 hours that remain
check('US2 cap applied after delay', hrp_ot_apply_rules(10, 10, 3, true, 12), [
	'delay_deducted' => 3, 'night_paid' => 10, 'morning_paid' => 2, 'discarded' => 5, 'final_total' => 12,
]);

// US2: not OT-eligible, excess comes off working-time pay, OT untouched
check('US2 not eligible deducts pay hours', hrp_ot_apply_rules(10, 10, 3, false, null), [
	'delay_deducted' => 0, 'pay_hours_deducted' => 3, 'final_total' => 20,
]);

// Delay larger than the morning hours spills into night hours
check('Delay spills into night', hrp_ot_apply_rules(2, 10, 5, true, null), [
	'morning_paid' => 0, 'night_paid' => 7, 'delay_deducted' => 5,
]);

// Delay: OT cannot cover it, the remainder comes off working-hours pay 1:1
check('Late excess 5, OT 3', hrp_ot_apply_rules(1, 2, 5, true, null), [
	'delay_deducted' => 3, 'pay_hours_deducted' => 2, 'final_total' => 0,
]);

// Under the cap nothing is discarded
check('Under cap', hrp_ot_apply_rules(3, 4, 0, true, 12), [
	'morning_paid' => 3, 'night_paid' => 4, 'discarded' => 0, 'final_total' => 7,
]);

exit($failed > 0 ? 1 : 0);
