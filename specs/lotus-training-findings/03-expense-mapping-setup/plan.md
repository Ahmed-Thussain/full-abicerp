# 03 Plan

## Files
- `modules/accounting/models/Accounting_model.php`: `automatic_*_conversion` methods (list via search `function automatic_`, ~30 of them, e.g. invoice ~11941, payment ~12179, expense ~12337), `count_*_not_convert_yet` (~12738 and others, kept as they are), default-option routine (~11560-11582), `update_automatic_conversion` (~1891), mapping add/update methods (`add_expense_category_mapping` ~13164, `update_expense_category_mapping` ~13241).
- `modules/accounting/controllers/Accounting.php`: `setting()` mapping_setup branch (~872), `convert()` (~3505, manual convert; read how it picks records), new `pending_conversions()` and `convert_pending()`.
- `modules/accounting/views/setting/general_mapping_setup.php`: pending table, warning, labels. Category/payment-mode mapping views under `views/setting/`.
- `modules/accounting/accounting.php`: hook `acc_automatic_expense_conversion` (skip reasons).
- Language files (english + arabic).

## Design: generic pending-conversion engine
One registry method `Accounting_model::get_conversion_registry()` returns, per type key, an array:
`['label' => lang key, 'table' => source table (no prefix), 'id_col' => 'id', 'rel_type' => value used in acc_account_history.rel_type, 'where' => extra SQL (for example 'status != 6' for invoices), 'convert' => 'automatic_x_conversion', 'enabled_option' => option that switches that conversion on]`.
Each entry is filled by reading the matching `automatic_*_conversion` method (it shows the source table, the `rel_type` it writes and its on/off option) and the matching `count_*_not_convert_yet` (it shows the extra filters).
- Each registry entry also holds `'group'` and `'tab'` (the Transactions screen tab where the type is listed, see `Accounting::transaction()` lines ~55-160) and the columns to show in the list (`date_col`, `ref_col`, `amount_col`, `currency_col`, all optional).
- `get_pending_rows($type, $limit, $offset)`: `SELECT id, <date_col>, <ref_col>, <amount_col> FROM <table> WHERE <where> AND id NOT IN (SELECT DISTINCT rel_id FROM acc_account_history WHERE rel_type = '<rel_type>') ORDER BY id LIMIT ...`. `get_pending_ids($type)` is the same with ids only.
- `convert_pending($type, $ids)`: for each id call the `convert` method, then report posted (history row now exists) and no-mapping (still none) ids. Selection is always explicit; there is no "all" call unless the user presses the type's "Convert all pending" (which passes the full id list).
- Manual set-accounts uses the existing `Accounting::get_data_convert($id, $type)` and `Accounting::convert()` from the Transactions screen. The Mapping Setup list only opens that dialog; it does not duplicate it. If the dialog is bound to the Transactions page JS, open the Transactions tab link (`admin_url('accounting/transaction?group=<group>&tab=<tab>')`) in a new tab instead.
- Registry is data, so adding a type later is one array entry.
- Because conversions are gated by their own option, only types with `get_option(enabled_option) == 1` are listed.
- Payment-type conversions depend on their invoice being posted first: convert invoices before payments (registry order = dependency order: invoices, credit notes, payments, then the rest).

Other design notes
- Do not change stored option names or debit/credit direction; only labels and validation for expenses.
- Manual convert on the transaction screen stays as is.

## Risks
- ~30 types: implement in groups (Phase 3 tasks) and test each group; a type that cannot be mapped cleanly is left out of the registry and listed in `test-checklist.md`.
- Some `automatic_*` methods take extra state (for example currency or date filters); if so, call them the same way `Accounting::convert()` does.
