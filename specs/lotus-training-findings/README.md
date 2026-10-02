# Lotus Training Findings: Specs

Source: findings from the last Lotus training session. Each folder holds `spec.md`, `plan.md`, `tasks.md`.

| # | Folder | Point | Priority |
|---|--------|-------|----------|
| 1 | `01-coa-import-opening-balance` | Chart of accounts import: opening balance per account, upsert instead of duplicate | P1 |
| 2 | `02-debit-credit-labels` | All reports: Increase/Decrease -> Debit/Credit, Split -> Counter Account | P3 (quick win) |
| 3 | `03-expense-mapping-setup` | Expenses added before Mapping Setup are never posted: add "Convert now" plus safeguards | P1 |
| 4 | `04-secondary-currency-reports` | Currency dropdown on every report, converted at each line's date rate, red "add rate" warning | P2 |

Point 5 (what the Monthly Cash Flow report reads) is a question, answered in `05-cashflow-monthly-answer.md`. No build work.

Suggested order: 2 (small), 1, 3, 4. Point 4 is the largest.

## Shared rules for every task
- Do only what the task says, in the file it names.
- Read the function named in the task before editing it. Copy the style of the code around it.
- New text goes in `modules/accounting/language/english/accounting_lang.php` and `language/arabic/accounting_lang.php`.
- New settings use `add_option('name','default')` in `modules/accounting/install.php` AND in a migration (next number after the highest in `modules/accounting/migrations/`). Guard with `field_exists`/`table_exists` so it can run twice.
- After each task, load the page or run the code path once and check there are no PHP errors.
