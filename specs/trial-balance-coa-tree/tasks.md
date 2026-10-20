# Trial Balance and Chart of Accounts Tree: Tasks

## Status (2026-10-20)
All tasks T001-T024 done and tested end to end on local full.localhost (main @ 7f40a617).
- T001/T002: accounting DB upgrade run for mr_gaa, shrk, shrk368 (and lotus_holding already 1.5.0) via `/<slug>/ps/cron/index?setup=1`. Note the tenant URL uses the slug with underscore: `/mr_gaa/ps/`.
- T006: COA v3 imported for mr_gaa: 4 created, 290 updated, 0 failed; history totals unchanged (118 rows, 263,185,981.94 Dr = Cr); roots 1,2,3,4. Account numbers turned on in mr_gaa settings.
- Tree: 1/2/3/4 at the top, banks 130102-130109 under 1301 (360,406.01), 2 = 99,013,226.91 = 1. List view in code order, uncoded accounts last.
- Trial balance: 8 columns, totals Dr = Cr 99,013,226.91; hide zero 294 -> 86 rows; collapse/expand OK. Layout widened to A4 landscape, plain numbers.
- T022: every code in the client file is in the export; extra in system: 1302501012, 220521, 400404. Balance sheet amounts match; only the 2025 P&L differs (closed into 214, net loss 2,999,424.86).
- Secondary currency (Lotus): test journal entry 1,000 USD -> 50,800 EGP showed in the 6 extra columns and in Excel columns I-N; entry deleted after the test.
- T023: PDF now landscape and all 14 columns fit; Arabic text fixed for all accounting report PDFs (TCPDF bidi shaping + DejaVu Sans, commit 7f40a617).
- Server package: `D:/Web Projects/ABICERP-main/deploy/` (zip + DEPLOY_STEPS.md).

Requirements: `spec.md`. Design details: `plan.md` (each task names its section). One task = one small change. Test after each.

