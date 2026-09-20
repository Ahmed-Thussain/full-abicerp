# HR notes (Phase 4)

## T037: where Timesheet settings are saved
- Screen: `modules/timesheets/views/includes/timekeeping_settings.php` (form posts to `timesheets/setting_timekeeper`).
- Controller `Timesheets::setting_timekeeper()` calls model `Timesheets_model::setting_timekeeper($data)` (about line 4587).
- Options live in table `timesheets_option` (name/value/auto rows), read with `get_timesheets_option($name)`; written by `UPDATE`, so a row must exist (install/migration inserts it). New helper `save_timesheets_option()` upserts.
- `get_overtime_setting` (line ~3963) is unrelated: it reads the table `timesheets_overtime_setting` (OT pay rules by shift), not these options.
- New options: ts_ot_split_enabled, ts_ot_morning_start/end, ts_ot_night_start/end, ts_delay_allowance_enabled, ts_delay_allowance_hours.

## T043: where OT is stored
- Table `timesheets_additional_timesheet`: one row per OT record. `creator` = staff id, `additional_day` = the date (VARCHAR, Y-m-d), `timekeeping_value` = approved hours, `time_in` / `time_out` = 'H:i' start and end, `timekeeping_type` = 'OT', `status` = 1 when approved.
- Payroll reads it in `Hr_payroll_model` near line 2452 (`$overtime_sql_where`), filtered by month, type OT and status 1, then sums into `overtime_hours` per staff.
- One OT record maps to one date (`additional_day`); an OT that crosses midnight is still one record on its start date (end time earlier than start time means next day).
- Late arrival: `timesheets_timesheet` rows with `type = 'L'`, `value` = hours, `date_work` = the day (written in Timesheets_model ~line 5000 and ~1786).

## T050: three places to add a payslip column key
1. Register the column in `hrp_payroll_columns` (taking_method system, function_name = key): `hr_payroll/install.php` and a migration (done in 119_version_119.php).
2. Fill the value in the payslip build: `Hr_payroll_model::add_payslip()`, in the loop over `$hrp_attendance` (the row is merged into `$staffs_id`).
3. Label: `hr_payroll/language/english/hr_payroll_lang.php` (key = column key).
Keys that are not in `payroll_system_columns()` are saved into the payslip `json_data` by `payslip_close()`, so no new DB column is needed.
Note: the attendance grid screen (`attendance_calculation_v2`, ~line 2019) still shows only `overtime_hours`; it was not changed.
