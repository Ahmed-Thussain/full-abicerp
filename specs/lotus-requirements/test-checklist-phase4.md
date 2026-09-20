# Test checklist (Phase 4: US1, US2)

Not run: the app could not be started in this environment.

## US1
- [x] `php modules/hr_payroll/tests/ot_result_test.php`: 6 PASS (10 morning + 10 night, cap 12 gives night 10, morning 2, discarded 8).
- [ ] T053 (needs the app): one employee with 10 morning and 10 night OT hours and cap 12. Turn on split, cap and separate columns, calculate a payslip, and confirm night 10, morning 2, discarded 8.
- [ ] Timesheet settings: save the toggles and times, reload, values persist.
- [ ] Payroll settings > Overtime tab: save rates and toggles; add, update and delete a department cap and an employee cap.
- [ ] Employee cap overrides the department cap.
- [ ] With all three features off, the payslip is identical to before (overtime_hours unchanged).
- [ ] Run migrations 125 (timesheets) and 119 (hr_payroll) twice; no errors.

## US2
- [ ] OT-eligible employee, 7 late hours, allowance 4, 20 OT hours: 3 deducted, 17 remain, cap then applies.
- [ ] Not OT-eligible: excess late hours reduce basic_salary at the hourly rate.
