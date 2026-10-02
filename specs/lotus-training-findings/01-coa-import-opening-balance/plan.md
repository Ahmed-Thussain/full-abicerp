# 01 Plan

## Files touched
- `modules/accounting/controllers/Accounting.php`: rewrite the loop in `import_file_xlsx_account()` (~8715-8955).
- `modules/accounting/models/Accounting_model.php`: new methods `find_account_for_import()`, `upsert_account_from_import()`, `set_account_opening_balance()`; keep `insert_batch_account()` untouched.
- `modules/accounting/views/chart_of_accounts/import_excel.php` and `assets/js/chart_of_accounts/import_excel_account_js.php`: instructions text, result message with created/updated/failed.
- `modules/accounting/uploads/file_sample/Sample_import_account_file_en.xlsx` and `_vi.xlsx`: add two columns.
- Language files (english + arabic).

## Design
Sheet columns (0-based): 0 Type, 1 Sub type, 2 Account code, 3 Account name, 4 Sub account of, **5 Opening balance, 6 Balance as of**. Error file gets the reason as the last column.

Row processing (per row, top to bottom; parents resolve as today via the sheet's own top-level names or existing accounts):
1. Validate as today, plus balance numeric and date valid (`d/m/Y` or the app date format via `to_sql_date`).
2. `find_account_for_import($number, $name, $parent_id)`:
   - if `$number != ''`: `acc_accounts` where `number = $number` (first match);
   - else: where `name = $name` and `parent_account = $parent_id` (parent 0/'' for top level). Also compare against `_l(key_name)` for default accounts with empty `name`.
3. If found: update type, detail type, number, name, parent, active. If not found: insert.
4. If balance cell is not empty: `set_account_opening_balance($account_id, $balance, $as_of)`.

`set_account_opening_balance()` is idempotent:
1. Load account (need its `account_type_id`).
2. Find the Opening Balance Equity account (`account_type_id = 10`, `account_detail_type_id = 71`), create it if missing (copy the block in `add_account`).
3. Delete the previous opening postings for this account: `acc_account_history` rows where `rel_type = 'deposit'`, `rel_id = 0` and (`account = $id` and `split = $obe_id`) or (`account = $obe_id` and `split = $id`); delete `acc_reconciles` rows where `account = $id` and `opening_balance = 1`.
4. If balance is 0, stop after updating `acc_accounts.balance = 0`.
5. Otherwise update `acc_accounts.balance/balance_as_of` and insert the reconcile row + two history rows. Copy the debit/credit sign logic from `add_account` (types 7, 15, 8, 9 debit-natural; others credit-natural) exactly.

Parent rule: before posting, if the account has children (`acc_accounts.parent_account = $id`) or any other row in the same sheet names it as parent, reject with the language key `acc_import_parent_balance_error`.

No closed-books check: do not read `acc_close_the_books` / `acc_closing_date` in the import. Negative balances are posted with the opposite-side branch of the `add_account` sign logic (copy it as is; do NOT copy `update_account`, which only handles balances above 0).

Wrap each row's DB writes in `$this->db->trans_start()/trans_complete()` so a failed row leaves nothing half-posted.

## Risks
- Existing manual openings created by the form use the same `rel_type = 'deposit'` rows, so the delete in step 3 would also remove a manual one for that account. That is intended (import is the source of truth for that account) but must be shown in the popup text.
- Number matching: if two accounts share a number, the first is used. Report a warning row.
