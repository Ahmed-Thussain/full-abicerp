# Trial Balance and Chart of Accounts Tree: Plan

Requirements and decisions (D1-D9): `spec.md`. Tasks: `tasks.md`.
All paths are relative to `full-abicerp/`. Model = `modules/accounting/models/Accounting_model.php`, controller = `modules/accounting/controllers/Accounting.php`.

## 0. Unblock the chart of accounts (tenant upgrade + JS error)

**Data/admin step (no code):** log in to tenant `mr-gaa` as admin and open `http://full.localhost/mr-gaa/ps/admin/modules/upgrade_database/accounting` (route `admin/modules/upgrade_database/<module>` → `application/controllers/admin/Mods.php::upgrade_database()`). The same button is on Setup → Modules when the module says "database upgrade required". This runs the missing accounting migrations for the tenant (adds `sec_currency`, `sec_debit`, `sec_credit`, `doc_*` to `mr_gaa_tblacc_account_history`, etc.).

Check with SQL on the local MySQL (port **3307**, not 3306):
```sql
SELECT installed_version FROM mr_gaa_tblmodules WHERE module_name = 'accounting';   -- 1.5.0
SHOW COLUMNS FROM mr_gaa_tblacc_account_history LIKE 'sec_%';                       -- 3 rows
```
Do the same on the live server for every tenant that is not 1.5.0. `shrk_` is also on 1.4.5.

**Code:** in `load_accounts_tree()` (`modules/accounting/assets/js/chart_of_accounts/manage_js.php` ~455):
- Before the request, show a loading text (`acc_tree_loading`) instead of `no_records`.
- Chain `.fail(function(xhr){ ... })` on the `$.post`. It shows `acc_tree_load_failed` in `#accounts-tree-root` as `<li class="text-danger">`.

## 1. COA data fix: level-1 accounts (National)

Create `docs/Companies_COA/import_ready/National_Real_Estate_Investment_COA_import_v3.xlsx` from v2. It has the same 7 columns: `Type, Sub Type, Account Code, Account Name, Sub Account of(account), Opening balance, Balance as of`.
- Put 4 new rows at the top, before any child (the import resolves parents in file order):

| Type | Sub Type | Code | Name | Sub Account of |
|------|----------|------|------|----------------|
| 2 | 10 | 1 | الاصول | (empty) |
| 8 | 54 | 2 | الخصوم | (empty) |
| 14 | 123 | 3 | المصروفات العمومية | (empty) |
| 11 | 86 | 4 | الايرادات | (empty) |

- Set `Sub Account of` = `1` for codes 11, 12, 13, 14; `2` for 21, 22; `3` for every root code starting with `3` (3001…3060); `4` for every root code starting with `4` (4001…4004).
- Make the code column text everywhere (v2 has the number `14` as an integer).
- **Empty the `Opening balance` and `Balance as of` columns in all rows.** The accounts already have their opening balances, and an import must not post them again.

The import (`Accounting::import_file_xlsx_account()` → `Accounting_model::upsert_account_from_import()` ~23373) matches existing accounts by code and updates `account_type_id`, `account_detail_type_id`, `name`, `parent_account`. New codes are inserted. The group-digit rule is not applied to imports, so `3` = expenses is allowed.

Write the file with a Python script (openpyxl). Script: `docs/Companies_COA/make_national_coa_v3.py`.

## 2. Banks in the tree and list (D3)

- `accounts_tree()` ~1353: delete `$this->db->where('account_detail_type_id !=', 14);`.
- `accounts_table()` ~1221-1223: delete the `else { array_push($where, 'AND account_detail_type_id != 14'); }` branch. Keep the `bank_accounts` branch, which the Bank accounts page uses.

## 3. Tree: roll-up and top level (D4, D5)

### Roll-up (D5): `get_all_account_balances()` ~37842
Today `own[id]['balance']` is signed by the account's own nature, and the parent adds children's signed balances. Change it to:
1. `own_raw[id] = ['d' => debit, 'c' => credit, 'sd' => sec_debit, 'sc' => sec_credit]` (raw sums, no sign).
2. Roll up raw sums: `tot[id] = own_raw[id] + sum(tot[child])` for each of d, c, sd, sc (keep the same cycle guard).
3. At the end, for each id: `balance = credit_side ? (c - d) : (d - c)`, and the same for `sec` with sd/sc, using **this** account's `account_type_id` and the existing `$credit_nature` list.
4. Return the same shape as today: `[id => ['balance' => ..., 'sec' => ...]]`. Callers do not change.

