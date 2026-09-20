# Lotus Requirements: HR and Accounting Spec

Status: Draft, clarified with the product owner. Modules: `hr_payroll` (Payroll), `hr_profile` (HR Records), `timesheets` (Timesheet), `accounting`.

## Global rules
- Every feature is a setting in the module that owns it. Each has an on/off toggle plus value fields, and every value is stored per company.
- Features are off by default, so existing behaviour does not change until a company turns one on.
- Saved documents (payslips, journal entries, invoices, bank transactions) are never recalculated when a setting or rate changes.

## Out of scope (revoked for now)
- Account ledger columns cleanup (old point 9), pending the report name.
- Real estate map change (old point 12): no change at all.

## User stories

### US1 (P1): Overtime with morning/night split and cap
Module settings: Timesheet (time ranges), Payroll (rates, cap, column mode).
- Each company defines the morning and night time ranges (Timesheet settings).
- Each company defines the morning rate (default 1.35) and the night rate (default 1.7), both editable (Payroll settings).
- Morning and night OT hours are calculated separately.
- A toggle chooses the payslip layout: separate morning and night OT columns, or one combined "OT hours" column.
- Cap: a toggle plus an hours limit, set per department and per employee. An employee value overrides the department value. The cap is per payroll month and applies to the combined morning plus night hours.
- When the cap cuts hours, night hours are kept first. Hours above the cap are discarded, not paid and not carried over.
- The payslip shows discarded hours as a separate line.

Acceptance: an employee with 10 morning and 10 night OT hours and a cap of 12 is paid 10 night hours and 2 morning hours. 8 hours are shown as discarded.

### US2 (P1): Late-arrival allowance
Module settings: Timesheet.
- Toggle plus free monthly allowance in hours (default 4).
- Delay above the allowance is deducted 1:1 in hours from the employee's OT hours if the employee is OT-eligible. Otherwise it is deducted from working-time pay at the normal hourly rate.
- Order of calculation: (1) OT hours by type, (2) delay deduction from the uncapped total, (3) cap on the remainder.
- The payslip shows delay deduction, discarded hours and final OT as separate lines.

Acceptance: an OT-eligible employee with 7 late hours, allowance 4 and 20 OT hours has 3 hours deducted. 17 OT hours remain, and the cap is then applied to those.

### US3 (P2): Annual leave carry-over
Module settings: HR Records.
- Toggle plus "years to accumulate" (default 3).
- Leave is drawn from the current year's balance first, then the older years.
- Balance older than the window expires when a new year starts.
- Leave requests show the balance per year.

Acceptance: with a window of 3, the balance from year N expires at the start of year N+3.

### US4 (P2): Payroll independent of accounting
Module settings: Payroll.
- A per-company toggle "Post payroll to accounting".
- When off: no journal entries are created and no account mapping is required or shown.
- Turning it off never touches existing entries.

Open point: trace what a payslip deduction is taken from (deduction base). To be answered from the code during planning.

### US5 (P1): Currency rates with effective dates
Module settings: Accounting (Finance setup > Currency rates).

Current behaviour (found in code): `currency_rates` holds one live rate per pair, and `acc_get_currency_rate()` and `acc_get_secondary_currency_amount()` read it every time a number is shown. Changing the rate today therefore changes every past journal entry on screen. This is the bug.

Target behaviour:
- Each currency pair (for example USD to EGP) has an auto-rate toggle. The existing row in `currency_rates` stays as the auto rate.
- Manual rate rows have a "from" datetime and an optional "to" datetime.
- Rate for a document: the manual row whose window contains the document date. If none matches, use the auto rate if the toggle is on, otherwise the latest earlier manual row.
- On overlapping windows the most recently created row wins, and the UI warns on save.
- If no rate exists, the save is blocked with a message asking for a rate.
- Editing a rate never changes saved documents.

### US6 (P1): Frozen rates and multi-currency documents
- Every ledger line stores its own currency, rate and amounts at save time. Reports and ledgers read the stored values and never the live rate table.
- Journal entries, bank transactions and invoices get a currency dropdown fed by the currencies in Finance setup. The document stores the document currency amount, the rate (from US5) and the base amount.
- The secondary-currency display (existing feature) reads the amount stored on the line, not a live rate.
- Ledgers and reports run in the base currency.
- One-time backfill of existing lines (decision: option a): each existing line gets the rate that was in effect on its transaction date, taken from `currency_rate_logs`. Where the log has nothing for that date, use the current rate. A backfill report table lists every row, the rate given and which rule was used, so nothing changes silently.
- Deferred, not in this build: a permission-gated manual rate override on a single document. The owner has not decided this.

### US7 (P2): Journal entry view, export and attachments
- A read-only layout view with company name and logo, the entry details, and attachments.
- Print, PDF and Excel export.
- Multiple attachments can be added on create and edit (PDF, images, Office files, 10 MB each, assumed).
- On screen each attachment has View and Download. In print and PDF, image attachments render inline and other files appear as a list of file names. Excel lists file names only.
- The full description shows in the journal entry list table.

### US8 (P3): Cashflow report by month
- Direct method: rows are the counterpart accounts of cash and bank movements.
- 12 month columns plus a total, a year selector, PDF and Excel export.

## Priorities
P1: US1, US2, US5, US6. P2: US3, US4, US7. P3: US8.

## Assumptions to confirm
- The attachment types and the 10 MB limit are assumed, not confirmed.
- The night window default (22:00 to 06:00) is only a starting value.
- Base currency comes from the existing Finance setup.
