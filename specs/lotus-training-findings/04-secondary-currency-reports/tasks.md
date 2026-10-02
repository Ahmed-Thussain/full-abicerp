# 04 Tasks

Shared rules: `../README.md`. Design details: `plan.md`. Rates page: `admin_url('accounting/setting?group=currency_rates')`. Model = `modules/accounting/models/Accounting_model.php`, helper = `modules/accounting/helpers/Accounting_helper.php`.

## A. Shared pieces (do first)
- [ ] T001 [P] Add texts to the english and arabic language files: `acc_report_currency`, `acc_report_missing_rate_banner` ('No exchange rate between %s and %s for %s date(s), starting %s.'), `acc_report_add_rate`, `acc_report_incomplete_total`.
- [ ] T002 Helper: add `acc_resolve_report_currency($requested_id)` after `acc_get_rate_at` (~1063): null when empty or base currency, else `['id','name','base_name']`.
- [ ] T003 Helper: add `acc_get_report_rate_at($from, $to, $datetime)`: copy steps 1-4 of `acc_get_rate_at` (same currency = 1; manual period covering the date; live rate if `auto_rate = 1`; latest manual period starting before the date) then `return null;`. Do not copy the last two fallbacks. Do not edit `acc_get_rate_at`.
- [ ] T004 Model: add `public $missing_rate_dates = [];` and `report_amount_sql($currency, $from, $to)` near `recalculate_secondary_amounts` (~34131). It gets the distinct dates in range, looks up each rate with T003 and returns two SQL strings `debit` and `credit`: `ROUND(h.debit * CASE DATE(h.date) WHEN 'd' THEN rate ... ELSE NULL END, 4)`. Dates with no rate are left out of the CASE and saved in `missing_rate_dates`. The history table alias must be `h`.
- [ ] T005 Create `modules/accounting/views/report/includes/report_currency_bar.php`: a Currency dropdown (all currencies, base first) and, if `missing_rate_dates` is not empty, a red `alert alert-danger` with the banner text and a link to the rates page.
- [ ] T006 Create `modules/accounting/assets/js/report/report_currency_js.php`: `getReportCurrency()` returns the dropdown value; changing the dropdown clicks the report's existing filter button.

## B. Reports (same 4 steps for each report)
Steps: (1) in the model function, if `acc_resolve_report_currency(...)` is not null use the `report_amount_sql` strings in place of `h.debit` / `h.credit` in every sum; (2) in the controller pass `report_currency` from the request and the currency list to the view; (3) in the view include the bar from T005 and show "Currency: CODE"; (4) include the JS from T006 and send `report_currency` with the filter request.
- [ ] T007 Trial Balance (`get_data_trial_balance` ~8841, `rp_trial_balance` ~4381).
- [ ] T008 Account History (`get_data_account_history` ~11319, `rp_account_history` ~4299).
- [ ] T009 General Ledger (`get_data_general_ledger` ~8241, `rp_general_ledger` ~4314).
- [ ] T010 Test T007-T009 (see section D) and show the result to the user before continuing.
- [ ] T011 Balance Sheet (`get_data_balance_sheet` ~5785, `rp_balance_sheet` ~3916).
- [ ] T012 [P] Balance sheet comparison, detail, summary (`rp_*` ~3937-3979).
- [ ] T013 Profit and Loss (`get_data_profit_and_loss` ~7288, `rp_profit_and_loss` ~4111).
- [ ] T014 [P] P&L comparison, detail, year-to-date, as % of income, 12 months.
- [ ] T015 [P] Journal, Recent Transactions, Transaction Detail by Account, Transaction List by Date, Deposit Detail, Check Detail.
- [ ] T016 [P] Statement of Cash Flows, Changes in Equity, Income by Customer, Account List (balances).
- [ ] T017 [P] AR/AP ageing and tax reports. If rows are documents, not ledger lines, convert each amount at its document date with `acc_get_report_rate_at`.
- [ ] T018 [P] Monthly Cash Flow (`get_data_cashflow_monthly` ~7570, view `report/includes/cashflow_monthly.php`).

## C. Exports
- [ ] T019 For each report above, pass `report_currency` to its PDF / Excel / print handler and print "Currency: CODE" under the title (and the missing-rate line if there are missing dates).

## D. Test
- [ ] T020 Base currency chosen: every report looks exactly as before.
- [ ] T021 Rates starting 2026-01-01 and 2026-06-01: a 2026-03-15 line uses the first rate, a 2026-07-01 line the second.
- [ ] T022 Only a rate starting 2026-03-01: lines before it show the red banner and are left out of the totals. Add an earlier rate: banner gone.
- [ ] T023 Balance Sheet in another currency still balances.
