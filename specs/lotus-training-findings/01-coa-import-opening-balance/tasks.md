# 01 Tasks

Shared rules: `../README.md`. Design details: `plan.md`. One task = one small change. Test after each.

## Model (`modules/accounting/models/Accounting_model.php`, put these after `insert_batch_account` ~20109)
- [ ] T001 Add `find_account_for_import($number, $name, $parent_id)`: if `$number` is not empty return the first `acc_accounts` row with that `number`; else return the first row with the same `name` and `parent_account` (treat '', null, 0 as the same); else return false.
- [ ] T002 Add `set_account_opening_balance($account_id, $balance, $as_of)`: (1) delete the account's old opening rows (see `plan.md`), (2) save `balance` and `balance_as_of` on `acc_accounts`, (3) if balance is not 0 insert the reconcile row and the two history rows. Copy the sign logic of `add_account` lines 2024-2190 exactly (it handles negative numbers). Do not copy `update_account`.
- [ ] T003 Add `upsert_account_from_import($row)`: call T001; if found update its type, sub type, number, name, parent and return `['id'=>..., 'action'=>'updated']`; if not found insert with `active = 1` and return `['id'=>..., 'action'=>'created']`.

## Import (`modules/accounting/controllers/Accounting.php`, function `import_file_xlsx_account` ~8715)
- [ ] T004 Read two new columns: column 5 `opening_balance`, column 6 `balance_as_of` (empty string if missing, so old files still work).
- [ ] T005 Validate: balance must be a number (negative is fine), else row error. Date empty = today. Date bad = row error. Do not check closing dates.
- [ ] T006 Replace the `insert_batch_account` call with a per-row `upsert_account_from_import`. Handle rows with an empty "Sub account of" first, then the rest, so parents exist before children.
- [ ] T007 After the upsert, if the balance cell is not empty call `set_account_opening_balance`. If the account is a parent of another row in the sheet, or already has child accounts, make it a row error "Parent account balance is the sum of its children".
- [ ] T008 Return `total_row_created` and `total_row_updated` in the JSON at the end. Add the two new columns to the error-file header.

## Screen, sample, text
- [ ] T009 [P] In `modules/accounting/assets/js/chart_of_accounts/import_excel_account_js.php` show created / updated / failed counts in the result message.
- [ ] T010 [P] In `modules/accounting/views/chart_of_accounts/import_excel.php` add one sentence: two new columns, matching by number (else name + parent), re-import updates.
- [ ] T011 [P] Add the two columns "Opening balance" and "Balance as of" with two example rows to `modules/accounting/uploads/file_sample/Sample_import_account_file_en.xlsx` and `_vi.xlsx`.
- [ ] T012 [P] Add the new error texts to the english and arabic language files (balance invalid, date invalid, parent balance, duplicate row).

## Test
- [ ] T013 Import a 5-account sheet with no balances, then the same sheet with balances: account count stays 5, each account has one opening entry.
- [ ] T014 Import the same sheet with balances twice: balances are not doubled. Change one balance and re-import: only that one changes.
- [ ] T015 Import a `-1200` balance dated last year: accepted, posted on the opposite side. Put a balance on a parent row: it lands in the error file.
