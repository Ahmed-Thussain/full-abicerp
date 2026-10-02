# 04 Report currency dropdown

## Problem
Reports only show base-currency amounts. Users want to view any report in another currency.

## Decision (updated by the user)
No secondary-currency columns and no new accounting settings. Every report gets a **Currency dropdown**. Choosing a currency shows the whole report (same columns) in that currency. Default is the base currency, so nothing changes until the user picks another one. If a rate is missing, a **red label** tells the user to add one.

## Rate rule (confirmed by the user)
A rate is added with a start date/time and applies to every date from that start onward, until a later rate starts (same as the manual rate periods). A date earlier than the first rate has **no rate**, so it is "missing" and triggers the red banner.

## What already exists (reuse)
- `acc_get_rate_at($from, $to, $datetime)` in `modules/accounting/helpers/Accounting_helper.php` (~1063): picks a rate for a date. Its steps 1-4 (manual period covering the date; live rate when auto is on; latest manual rate starting before the date) already match the rule above. Its last two fallbacks (earliest manual rate for earlier dates, then the initial rate) do NOT, because they invent a rate for dates before the first one. Reports use a strict variant without those two fallbacks. `acc_get_rate_at` itself stays unchanged (posting still uses it).
- Rate management page: `accounting/setting?group=currency_rates`.
- The stored `sec_debit/sec_credit` columns are NOT used by reports, because they were filled with the fallback rate; reports always convert on the fly with the strict rule.

## User stories
### US1 (P1): Currency dropdown on every report
- Every report page has a "Currency" dropdown listing the base currency and all other currencies. Default = base. Selection applies to that view only (not saved as a setting).
- With base selected the report is identical to today.
### US2 (P1): Amounts in the chosen currency
- Each ledger line is converted at the rate of its own date (`acc_get_rate_at(base, chosen, line_date)`). Totals, sub-account roll-ups, running balances and comparison columns are sums of converted lines, not a re-conversion of the base total.
- Sign and grouping rules by account type stay exactly as in the base report.
- Column headings do not change; the report title area shows "Currency: XXX".
- On-screen table, print, PDF and Excel export all use the chosen currency.
### US3 (P1): Missing-rate warning
- If any line in the report range is dated before the first rate (or there is no rate at all) between base and the chosen currency, a **red banner** shows: "No exchange rate between BASE and XXX for N date(s), starting YYYY-MM-DD. Add a rate." with a link to `accounting/setting?group=currency_rates`.
- Those lines are left out of the totals and the totals are marked "incomplete" with an asterisk. They are never shown as 0 silently.
- If the currency has no rate at all, the same banner shows and all amounts are blank.
### US4 (P2): Monthly Cash Flow
The Monthly Cash Flow report (see `../05-cashflow-monthly-answer.md`) follows the same dropdown.

## Reports in scope
All financial reports under Accounting > Reports, as agreed. Ageing and tax reports are included by default (converted at each document date). Budget reports and Bank Reconciliation reports are excluded (budgets have no currency, reconciliation follows the bank statement).

## Out of scope
Changing how ledger lines are posted; period-end revaluation and unrealised gain/loss.

## Confirmed
- The dropdown does not remember the last choice; it always starts at base.

## NEEDS CLARIFICATION
- Confirm exclusion of budget and bank reconciliation reports.
