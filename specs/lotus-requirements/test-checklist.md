# Lotus Requirements: Test Checklist

## US1 Overtime split, rates, cap

- [ ] 

## US2 Late-arrival allowance

- [ ] 

## US3 Leave carry-over

- [ ] 

## US4 Payroll to accounting toggle

- [ ] 

## US5 Exchange rates (auto / manual periods)

- [ ] Not run (no database access in this environment): create a manual rate period, confirm `acc_get_rate_at` returns it inside the window and falls back per the T005 rules.
- [x] `php -l` passes on Accounting.php, Accounting_model.php, Accounting_helper.php, migrations 148/149, install.php, and the new/changed views.

## US6 Currency on documents and stored secondary amounts

- [ ] T013 NOT RUN (no database access): run migrations 148 and 149 on a DB copy, then `SELECT COUNT(*) FROM acc_account_history WHERE doc_rate IS NULL` and expect 0.
- [ ] T031 NOT RUN (no database access): create a USD entry, change USD to EGP rate, reload journal entry list and chart of accounts; the old entry's secondary amount must not change.
- [ ] T036 NOT RUN (no database access): repeat T031 for a bank feed transaction (Add transaction modal) and an invoice conversion.
- [x] `php -l` passes on every changed PHP file.

### T032 bank transaction notes
- Save function: `Accounting_model::add_transaction_save($data)` (called from controller `Accounting::add_transaction_save`, also by `bulk_add_transaction_save`). It writes two `acc_account_history` rows with `rel_type = 'banking'`.
- Form: modal `#add-transaction-modal` in `modules/accounting/views/banking/banking_feeds.php`, posted by `add_transaction_save()` in `modules/accounting/assets/js/banking/plaid_new_transaction.js`.
- `match_transaction_save` only updates existing ledger lines and was not changed.

### T029 review note
- Chart of accounts: the DataTable row and the tree account node (single accounts) now use `get_account_sec_balance`. The type-group total and class-group total in the tree (`acc_secondary_currency_cell_html($type_total)` and `($class_total)` in `Accounting.php`) are TREE TOTALS and were left on the live rate for review.
- `get_account_sec_balance` mirrors `acc_get_account_balance` (sign by account type, includes sub-accounts, cash-method invoice filter) rather than a plain `SUM(sec_debit) - SUM(sec_credit)`, so the secondary column has the same sign as the base column.

### T030 callers still using live rate
- `modules/accounting/controllers/Accounting.php`, chart of accounts tree, type group total: `acc_secondary_currency_cell_html($type_total)` (about line 1450)
- `modules/accounting/controllers/Accounting.php`, chart of accounts tree, class group total: `acc_secondary_currency_cell_html($class_total)` (about line 1470)
- `acc_get_secondary_currency_amount` is only called from `acc_secondary_currency_cell_html` in `Accounting_helper.php`.

## US7 Journal entry view, exports, attachments

- [ ] 

## US8 Monthly cashflow report

- [ ] 

