# HR follow-up 2 notes

- OT eligibility: option hrp_ot_eligibility_enabled (default 0 = everyone eligible) and table hrp_ot_eligibility (id, rel_type, rel_id). Enabled: eligible if staff listed directly or any of their departments listed. Migration 121 / module Version 1.2.1. UI in views/includes/overtime_settings.php (two multi-selects, one row per selection, duplicates skipped, delete link).
- OT cap form: both forms are multi-select (rel_ids[]), one hrp_ot_cap_limits row inserted/updated per selection. Employee cap still overrides department; multi-department rule (highest cap) unchanged, undecided by owner.
- Delay rule: hrp_ot_apply_rules now deducts excess late hours from OT first (eligible only), remainder goes to pay_hours_deducted 1:1. Non-eligible: all to pay hours. Test added (late 5, OT 3 -> 3 / 2); ot_result_test.php passes.
- Not runtime-verified (php -l and the pure-function test only). Check that the payslip/grid consumers of pay_hours_deducted deduct at the normal hourly rate.
- is_ot_eligible no longer looks at OT records; the date args are kept but unused.
