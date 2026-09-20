# Lotus Requirements: Implementation Plan

Spec: `spec.md`. Modules live in `modules/`. Code is PHP (CodeIgniter, Perfex CRM modules).

## Facts found in the code
1. **The rate bug.** `modules/accounting/helpers/Accounting_helper.php` has `acc_get_currency_rate($from,$to)` (line ~1032) and `acc_get_secondary_currency_amount($base_amount)` (line ~1061). Both read the single live row in table `currency_rates`. The journal entry list (`Accounting.php` `journal_entry_table`, line ~3688) and the chart of accounts call `acc_secondary_currency_cell_html()`, so changing a rate changes every old number on screen.
2. **Rate history exists.** Table `currency_rate_logs` stores a row per pair per day (written by `cronjob_currency_rates` in `Accounting_model.php`, line ~33820) and on each edit (`update_currency_rate`, line ~33778). This is the source for the backfill.
3. **Ledger table.** `acc_account_history` has one row per ledger line (`add_journal_entry`, `Accounting_model.php` line ~5659). It already has a `currency_rate` column, used by invoice/purchase conversions with its own meaning. We will not reuse it. We add new columns.
4. **Journal entry code.** Controller `new_journal_entry` (line ~3805), `journal_entry_table` (line ~3688), `journal_entry_export` (line ~6650, currently Excel only). Model `add_journal_entry`, `update_journal_entry` (line ~8951), `get_journal_entry` (line ~8891). Views: `views/journal_entry/journal_entry.php` (form) and `manage.php` (list).
5. **Cash flow.** The existing report `get_data_statement_of_cash_flows` (line ~7381) is a statement, not a monthly one. The monthly report is new.
6. **Latest migrations.** Accounting is at `147_version_147.php`. For `timesheets`, `hr_payroll` and `hr_profile`, each task says to take the next number after the highest file in that module's `migrations/` folder.
7. **HR.** Timesheet already has a table `timesheets_overtime_setting`. Leave balance per staff and year is in `timesheets_day_off` (`staffid, year, total, remain, accumulated`). Payslips are template driven: OT hours come from `attendance_calculation_v2` and the payslip columns are formulas set in Payroll settings.

## Design decisions
- **Rates.** New table `acc_currency_rate_periods` (manual rates with from/to). New column `auto_rate` on `currency_rates`. One new helper `acc_get_rate_at($from,$to,$datetime)` is the only place that picks a rate.
- **Ledger.** New columns on `acc_account_history`: `doc_currency`, `doc_rate`, `doc_debit`, `doc_credit`, `sec_currency`, `sec_debit`, `sec_credit`. Reports keep using `debit` and `credit` (base). Secondary amounts are summed from `sec_debit`/`sec_credit`, never recalculated.
- **Backfill.** A one-time migration fills the new columns from `currency_rate_logs`, and writes `acc_rate_backfill_report`.
- **HR settings.** New options are stored with `add_option()` in each module's `install.php` and a migration. Every option is per company (keyed the same way the module already keys its settings; check `get_option` usage in that module before writing).
- **Attachments.** Journal entry files use the core Perfex `files` table with `rel_type = 'acc_journal_entry'`, following how other accounting documents attach files.

## Phases
1. Setup: a migration file for each module and a shared test checklist.
2. Foundation: rate tables and helper (US5), ledger columns and backfill (US6).
3. US6 and US5 screens: rate settings UI, journal entry currency dropdown, secondary display fix, then bank and invoice.
4. US1 and US2 (P1 HR): time ranges, rates, cap, delay allowance, payslip columns.
5. US7 journal entry view, exports, attachments.
6. US3 leave carry-over and US4 payroll/accounting toggle.
7. US8 monthly cashflow report.
8. Polish and final checks.

Ordering rule: phase 2 must be finished before phase 3. Phases 4, 5, 6 and 7 are independent of each other.

## Risks
- `Accounting_model.php` is very large (38,000 lines). Tasks touch one function at a time.
- The payslip templates are formula driven, so new payslip columns need a task that registers the column names first.
- Bank transaction and invoice screens were not read in detail. Their tasks start with a locate-and-report step.
