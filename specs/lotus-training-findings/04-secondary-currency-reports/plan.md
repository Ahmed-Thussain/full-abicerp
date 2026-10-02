# 04 Plan

## Files
- `modules/accounting/helpers/Accounting_helper.php`: report-currency helper.
- `modules/accounting/models/Accounting_model.php`: report models (`get_data_trial_balance` ~8841, `get_data_account_history` ~11319, `get_data_general_ledger` ~8241, `get_data_balance_sheet` ~5785, `get_data_profit_and_loss` ~7288, `get_data_cashflow_monthly` ~7570, plus comparison/detail/summary/12-month/YTD variants and journal/transaction lists).
- `modules/accounting/controllers/Accounting.php`: `rp_*` methods (~3916-4381, 6394-6426, 8532-8648) and their data/export endpoints.
- `modules/accounting/views/report/details/*.php`, `views/report/includes/*.php`.
- New `modules/accounting/views/report/includes/report_currency_bar.php` and `modules/accounting/assets/js/report/report_currency_js.php`.
- Language files (english + arabic). No migration, no new options.

## Core design
1. **Resolve currency**: `acc_resolve_report_currency($requested_id)` returns `null` (= base, no conversion) when the request is empty or the base currency, else `['id','name','base_name']`.
2. **Strict rate lookup**: `acc_get_report_rate_at($from, $to, $datetime)` in the helper file: same steps 1-4 as `acc_get_rate_at` (manual period covering the date; live rate when `auto_rate = 1`; latest manual period with `effective_from <= date`), then returns `null`. No earliest-rate or initial-rate fallback.
3. **One SQL expression for every report**: `Accounting_model::report_amount_sql($currency, $from, $to)` returns `['debit' => ..., 'credit' => ...]` fragments to use in place of `h.debit` / `h.credit` (the history table alias must be `h`), and fills `$this->missing_rate_dates`. It reads the distinct `DATE(date)` in range, calls `acc_get_report_rate_at` for each, and builds `ROUND(h.debit * CASE DATE(h.date) WHEN 'd' THEN rate ... ELSE NULL END, 4)`. Dates with a `null` rate are omitted (result NULL) and recorded as missing. `sec_debit/sec_credit` are not used.
   Each report model uses these fragments wherever it currently sums `debit`/`credit`, keeping its own grouping and sign rules. Reuse those rules by parameterising the column expressions, do not copy them.
4. **Result**: each report result gets `currency` and `missing_dates`; views print the banner from the shared partial.
5. **Filter plumbing**: each report page's filter JS posts a `report_currency` value from the dropdown; controllers put it in `$data_filter['report_currency']`.

## Delivery order (each phase leaves a working product)
1. Helpers + shared partial.
2. Trial Balance, Account History, General Ledger (ledger-line reports).
3. Balance sheet family, P&L family.
4. Remaining reports, then exports.
5. Monthly cash flow.

## Risks
- Report models are long and partly duplicated across variants. If a variant cannot be done cleanly, list it in `test-checklist.md` as not covered rather than half-doing it.
- CASE has one branch per distinct posting date (~370 per year): acceptable; only batch by month if a multi-year report is slow.
- If some reports total through stored balances rather than sums of lines, they must be routed through `report_amount_sql` too, otherwise base totals leak into a converted report. Check each report's totals in verification.
