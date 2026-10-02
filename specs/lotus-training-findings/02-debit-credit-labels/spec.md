# 02 Report column names: Debit / Credit and Counter Account

## Problem
Report tables label their amount columns "Decrease" / "Increase" and the other-side account column "Split". Accountants expect Debit, Credit and a clearer name for the other side of the entry.

## Decisions (confirmed with the user)
- Apply to **all reports** that use these labels.
- "Split" becomes **Counter Account**.
- Increase/Decrease become Debit/Credit wherever a report uses them (today only Account History does; a search for other report views found none).
- The shared keys `increase`, `decrease`, `split` are NOT edited, because `views/setting/warehouse.php` uses increase/decrease. Reports switch to new keys.

## Trap to avoid
The existing keys `debit` and `credit` render "Deposit"/"Payment" when `acc_mapping_label_type == 'payment_deposit'` (`language/english/accounting_lang.php` 1274-1275). Reports must always say Debit/Credit, so use NEW keys.

## Reports in scope (views that print `_l('split')` today)
Account History, General Ledger, Transaction Detail by Account, Transaction List by Date, Profit and Loss Detail, Bank Reconciliation Detail (all under `modules/accounting/views/report/details/`). Also their PDF/print/Excel outputs.

## User stories
### US1 (P1): Account History
Headers read **Debit**, **Credit**, **Counter Account**. Make sure each header matches the numbers in its column (the current "Decrease" column may hold the credit value; verify before renaming).
### US2 (P1): The other five reports
The Split header reads **Counter Account** in the screen, PDF and Excel export, in English and Arabic.
### US3 (P2): Any other place
If the implementer finds another report view/export using increase, decrease or split, apply the same rename and list it in the test checklist.

## NEEDS CLARIFICATION
- Arabic wording: proposed "مدين" / "دائن" / "الحساب المقابل". Confirm.
