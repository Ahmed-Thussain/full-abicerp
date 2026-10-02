# Monthly Cash Flow: where the data comes from

Route: `accounting/rp_cashflow_monthly` -> AJAX `cashflow_monthly_data` -> `Accounting_model::get_data_cashflow_monthly($year)` (`modules/accounting/models/Accounting_model.php`, ~line 7570). Excel export: `cashflow_monthly_export`.

Source table: only `acc_account_history` (the posted ledger). No invoices, expenses or bank feeds are read directly.

How it is built:
1. Cash accounts = every row in `acc_accounts` with `account_type_id` 3 or 16 (Bank and Cash types). Not filtered by active flag.
2. Cash lines = `acc_account_history` rows on those accounts dated within the chosen calendar year (Jan 1 to Dec 31). Movement per line = `debit - credit`.
3. Lines are grouped by document (`rel_type|rel_id|month`). Lines with no `rel_id` (for example manual/opening entries) stay as their own group.
4. For each document, the counterpart lines (same `rel_id`, same year, accounts that are NOT cash accounts) are read. Each counterpart account gets `credit - debit`.
5. Rows of the report = the counterpart account (what the cash was spent on or received from). Columns = months 1 to 12 plus total. If a cash movement has no counterpart, it shows under "Unallocated".

Things to know:
- Amounts are base-currency ledger amounts (`debit`/`credit`). It does NOT use `sec_debit`/`sec_credit`, so it is not secondary-currency aware (covered by spec 04).
- Opening balances posted with `rel_id = 0` are grouped per line, so they appear as separate movements rather than being netted.
- Transfers between two cash accounts have no non-cash counterpart, so they appear as Unallocated.
- Cash basis: it shows money movement on bank/cash accounts, not invoices or bills that are unpaid.