### Top level (D4): `accounts_tree()` ~1506-1568
After `$roots_all` is sorted:
- `$code_roots = true` if **every** root that has a number has a number of exactly 1 character (`strlen(trim($root['number'])) === 1`), and at least one root has a number.
- If `$code_roots` is true: `$tree = $roots_all;`, skip the class grouping, and continue to `echo json_encode(...)`.
- Else: keep the class grouping exactly as it is.

The JS already renders any node with `children`. Nodes without `is_group` render as accounts.

## 4. Code order (US4)

- Tree: change both sort callbacks in `accounts_tree()` (~1466 `$sort_by_code` and ~1534 the `usort($roots_all, ...)`) from `strnatcmp($na, $nb)` to `strcmp($na, $nb)` for the number comparison. Keep the name tie-break and "no code last" rules.
  - Why: codes are prefixes, so string order equals the client's order. Natural order puts `13027` before `130260001`.
- List view: `get_account_data_tables()` ~16846-16858. If the DataTable sends no `order`, or the order column is `id`, order the top-level query by `number ASC`. `get_recursive_account()` already orders children by `number,name`.
- Trial balance: the new builder sorts by code itself (section 5).

## 5. New trial balance (US3)

### Model: `get_data_trial_balance_tree($data_filter)` (new, put it right after `get_data_trial_balance()` ~10487)

Inputs from `$data_filter`: `from_date`, `to_date` (format with `to_sql_date()` as the old function does; defaults `date('Y-01-01')` and `date('Y-m-d')`), `accounting_method` (default `get_option('acc_accounting_method')`), `hide_zero` (`'1'` or empty).

Steps:
1. Accounts: `SELECT id, number, name, key_name, parent_account, account_type_id FROM acc_accounts WHERE active = 1`. Label = `name` if not empty, else `_l(key_name)`. `number` = `trim((string) number)`.
2. Opening-line condition (D1), used as SQL text:
   `$is_opening = '(date < "'.$from_date.'" OR (date = "'.$from_date.'" AND rel_type = "deposit" AND rel_id = 0))';`
3. Cash filter (only when method is `cash`): `((rel_type = "invoice" AND paid = 1) OR rel_type != "invoice")`. This is the same text as the old function.
4. Query O (opening): `SELECT account, SUM(debit) d, SUM(credit) c FROM acc_account_history WHERE <is_opening> [AND cash filter] GROUP BY account`.
5. Query M (movement): `SELECT account, SUM(debit) d, SUM(credit) c FROM acc_account_history WHERE date >= from AND date <= to AND NOT <is_opening> [AND cash filter] GROUP BY account`.
6. `own[id] = ['od'=>, 'oc'=>, 'md'=>, 'mc'=>]` (0 when missing, cast to float).
7. Parent map with the same cycle-safe rule as `accounts_tree()` ~1431-1456: a parent counts only if it exists, is not the account itself, and the parent chain does not loop back to the account. Otherwise the account is a root.
8. Sort children and roots: accounts with a number first, by `strcmp(number)`, then by `strcmp(label)`. Accounts without a number go last (D2).
9. Roll-up: `tot[id] = own[id] + sum(tot[child])` for od, oc, md, mc (recursive, with a visited set).
10. Rows: depth-first from the sorted roots. For each account push:
    ```
    ['id', 'number', 'name', 'level' (0 = root), 'has_children' (bool),
     'open_dr', 'open_cr', 'mov_dr', 'mov_cr', 'close_dr', 'close_cr']
    ```
    with `net_o = od - oc`, `open_dr = net_o > 0 ? net_o : 0`, `open_cr = net_o < 0 ? -net_o : 0`;
    `mov_dr = md`, `mov_cr = mc`;
    `net_c = (od + md) - (oc + mc)`, `close_dr`/`close_cr` the same way as opening.
    Round all 6 with `round(x, 2)`.
