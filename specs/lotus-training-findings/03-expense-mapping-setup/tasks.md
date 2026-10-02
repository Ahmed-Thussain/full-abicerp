# 03 Tasks

Shared rules: `../README.md`. Design details: `plan.md`. One task = one small change. Test after each. Model file = `modules/accounting/models/Accounting_model.php`, controller = `modules/accounting/controllers/Accounting.php`, view = `modules/accounting/views/setting/general_mapping_setup.php`.

## A. Pending list with manual convert (main fix)
- [ ] T001 Model: add `get_conversion_registry()` after `count_expense_not_convert_yet` (~12797) returning three entries (`invoice`, `payment`, `expense`). Each entry has: label key, table, rel_type, extra where, convert method, on/off option, Transactions `group`/`tab`, columns for date/reference/amount. Values come from reading `automatic_invoice_conversion`, `automatic_payment_conversion`, `automatic_expense_conversion`.
- [ ] T002 Model: add `get_pending_rows($type, $limit, $offset)` and `count_pending($type)`: rows of the entry's table that have no `acc_account_history` row with that `rel_type` and `rel_id`. Return empty/0 when the type's option is off.
- [ ] T003 Model: add `convert_pending($type, $ids)`: call the entry's convert method for each id; return which ids now have history rows (`posted`) and which do not (`no_mapping`).
- [ ] T004 Controller: add `pending_rows()` (returns the rows as JSON) and `convert_pending()` (POST `type`, `ids[]`; returns `posted` count and `no_mapping` ids). Permission: same check as `update_secondary_currency_setting`.
- [ ] T005 Controller: in `setting()` `mapping_setup` branch (~872) set `$data['pending_conversions']` = types with count > 0.
- [ ] T006 View: at the top show a panel "Not posted to accounting yet" with a row per type (name, count, View button). View opens a modal list with a checkbox per row, Select all, `Convert selected`, `Convert all pending` (asks confirm), a red "No mapping" tag for rows that stayed unposted, and a `Set accounts` link per row opening `admin_url('accounting/transaction?group=<group>&tab=<tab>')` in a new tab.
- [ ] T007 [P] Add the new texts to the english and arabic language files (panel title, buttons, "No mapping", result message).
- [ ] T008 Test: 3 expenses + 2 invoices created with conversion off; set expense accounts only. Tick 3 expenses + 1 invoice, Convert selected: expenses post, invoice shows "No mapping". Convert it by hand from Transactions. Convert selected again: no duplicates.

## B. Other transaction types (one line each, add an entry to `get_conversion_registry()` like T001)
- [ ] T009 [P] Credit note, credit note apply, credit note refund.
- [ ] T010 [P] Purchase order, purchase invoice, purchase payment, purchase refund, purchase order return.
- [ ] T011 [P] Debit note, debit note apply, debit note refund.
- [ ] T012 [P] Stock import, stock export, loss adjustment, opening stock.
- [ ] T013 [P] Payslip, manufacturing order, pay bill, check.
- [ ] T014 [P] Omni sales refund and return order.
- [ ] T015 [P] Fixed equipment: asset, license, component, consumable, maintenance, depreciation.

## C. Stop bad setup
- [ ] T016 Model: add `validate_expense_mapping_accounts($payment_account, $deposit_to)`: return an error key if either is empty or not in `acc_accounts`, else ''.
- [ ] T017 Model `update_automatic_conversion` (~1891) and the expense category / payment-mode mapping add/update methods (~13164, ~13241): refuse to save when T016 returns an error; controller shows a red alert.
- [ ] T018 Model default routine (~11579-11580): replace the fixed ids 13 and 80 with a lookup by account key; leave empty if not found.
- [ ] T019 Controller + view: on Mapping Setup show a red warning above the Expense block when expense conversion is on and a default account is empty or missing.
- [ ] T020 [P] View + category/payment-mode mapping views: relabel the expense selects "Expense account (debit)" and "Paid from account (credit)". Do not rename the field names.

## D. Show why an expense was skipped
- [ ] T021 Model `automatic_expense_conversion`: each early `return false` first stores a reason in a public property. `modules/accounting/accounting.php` function `acc_automatic_expense_conversion` shows it with `set_alert('warning', ...)`.

## E. Test
- [ ] T022 New expense after setup posts at once: debit expense account, credit paid-from account. Empty account in Mapping Setup is refused. Delete the default account: red warning shows.
