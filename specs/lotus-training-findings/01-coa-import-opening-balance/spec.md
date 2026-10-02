# 01 Chart of Accounts import: opening balance and no duplicates

## Problem
Importing the chart of accounts (Excel) can only create accounts. There is no way to give an account its opening balance in the sheet. If the same accounts are imported again, the importer inserts them again, so the user ends up with one copy with a balance and one without.

Today: `Accounting::import_file_xlsx_account()` (`modules/accounting/controllers/Accounting.php` ~8715) reads 5 columns (Type, Sub type, Account code, Account name, Sub account of) and calls `Accounting_model::insert_batch_account()` (~20109), which is a plain `insert_batch`.
Opening balances today are created only from the single-account form (`add_account` / `update_account`, model ~1996 and ~2206): they write `acc_accounts.balance` + `balance_as_of`, an `acc_reconciles` row (`opening_balance = 1`) and two `acc_account_history` rows against the "Opening Balance Equity" account (type 10, detail type 71).

## Decisions (confirmed with the user)
- Match key for an existing account: **account number** when the row has one; otherwise **name + parent account**.
- Parent accounts: **parent balance = sum of its children**. Only leaf accounts carry an entered balance. The parent rolls up in reports as it does today.

## User stories

### US1 (P1): Import an opening balance per account
As an accountant I add two columns to the import sheet, **Opening balance** and **Balance as of** (date), and after import each account has that opening balance posted the same way the account form posts it.
- Acceptance: a sheet row with balance 5,000 as of 2026-01-01 produces `acc_accounts.balance = 5000`, `balance_as_of = 2026-01-01`, one opening reconcile row, and two ledger rows against Opening Balance Equity (sign rules identical to `add_account`).
- Old sheets without the two columns still import (balance treated as empty).
- Empty balance cell = do not post anything and do not change an existing balance.
- Balance 0 typed explicitly on an existing account = clear its opening postings.

### US2 (P1): Re-import updates instead of duplicating
As an accountant I re-upload the same sheet (now with balances) and the existing accounts are updated, not duplicated.
- Acceptance: import sheet A (no balances) then sheet A again with balances: account count is unchanged; each account has exactly ONE opening posting pair with the new balance.
- Import sheet A with balances twice: still exactly one posting pair per account (balance not doubled).
- Changing a balance in the sheet and re-importing replaces the old opening posting with the new amount.
- Matched accounts also get their type, sub type, name and parent refreshed from the sheet.

### US3 (P2): Clear result report
After import the popup shows: created N, updated N, failed N. Failed rows go to the existing error Excel with a reason.

### US4 (P2): Sample file and validation
- The English and Vietnamese sample files include the two new columns.
- A balance on a row that is a parent of other rows in the same sheet (or of existing accounts) is rejected with reason "Parent account balance is the sum of its children".
- Non-numeric balance, or invalid date, is a row error. Negative numbers are valid.
- Two rows in the same sheet resolving to the same account are a row error on the second row.

## Out of scope
Changing the single-account form. Secondary-currency balances (opening balances are base currency).

## Confirmed by the user
- Balances can be **positive or negative** (for example a negative balance carried forward from the previous year). Positive = the account's natural side, negative = the opposite side, exactly as `add_account` already does.
- **No closed-books / date check.** Previous-year "balance as of" dates are allowed; the importer never rejects a row because of the accounting close date.
