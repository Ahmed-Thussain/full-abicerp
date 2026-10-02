# 02 Plan

## Files
- `modules/accounting/views/report/details/`: `account_history.php` (lines 51, 60, 61), `general_ledger.php` (55), `transaction_detail_by_account.php` (50), `transaction_list_by_date.php` (48), `profit_and_loss_detail.php` (45), `bank_reconciliation_detail.php` (57).
- Export/PDF header arrays: search `Accounting.php` and `Accounting_model.php` for `_l('split')`, `_l('increase')`, `_l('decrease')`.
- `modules/accounting/language/english/accounting_lang.php` and `arabic/accounting_lang.php`.

## New keys
`acc_report_debit` 'Debit' (ar 'مدين'), `acc_report_credit` 'Credit' (ar 'دائن'), `acc_report_counter_account` 'Counter Account' (ar 'الحساب المقابل').

## Account History: why the model changes too
`get_data_account_history` (~11357-11372) fills `decrease`/`increase` by account type: for asset-like accounts increase = debit and decrease = credit, for liability/equity types (1, 7, 8) increase = credit and decrease = debit. So "Decrease, Increase" is not always "Debit, Credit". The model therefore also returns the raw `debit` and `credit` of each line, and the view's generic branch (not the bank Payment/Deposit branch, not the Charge/Payment branch) prints Debit then Credit from those raw values.