11. Zero rows: a row is zero when all 6 values are 0. Skip it if `hide_zero` is `'1'`, **or** if the account has no number (D2). When a row is skipped, its whole subtree is also zero, so skip the subtree.
12. Totals: sum the 6 values of the **root** rows only (level 0, not skipped).
13. Return `['rows' => $rows, 'total' => ['open_dr'=>..,'open_cr'=>..,'mov_dr'=>..,'mov_cr'=>..,'close_dr'=>..,'close_cr'=>..], 'from_date' => $from_date, 'to_date' => $to_date, 'secondary' => $secondary]`.

**Secondary currency (D9).**
- `$secondary = null`. When `get_option('acc_enable_secondary_currency') == 1`, `get_option('acc_secondary_currency_id') != ''`, and `$this->db->field_exists('sec_debit', db_prefix().'acc_account_history')`, load the currency with `$this->currencies_model->get(id)`. Then `$secondary = ['id' => .., 'name' => $cur->name]`.
- When `$secondary` is set, queries O and M also select `SUM(sec_debit) sd, SUM(sec_credit) sc`.
- Every step above is repeated for `sod, soc, smd, smc`. Each row and the totals get 6 more keys: `s_open_dr, s_open_cr, s_mov_dr, s_mov_cr, s_close_dr, s_close_cr`, with the same netting and rounding.
- A row counts as zero (step 11) only when all 12 values are 0.
- When `$secondary` is null, these keys are not added, and the view/export show only the 8 base columns.

### Controller: `view_report()` case `'trial_balance'` ~5619
```php
case 'trial_balance':
    if (empty($data_filter['report_currency'])) {
        $data['data_report'] = $this->accounting_model->get_data_trial_balance_tree($data_filter);
        $data_filter['type'] = 'trial_balance_tree';
    } else {
        $data['data_report'] = $this->accounting_model->get_data_trial_balance($data_filter);
    }
    break;
```
The view is loaded later with `'report/details/'.$data_filter['type']` (~5680), so the new view name is picked from `type`. Check that `$data_filter['type']` is not used again between the switch and that line. If it is, use a local `$view` variable instead.

### View: `modules/accounting/views/report/details/trial_balance_tree.php` (new)
- Same wrapper as `details/trial_balance.php` (`#accordion`, `.card`, `report_currency_bar`), `<table class="tree">` so expand/collapse keeps working.
- Title rows: company name, `_l('trial_balance')`, `_d(from) - _d(to)`, each `<td colspan="8">`.
- Header row 1 (`tr_header`): `Code` (`acc_tb_code`), `Account` (`acc_account`), `Opening balance` colspan 2 (`acc_tb_opening`), `Movement` colspan 2 (`acc_tb_movement`), `Closing balance` colspan 2 (`acc_tb_closing`).
- Header row 2 (`tr_header`): 2 empty cells, then `debit`, `credit` three times (`_l('debit')`, `_l('credit')`).
- One `<tr>` per row: `data-node-id = index + 1`. `data-node-pid` = the node id of the parent row (keep a `$node_id_of[account_id]` map while looping). Root rows have no `data-node-pid`. Parent rows (`has_children`) get class `tr_parent text-bold`.
- Code cell: the number. Name cell: `str_repeat('&nbsp;', level * 4)` + `<a href="admin_url('accounting/user_register_view/'.id.'?from_date='.from.'&to_date='.to)">name</a>`.
- 6 amount cells, `class="total_amount"`, `app_format_money($v, $currency->name)`, empty when `$v == 0`.
- Last row `tr_total`: `_l('total')` across 2 cells, then the 6 totals.
- **If `$data_report['secondary']` is set:** header row 1 adds a cell with colspan 6, text `$data_report['secondary']['name']`. Header row 2 adds the 6 sub-headers (`acc_tb_opening`/`acc_tb_movement`/`acc_tb_closing` × debit/credit, shortened to `debit`/`credit` under three colspan-2 labels if that reads better). Every row and the total row add the 6 `s_*` values, formatted with `app_format_money($v, $data_report['secondary']['name'])`. All title `colspan`s become 14.
- Escape names with `html_escape()`.

