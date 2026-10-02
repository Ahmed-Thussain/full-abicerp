# 02 Tasks

Shared rules: `../README.md`. One task = one small change. Test after each.

- [ ] T001 [P] In `modules/accounting/language/english/accounting_lang.php` next to `$lang['split']` add: `acc_report_debit` = 'Debit', `acc_report_credit` = 'Credit', `acc_report_counter_account` = 'Counter Account'.
- [ ] T002 [P] In `modules/accounting/language/arabic/accounting_lang.php` add the same three keys: 'مدين', 'دائن', 'الحساب المقابل'.
- [ ] T003 In `modules/accounting/models/Accounting_model.php`, function `get_data_account_history` (~11373), add two keys to each `$data_report[]` row: `'debit' => $v['debit'], 'credit' => $v['credit']`. Change nothing else there.
- [ ] T004 In `modules/accounting/views/report/details/account_history.php` line 51 replace `_l('split')` with `_l('acc_report_counter_account')`.
- [ ] T005 In the same file, only in the last `else` branch (lines 59-62, the one that prints Decrease/Increase): make the two headers `_l('acc_report_debit')` then `_l('acc_report_credit')`, and in the data rows (lines 86-91) print `$val['debit']` in the first amount cell and `$val['credit']` in the second when `$data_report['account_type']` is not 3, 7 or 1. Leave the Payment/Deposit and Charge/Payment branches as they are.
- [ ] T006 [P] In `modules/accounting/views/report/details/general_ledger.php` line 55, replace `_l('split')` with `_l('acc_report_counter_account')`.
- [ ] T007 [P] Same replacement in `transaction_detail_by_account.php` line 50.
- [ ] T008 [P] Same replacement in `transaction_list_by_date.php` line 48.
- [ ] T009 [P] Same replacement in `profit_and_loss_detail.php` line 45.
- [ ] T010 [P] Same replacement in `bank_reconciliation_detail.php` line 57.
- [ ] T011 Search `modules/accounting/controllers/Accounting.php` and `Accounting_model.php` for `_l('split')`, `_l('increase')`, `_l('decrease')` in export/PDF headers; apply the same replacements. If none, do nothing.
- [ ] T012 Test: open the six reports, check the headers; on Account History pick a bank-type account and a liability account and check Debit and Credit match the ledger. Export PDF and Excel once.
