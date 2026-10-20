# Trial Balance and Chart of Accounts Tree (National company)

## Problem
The client (National company, tenant `mr-gaa`, table prefix `mr_gaa_`) compared our system with their old program and sent this list:

1. The trial balance must show amounts like their file `docs/ميزان مراجعة الشركة الوطنية 31-12-2025.XLS`, and every main and sub account must appear at its level in the tree.
2. The bank accounts 130102 to 130109 are not shown in the tree, but the parent 1301 shows a balance that includes them.
3. Total liabilities (code 2) is not shown and has no balance.
4. Accounts in the tree and in the trial balance must be ordered by account code.
5. The trial balance Excel export shows only account codes. Names and amounts are missing.

While checking, we found one more problem that blocks everything else:

0. On `full.localhost`, the chart of accounts page loads forever after the COA import.

## What we found (causes)

| # | Cause | Where |
|---|-------|-------|
| 0 | `POST admin/accounting/accounts_tree` returns HTTP 500. The tenant `mr_gaa_` still has accounting **1.4.5** in `mr_gaa_tblmodules` (the code is 1.5.0), so migration 148 never ran and `mr_gaa_tblacc_account_history` has no `sec_currency`, `sec_debit`, `sec_credit` columns. `get_all_account_balances()` selects `SUM(sec_credit)` and the query fails. The page JS has no error handler, so it waits forever. | `modules/accounting/controllers/Accounting.php` `accounts_tree()` ~1339; `modules/accounting/models/Accounting_model.php` `get_all_account_balances()` ~37842; `modules/accounting/assets/js/chart_of_accounts/manage_js.php` `load_accounts_tree()` ~455 |
| 1 | The trial balance has only one Debit/Credit pair (net movement for the period). It has no opening balance, no separate debit and credit movement, and no closing balance. Rows are grouped by account type, not by the code tree. A parent row shows only its own amount, and the sum of its children is in a separate "Total for" row. There is no Code column. | `Accounting_model::get_data_trial_balance()` ~10306, `Accounting_model::get_html_trial_balance()` ~18334, `modules/accounting/views/report/details/trial_balance.php` |
| 2 | The 8 bank accounts were imported with sub type `14` (Bank). The tree and the list both skip sub type 14 (`account_detail_type_id != 14`) and show those accounts only on the Bank accounts page. The parent balance still adds them. | `Accounting.php` `accounts_tree()` ~1353 and `accounts_table()` ~1221 |
| 3 | **Data:** the National COA has no level-1 accounts `1`, `2`, `3`, `4`. The client trial balance has 291 accounts. `docs/Companies_COA/import_ready/National_Real_Estate_Investment_COA_import_v2.xlsx` has the same 291 minus these 4. So `21` and `22` are roots with no common parent, and so are `3001`…`3060` and `4001`…`4004`. **Code:** the tree groups roots under fixed labels (Assets / Liabilities / Equity / Income / Expenses) by account type, not by code. The balance roll-up adds each child's balance with the child's own sign, which is wrong when a parent and a child have different natures. | COA data; `accounts_tree()` ~1506-1568; `get_all_account_balances()` |
| 4 | The tree already sorts siblings by code (`strnatcmp`), but under fixed type labels (see 3). The trial balance sorts by account type, highest first (`get_accounts()` orders by `account_type_id desc`). | `accounts_tree()` ~1466; `get_accounts()` ~2018 |
| 5 | The export builds a CSV in the browser from the HTML table. (a) The plugin has an option `utf8BOM: true` but never writes the BOM, so Excel reads the Arabic as broken text and only the digits are readable. (b) The plugin takes the column count from the first row (5 cells: title rows) but data rows have 3 cells, so data rows get no line break. (c) Amounts are formatted text (`1,234.00 EGP`), not numbers. | `modules/accounting/assets/js/report/main.js` `printExcel()` ~121; `modules/accounting/assets/js/report/tableHTMLExport.js` |

Other facts used by this spec:
- Opening balances from the COA import are stored in `acc_account_history` as `rel_type = 'deposit'` and `rel_id = 0`, dated `balance_as_of` (2026-01-01 for National). The other side goes to one uncoded offset account (Opening Balance Equity) that nets to zero.
- Tenant options: `acc_enable_account_numbers = 0`, `acc_show_account_numbers = 0`, `acc_hide_zero_value_rows = 0`, `acc_accounting_method = accrual`.
- `acc_accounts.number` is `VARCHAR`. Codes are hierarchical prefixes (1 → 13 → 1301 → 130105), so plain string order of the code equals tree order. This is the order of the client file.

## The client's trial balance format (target)
One sheet, title row `ميزان المراجعة الشهري اعتباراً من شهر 1 عام 2025 لغاية شهر 12 عام 2025`, then:

| Col | Header | Meaning |
|-----|--------|---------|
| A | الرمز | Account code |
| B | الاسم | Account name |
| C | رصيد مدين (opening) | Opening balance, debit side |
| D | رصيد دائن (opening) | Opening balance, credit side |
| E | الحركة المدينة | Total debit movement in the period (gross) |
| F | الحركة الدائنة | Total credit movement in the period (gross) |
| G | رصيد مدين (closing) | Closing balance, debit side |
| H | رصيد دائن (closing) | Closing balance, credit side |

