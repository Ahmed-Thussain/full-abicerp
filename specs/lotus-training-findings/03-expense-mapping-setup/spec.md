# 03 Mapping Setup: expenses added earlier are not posted to accounting

## Problem (clarified by the user)
On Lotus: the chart of accounts was imported, expenses were added, then Mapping Setup (`accounting/setting?group=mapping_setup`) was filled in. The expenses did not appear in the Account History report. The user opened the transaction screen, found the expenses "not mapped", mapped (converted) them again, and only then did they show in the report.

## Root cause (from the code)
Expenses are posted to the ledger only at the moment they are added or edited: hooks `after_expense_added`, `expense_updated`, etc. -> `automatic_expense_conversion($expense_id)` (`modules/accounting/models/Accounting_model.php` ~12337). That function does nothing when `acc_expense_automatic_conversion` is 0 or when the default accounts are empty. So expenses created BEFORE Mapping Setup was saved were never posted, and saving Mapping Setup does not go back and post them. The same applies to invoices, payments and other automatic conversions. The transaction screen's manual convert (`Accounting::convert()` ~3505) is the only way to post them afterwards, one by one.

Secondary defects seen in the same code (kept, as safeguards):
- Hard-coded default account ids 13 and 80 in the default routine (~11579-11580); wrong after a chart import.
- Mapping rows with an empty account are saved and then produce ledger rows with an empty account.
- Skips are silent (closed books, purchase-order-linked expense, switch off).

## User stories
### US1 (P1): Post already-existing transactions after Mapping Setup (ALL transaction types, confirmed)
- **Manual and selective, decided by the user:** saving Mapping Setup posts nothing by itself. It is the user who chooses, each time, which old transactions to post, and can set the accounts by hand for transactions that have no mapping or that should go to a different account.
- After saving Mapping Setup, the page shows a panel "Not posted to accounting yet" with one row per transaction type that has pending items (type name, count). Clicking a type opens a list of its pending transactions (date, number/reference, amount, currency) with a checkbox per row and **Select all**.
- **Convert selected** posts only the ticked transactions using the current mapping. Each ticked transaction that ends up with no ledger entry (no mapping resolves for it) stays in the list marked "No mapping", with a **Set accounts** action.
- **Set accounts** (any row, mapped or not) opens the existing manual convert dialog of the Transactions screen (`get_data_convert` / `convert`, which lets the user pick the debit and credit accounts per transaction), so the user can choose a different account or post a few transactions by hand. Nothing new is built for the manual dialog.
- Optional shortcut: **Convert all pending of this type** (with a confirm box) for large lists.
- Types covered: everything that has an `automatic_*_conversion` in `Accounting_model.php`: invoices, payments, credit notes (+apply, refund), expenses, payslips, purchase orders, purchase invoices, purchase payments, purchase refunds, purchase order returns, debit notes (+apply, refund), stock import/export, loss adjustment, opening stock, manufacturing orders, omni-sales refunds/returns, pay bills, checks, fixed-equipment (asset, license, component, consumable, maintenance, depreciation). Only types whose own conversion switch is on are offered.
- "Pending" = a source record with no `acc_account_history` row of that type and id (an exact NOT EXISTS test, not the current "total minus converted" count). Cancelled/draft records that the manual convert screen already excludes stay excluded.
- Posting uses the current mapping, each transaction on its own document date, and reports "N posted, M without mapping". Running it twice never duplicates (each conversion deletes its previous rows first).
- Acceptance: create 3 expenses and 2 invoices with conversion off, set accounts for expenses only. Open the panel, tick the 3 expenses and 1 invoice, Convert selected: the 3 expenses post, the invoice stays listed as "No mapping", the second invoice is untouched. Use Set accounts on the invoice, pick accounts by hand: it posts with those accounts.

### US2 (P1): Correct accounts
An expense produces debit = expense account ("Deposit to"), credit = paid-from account ("Payment account"), base-currency amounts, on the expense date.

### US3 (P2): Prevent bad configuration
- Saving Mapping Setup (general, category, payment-mode mappings) with conversion on and an empty or non-existing account is refused with a clear message.
- The hard-coded default ids 13/80 are replaced by a lookup by account key; if the saved default account no longer exists the page shows a red warning.
- Expense labels read "Expense account (debit)" and "Paid from account (credit)" (stored option names unchanged).

### US4 (P3): Visible skips
When an expense is not posted automatically the expense screen shows the reason (closed books, PO-linked, conversion off, accounts missing).

## Interpretation to confirm
Your answer was "yes, it should ... only from transactions that will be manual each time". I read this as: no automatic posting on save; the user manually picks which transactions to post and can set accounts by hand. If you instead want an automatic post-on-save option (a switch), say so and I will add it as a small extra story.
