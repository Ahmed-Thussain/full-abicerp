# HR follow-up notes

## Leave carry-over: days allowed
With ts_leave_carry_enabled = 1 the allowed-days figure (request form, get_data_date_leave, get_remain_day_of,
requisition number_of_days, leave edit page) is the sum of remain from get_leave_balances (annual leave type 8,
ts_leave_carry_years window). Helper: Timesheets_model::get_leave_remain_with_carry. Toggle off = old behaviour.

## Leave carry-over: crediting days back
Provenance of each deduction is not stored, so credit_leave_days_with_carry uses a LIFO rule.
deduct_leave_days_with_carry takes the newest year first, so the oldest year in the window that still has used
days (days_off > 0 and remain < total) was drawn last. Credit refills that year first, then the next newer one.
Each year is capped at total (and days_off never below 0). Used for delete_requisition and the reject/revert path.
Limitation: if requests are reverted out of order, days can return to a different year than they were taken from;
the total balance is always right.

## Attendance grid
With hrp_ot_separate_columns = 1 the attendance grid gets read-only ot_morning_hours / ot_night_hours columns
appended after standard_workday (values from calculate_ot_result). They are not in attendance_key and are
sliced off before saving. attendance_calculation_v2 rows also carry the two values.
