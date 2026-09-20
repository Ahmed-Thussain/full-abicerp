# HR notes (phase 6)

## T064 Leave cycle (timesheets)
- One `timesheets_day_off` row per staff, per year, per leave type (annual leave is type 8). Columns: `total`, `days_off` (used), `remain`.
- Rows are created or updated in `Timesheets_model::set_leave()` (~line 140) from the Manage leave setting screen: `remain = total - days_off`. New row: `remain = total`, `days_off = 0`. The `year` is the first year of the leave cycle (`start_year_for_annual_leave_cycle`).
- Where `remain` is reduced: `Timesheets_model::update_requisition_after_approve()` (~line 8439). On approval it finds the row for `get_first_year_of_period(start_time)`, does `days_off + number_of_leaving_day` and `remain = total - days_off`.
- Where it is given back: the annual-leave (type 8) case at ~1152 (when a request is reverted) and `delete_requisition()` (~2057, only for pending requests, status 1).
- Where it is only read: `add_requisition` (~1996, copies `remain` into the request), `get_requisition_number_of_day_off` (~4032), `get_requisition_number_day_off_manual_leave` (~8899), the controller (~924) and the leave request screen.

## T065-T069 what was built
- Options `ts_leave_carry_enabled` (0) and `ts_leave_carry_years` (3): install.php, migration 126, saved through `set_leave()` from the checkbox and number field added to `manage_leave.php`.
- `get_leave_balances($staffid, $year, $type='8')`: rows for years `year-(n-1)` to `year`, newest first, `remain > 0`. Toggle off: current year only.
- `deduct_leave_days_with_carry()` is called from `update_requisition_after_approve()` only when the toggle is on and the type is 8. Takes days current year first, then older years. Any part no row can cover goes to the current-year row (same over-draw the old code allowed). Toggle off: old code path is untouched.
- Leave request screen (`views/leave/calendar.php`) shows a year / remaining table.
- Expiry needs no job: rows older than the window are simply not returned by `get_leave_balances`.

Known limits:
- Giving days back (revert at ~1152 and `delete_requisition`) still credits the year of the request start, not the year the days were taken from. Not changed (task did not ask).
- The "days allowed" number and the check that blocks a request still read only the current-year `remain`. A staff member with 0 current-year days but a carried balance still sees 0 allowed. The new table shows the carried days.
- `get_leave_balances` returns type 8 only unless another type is passed.

## T071 Where payroll creates journal entries
- Only one function creates them: `Accounting_model::automatic_payslip_conversion($payslips_id)` (~line 15893). It runs from `acc_update_payslip_status()` in `modules/accounting/accounting.php` (hook `after_update_payslip_status`) when the status becomes `payslip_closing`.
- Other payslip functions in accounting.php (`acc_delete_payslip_convert`, the else branch of `acc_update_payslip_status`) only delete entries. `acc_payslip_class_save` only stores the class.
- `Hr_payroll_model.php` has no accounting code (no `acc_` calls).

## T072-T073 what was built
- `hrp_post_to_accounting` (default 1): install.php and migration 120 in hr_payroll. Toggle in `data_integration.php`, saved in `Hr_payroll_model::update_data_integration()` as a core option (hidden 0 plus checkbox 1).
- Guard `if (get_option('hrp_post_to_accounting') != 1) { return false; }` at the top of `automatic_payslip_conversion`. Nothing deleted.
- `accounting/views/setting/payslip.php` shows a short message instead of the mapping fields when the toggle is 0. `data_integration.php` has no mapping fields, so nothing to hide there.

## T074 What is a payslip deduction subtracted from?
- `payslip_checked()` (~6667) does no maths. It only returns false if a closed payslip already exists for that month and template.
- The deduction amount is built when the payslip is generated (Hr_payroll_model ~5610-5660). Each `deduction_<id>` column is a percentage: rate / 100 times either the Gross Pay column ("gross" basis), or the salary, allowance or earning item chosen in the deduction setting.
- The code does not hard-code what the deduction is subtracted from. Total Deductions and Net Pay are formula columns (type "caculator") of the payslip template, written by the admin as spreadsheet formulas.
- So the deduction reduces gross only if the template sets Net Pay as Gross Pay minus Total Deductions. I could not see the formulas of your live templates; check them in the payslip template.
- Accounting posts the stored Net Pay, Total Insurance and Income Tax (PAYE) figures of each payslip line.