- Every account appears, at every level, in code order, including parents and accounts with zero amounts.
- A parent row shows the sum of everything under it, including itself.
- Opening and closing are netted to one side: if debit > credit the amount goes in the debit column, else in the credit column. Movement is not netted.
- Last row `المجموع` = sum of the root (level-1) rows. Debit total = credit total in each column pair.
- Example check (closing): `1301` = 360,406.01 Dr; `2` = 102,012,651.77 Cr; grand total closing = 134,988,147.97 on both sides.

## Decisions (defaults; confirm with the user before implementing)
- **D1 Opening balance:** opening = all lines dated before `from_date`, **plus** opening-balance lines (`rel_type = 'deposit'` and `rel_id = 0`) dated exactly on `from_date`. Without the second part, a report from 2026-01-01 would show the COA opening balances as movement.
- **D2 Uncoded system accounts** (no `number`, e.g. Opening Balance Equity): show them at the end with an empty code, only if any of their 6 amounts is not zero.
- **D3 Banks in the tree:** sub type 14 accounts show in the main tree and list like any other account. The Bank accounts page stays as it is.
- **D4 Tree top level:** when the company has accounts at level 1 (codes `1`, `2`, …), the tree shows the code tree only, without the fixed Assets/Liabilities/… labels. The fixed labels are kept only for companies whose roots are not a single digit. This keeps Lotus companies unchanged.
- **D5 Roll-up:** a parent's balance = (sum of raw debit − sum of raw credit) over itself and all sub accounts, then shown in the parent's own nature. Children are never added with their own sign.
- **D6 Hide zero rows:** the trial balance ignores `acc_hide_zero_value_rows` and shows every account (the client asked for all accounts). A checkbox "Hide zero rows" on the report page, unchecked by default, can hide them.
- **D7 Code column:** the trial balance always has its own Code column, independent of `acc_show_account_numbers`.
- **D8 Export:** the Excel export is a real `.xlsx` built on the server with `XLSXWriter`, the same 8 columns, amounts as numbers. The PDF export keeps using the HTML table.
- **D9 Currency (decided with the user 2026-10-18):** the 8 base-currency columns are always shown. When a secondary currency is enabled in the accounting settings (`acc_enable_secondary_currency = 1` and `acc_secondary_currency_id` set), the report adds **6 more columns** after them: opening Dr/Cr, movement Dr/Cr, and closing Dr/Cr in the secondary currency. They are built from the stored `sec_debit` / `sec_credit` of each ledger line and use the same rules (netting, roll-up, totals). Lotus Holding uses this the most, but it works for every company. The screen and the Excel export both show it. If a *report currency* is chosen in the currency bar, the old report is shown as today.

## User stories

### US0 (P0, blocker): The chart of accounts opens
- Acceptance: after the tenant DB upgrade, `admin/accounting/chart_of_accounts` on tenant `mr-gaa` shows the tree within a few seconds, and `accounts_tree` returns HTTP 200.
- If `accounts_tree` fails for any reason, the page shows an error message instead of waiting forever.

### US1 (P1): Level-1 accounts and correct totals in the tree
- Acceptance: the tree for National starts with `1 الاصول`, `2 الخصوم`, `3 المصروفات العمومية`, `4 الايرادات` and has no Assets/Liabilities/… labels above them.
- `2` contains `21` and `22`, and shows a balance (credit). `3` contains `3001`…`3060`. `4` contains `4001`…`4004`.
- A parent balance equals the sum of all sub accounts at any depth (D5).

### US2 (P1): Bank accounts in the tree
- Acceptance: `130102`…`130109` appear under `1301` in the tree and in the list view, and the sum of the children under `1301` equals the balance of `1301`.

### US3 (P1): New trial balance layout
- Acceptance: the trial balance page shows the 8 columns of the client format, in code order, with every account at its level, parents with rolled-up totals, and a grand total row.
- With from = 2026-01-01 and to = 2026-01-01 for National, opening = the imported opening balances, movement = 0 (D1), closing = opening.
- Debit total = credit total for opening, movement and closing.
- Clicking an account name opens its register (`accounting/user_register_view/<id>`), as today.

### US4 (P2): Code order everywhere
- Acceptance: tree, list view and trial balance all show accounts in the same order as the client file (string order of the code at each level).

### US5 (P2): Excel export
- Acceptance: "Export to Excel" on the trial balance downloads an `.xlsx`. Opened in Excel, it shows the title, the date range, the 8 headers, then one row per account with code in column A, Arabic name in column B, and numbers (not text) in C-H, then the total row. The sheet is right-to-left.

## Out of scope
- Comparing against or importing the client's 2025 figures. The system starts at 2026-01-01.
- Changing the balance sheet, profit and loss, or any other report.
- Fixing the same CSV export bug on other reports (the shared `printExcel()` stays; only the trial balance gets the new export). A follow-up can reuse the new export pattern.