## Shared rules for every task
- Do only what the task says, in the file it names. Do not refactor nearby code.
- Read the function named in the task before editing it. Line numbers (`~1353`) are approximate, so search for the function name.
- Copy the style of the code around it: indentation, `db_prefix()`, `_l()`, `$this->db` query builder.
- Every user-visible text is a language key in BOTH `modules/accounting/language/english/accounting_lang.php` and `modules/accounting/language/arabic/accounting_lang.php`.
- Model = `modules/accounting/models/Accounting_model.php`. Controller = `modules/accounting/controllers/Accounting.php`.
- After each PHP edit run `php -l <file>` (PHP: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`).
- Local site: `http://full.localhost/mr-gaa/ps/admin/...`. Local MySQL is on port **3307** (`mysql -uroot -h127.0.0.1 -P3307 abicerp_full`), table prefix `mr_gaa_`. Port 3306 is a different server (MariaDB). Do not use it.
- Do not change the balance sheet, profit and loss, or any other report.

## Phase 1: Setup (unblock the chart of accounts)
- [x] T001 **(manual, admin)** Run the accounting DB upgrade for tenant `mr-gaa`: log in and open `http://full.localhost/mr-gaa/ps/admin/modules/upgrade_database/accounting`. Then run `SELECT installed_version FROM mr_gaa_tblmodules WHERE module_name='accounting';` and expect `1.5.0`. Run `SHOW COLUMNS FROM mr_gaa_tblacc_account_history LIKE 'sec_%';` and expect 3 rows. Open `admin/accounting/chart_of_accounts` and check that the tree loads. (plan §0)
- [x] T002 **(manual, admin)** Do the same as T001 for tenant `shrk` (also on 1.4.5), and on the live server for every tenant whose `<prefix>tblmodules.installed_version` for `accounting` is not `1.5.0`.
- [x] T003 [P] Add the 7 language keys from the table in plan "Language keys" to the end of `modules/accounting/language/english/accounting_lang.php` (English text) and `modules/accounting/language/arabic/accounting_lang.php` (Arabic text).
- [x] T004 In `modules/accounting/assets/js/chart_of_accounts/manage_js.php`:
  - add `loading: '<?php echo _l('acc_tree_loading'); ?>'` and `load_failed: '<?php echo _l('acc_tree_load_failed'); ?>'` to the `acc_tree_lang` object at the top;
  - in `load_accounts_tree()` (~455), change the first `$root.html(...)` to use `acc_tree_lang.loading`;
  - chain `.fail(function() { $root.html('<li class="text-danger">' + acc_tree_lang.load_failed + '</li>'); });` after the `$.post(...)` call.
  - Test: rename a column in a query temporarily (or test before T001) and check that the message appears instead of endless loading. Undo the test change.

## Phase 2: Foundational (data fix + correct roll-up)
- [x] T005 Create the Python script `docs/Companies_COA/make_national_coa_v3.py`. It reads `docs/Companies_COA/import_ready/National_Real_Estate_Investment_COA_import_v2.xlsx` with openpyxl and writes `docs/Companies_COA/import_ready/National_Real_Estate_Investment_COA_import_v3.xlsx`:
  - same header row;
  - first 4 data rows = the table in plan §1 (codes `1`, `2`, `3`, `4`);
  - then all v2 rows in their order, with: column C (code) written as a string; column E (`Sub Account of`) set to `1` for codes `11`,`12`,`13`,`14`, `2` for `21`,`22`, `3` for codes that start with `3` and have an empty column E, `4` for codes that start with `4` and have an empty column E; columns F and G (opening balance, balance as of) set to empty in **every** row;
  - print the number of rows written (expect 294 data rows) and the codes that are still roots (expect only `1`,`2`,`3`,`4`).
  - Run it with `python docs/Companies_COA/make_national_coa_v3.py`.
- [x] T006 **(manual)** Before importing, run `SELECT COUNT(*), SUM(debit), SUM(credit) FROM mr_gaa_tblacc_account_history;` and write the numbers down. Import `National_Real_Estate_Investment_COA_import_v3.xlsx` on `admin/accounting/accounts_import` for tenant `mr-gaa`. After the import, run the same query and expect the **same** numbers (no new opening balances). Run `SELECT number FROM mr_gaa_tblacc_accounts WHERE parent_account = 0 AND number <> '';` and expect only `1`,`2`,`3`,`4`.
- [x] T007 In the model, rewrite the body of `get_all_account_balances()` (~37842) as described in plan §3 "Roll-up": roll up raw `debit`, `credit`, `sec_debit`, `sec_credit` sums from children to parents, then sign each account's total by **its own** `account_type_id` using the existing `$credit_nature` list. Keep the function name, the cash-method filter, the cycle guard, and the return shape `[id => ['balance' => float, 'sec' => float]]`.
  - Test: in the tree, `1301` balance = sum of its children's balances, and `2` shows a credit balance.

## Phase 3: US1 Level-1 accounts and correct totals in the tree (P1)
- [x] T008 [US1] In the controller `accounts_tree()` (~1506-1568), right after the `usort($roots_all, ...)` call, add the `$code_roots` check from plan §3 "Top level". If it is true, set `$tree = $roots_all;`, skip the class grouping block, and go straight to the existing `echo json_encode(...)` line. Wrap the existing grouping code in `else { ... }` (or use an early `echo ... die();`). Do not change the grouping code itself.
  - Test (National, after T006): the tree starts with `1 الاصول`, `2 الخصوم`, `3 …`, `4 …`, with no "Assets / Liabilities" labels. Test (tenant `lotus-holding`): the tree still shows the class labels as before.
- [x] T009 [US1] In `modules/accounting/assets/js/chart_of_accounts/manage_js.php` `load_accounts_tree()`: when the first node of `tree` has no `is_group`, call `set_tree_node_collapsed` on `$root.find('.tree-node').not($root.children('.tree-node'))` instead of all nodes, so the four level-1 accounts stay open on load. Leave the current behaviour for the grouped tree.

## Phase 4: US2 Bank accounts in the tree (P1)
- [x] T010 [P] [US2] In the controller `accounts_tree()` (~1353), delete the line `$this->db->where('account_detail_type_id !=', 14);`.
- [x] T011 [P] [US2] In the controller `accounts_table()` (~1215-1223), delete only the `else{ array_push($where, 'AND account_detail_type_id != 14'); }` part. Keep the `if ($this->input->post('menu_type'))` branch.
  - Test: `130102`…`130109` show under `1301` in tree view and list view, and the Bank accounts page still lists only banks.

## Phase 5: US3 New trial balance layout (P1)
- [x] T012 [US3] In the model, add `public function get_data_trial_balance_tree($data_filter)` right after `get_data_trial_balance()` (~10487). Follow plan §5 "Model" steps 1-13 exactly, plus the "Secondary currency (D9)" part right after them. Use `$this->db->query()` with the SQL texts from the plan and `db_prefix()` for table names. Do not use the `$amount_sql` / currency code of the old function.
- [x] T013 [US3] Check T012 by hand: add a temporary `var_dump` call from any admin page, or a temporary controller method, for tenant `mr-gaa` with from = to = `2026-01-01`. Expect all `mov_dr`/`mov_cr` = 0, `total.open_dr == total.open_cr`, row `130105` `open_dr` = 323726.64, row `130102` `open_dr` = 4170, and row `1301` `open_dr` = the sum of its children's `open_dr` minus their `open_cr`. Remove the temporary code.
- [x] T014 [US3] In the controller `view_report()`, replace the `case 'trial_balance':` block (~5619) with the block in plan §5 "Controller". Before editing, read the code between the `switch` and `$this->load->view('report/details/'.$data_filter['type'], $data);` (~5680). If `$data_filter['type']` is used again in between, use a `$view_type` variable instead and use it in the `load->view` line.
- [x] T015 [US3] Create `modules/accounting/views/report/details/trial_balance_tree.php` as described in plan §5 "View" (8 columns, two header rows, one row per `$data_report['rows']`, total row). Use `$data_report`, `$currency` and `$report_currency_info` as `details/trial_balance.php` does. Include the secondary-currency columns when `$data_report['secondary']` is set (plan §5 View, last bullet). Keep `<table class="tree">` and the `data-node-id` / `data-node-pid` attributes so the existing expand/collapse buttons keep working.
- [x] T016 [US3] In `modules/accounting/views/report/includes/trial_balance.php`, add the `hide_zero` checkbox to the filter form before the Filter button (Bootstrap `checkbox` markup, `name="hide_zero"`, `value="1"`, label `_l('acc_tb_hide_zero')`, unchecked).
  - Test: open `admin/accounting/rp_trial_balance`, choose 2026-01-01 → today, press Filter. Check 8 columns, code order, parents bold with rolled-up totals, and a total row where Dr = Cr in each pair. Check that the hide-zero checkbox hides zero rows. Check that expand/collapse works.

## Phase 6: US4 Code order everywhere (P2)
- [x] T017 [P] [US4] In the controller `accounts_tree()`, in `$sort_by_code` (~1466) and in the `usort($roots_all, ...)` callback (~1534), replace `strnatcmp($na, $nb)` / `strnatcmp($a['number'], $b['number'])` with `strcmp(...)` for the **number** comparison only. Keep `strnatcmp` for the name tie-break.
- [x] T018 [P] [US4] In the model `get_account_data_tables()` (~16846-16858): if `$CI->input->post('order')` is empty, or the chosen `$columnName` is `id` or `1`, call `$this->db->order_by('number', 'ASC');` instead of ordering by that column.
  - Test: list view and tree view show `1, 11, 1101, 11011, …, 12, …, 13, 1301, 130101 …` in the same order as `docs/ميزان مراجعة الشركة الوطنية 31-12-2025.XLS`.

## Phase 7: US5 Excel export (P2)
- [x] T019 [US5] In `modules/accounting/assets/js/report/main.js`, add the function `acc_trial_balance_export()` from plan §6 "JS" at the end of the file.
- [x] T020 [US5] In `modules/accounting/views/report/includes/trial_balance.php`, change the "export to excel" menu link from `onclick="printExcel(); return false;"` to `onclick="acc_trial_balance_export(); return false;"`. Do not change `printExcel()` itself (other reports use it).
- [x] T021 [US5] In the controller, add `public function trial_balance_export()` right after `cashflow_monthly_export()` (~4500), as described in plan §6 "Controller". Include columns I-N when `$report['secondary']` is set. Copy the permission check, the `delete_error_file_day_before`, the XLSXWriter `require_once`, the styles and the write/download lines from `cashflow_monthly_export()`.
  - Test: press Export to Excel on the trial balance page. Open the file in Excel and check: RTL sheet, Arabic names readable in column B, codes in column A (long codes like `1302501001` not in scientific format), amounts are numbers (SUM works), total row at the end. Test again on tenant `lotus-holding` (secondary currency on): columns I-N show the secondary amounts.

## Phase 8: Polish
- [x] T022 Compare with the client file: for tenant `mr-gaa`, list every code in `docs/ميزان مراجعة الشركة الوطنية 31-12-2025.XLS` column A that is not in the exported trial balance column A (a short Python script in the scratchpad is fine). Expect none, except accounts that are inactive in the system. Report any difference.
- [x] T023 Check the PDF export (`printDiv()`) of the new trial balance: all 8 columns fit on the page. If they do not, add `@media print` CSS in `modules/accounting/assets/css/report.css` for `.tree` with a smaller font (e.g. 10px). Change nothing else.
- [x] T024 Add a `## Status (<date>)` section under the title of this file: which tasks are done, what was tested, and anything left. Mark the done tasks `[x]`.