### Page: `modules/accounting/views/report/includes/trial_balance.php`
- Add a checkbox `hide_zero` (value `1`, label `acc_tb_hide_zero`) inside the filter form, before the Filter button.
- Change the Excel menu item to `onclick="acc_trial_balance_export(); return false;"`.

## 6. Excel export (US5, D8)

### JS: `modules/accounting/assets/js/report/main.js` (add at the end)
```js
function acc_trial_balance_export() {
  "use strict";
  var params = {
    from_date: $('input[name="from_date"]').val(),
    to_date: $('input[name="to_date"]').val(),
    accounting_method: $('select[name="accounting_method"]').val(),
    hide_zero: $('input[name="hide_zero"]').is(':checked') ? 1 : ''
  };
  window.location.href = admin_url + 'accounting/trial_balance_export?' + $.param(params);
}
```

### Controller: `public function trial_balance_export()` (new, put it right after `cashflow_monthly_export()` ~4500)
Copy the structure of `cashflow_monthly_export()`:
- Permission `accounting_report` view; `delete_error_file_day_before(1, ACCOUTING_EXPORT_XLSX)`; require XLSXWriter.
- `$report = $this->accounting_model->get_data_trial_balance_tree($this->input->get());`
- `$writer = new XLSXWriter(); $writer->setRightToLeft(true);`
- `$writer->writeSheetHeader('Sheet1', ['A'=>'string','B'=>'string','C'=>'price','D'=>'price','E'=>'price','F'=>'price','G'=>'price','H'=>'price'], ['suppress_row' => true, 'widths' => [14, 45, 16, 16, 16, 16, 16, 16]]);`. Column A is `string` so long codes stay text.
- Rows: company name; `_l('trial_balance')`; `_d(from).' - '._d(to)`; empty; header row 1 (`acc_tb_code`, `acc_account`, `acc_tb_opening`, '', `acc_tb_movement`, '', `acc_tb_closing`, ''); header row 2 ('', '', debit, credit, debit, credit, debit, credit) with the bold style `$style3` of `cashflow_monthly_export()`.
- One row per `$report['rows']`: `[number, str_repeat('  ', level).name, open_dr, open_cr, mov_dr, mov_cr, close_dr, close_cr]` as numbers (floats, not formatted). Parent rows use a bold style.
- Total row with `$style3`.
- **If `$report['secondary']` is set:** add columns I-N (type `price`, width 16) with the 6 `s_*` values. Header row 1 adds the secondary currency name in column I. Header row 2 adds the debit/credit pairs. The total row adds the 6 secondary totals.
- File name `trial_balance_<time()>.xlsx`, written and sent the same way as `cashflow_monthly_export()`.

## Language keys
Add to both `modules/accounting/language/english/accounting_lang.php` and `modules/accounting/language/arabic/accounting_lang.php` (end of file):

| Key | English | Arabic |
|-----|---------|--------|
| `acc_tb_code` | Code | الرمز |
| `acc_tb_opening` | Opening balance | الرصيد الافتتاحي |
| `acc_tb_movement` | Movement | الحركة |
| `acc_tb_closing` | Closing balance | الرصيد الختامي |
| `acc_tb_hide_zero` | Hide zero rows | إخفاء الحسابات الصفرية |
| `acc_tree_loading` | Loading… | جاري التحميل… |
| `acc_tree_load_failed` | The chart of accounts could not be loaded. Ask the administrator to check the module database upgrade. | تعذر تحميل دليل الحسابات. يرجى مراجعة مسؤول النظام للتأكد من تحديث قاعدة بيانات الموديول. |

## Test data and expected results (National, after steps 0-1)
- Tree: roots `1`, `2`, `3`, `4` only, in this order. `1301` has 9 children (`130101`…`130109`).
- Trial balance 2026-01-01 → 2026-01-01: movement columns all 0; opening = closing; opening Dr total = opening Cr total.
- Opening of `130105` = 323,726.64 Dr; `130102` = 4,170.00 Dr; `1301` = sum of its 9 children.
- Trial balance 2026-01-01 → today: after the journal import, movement Dr total = movement Cr total = the sum of the imported journal (18,321,394.41 for the 2026-01-01 → 2026-10-13 file, if it was posted).
