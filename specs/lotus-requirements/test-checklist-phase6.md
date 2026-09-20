# Test checklist (phase 6)

## US3 Leave carry-over
- [ ] Toggle off: approve a 2-day annual leave. Only the current-year row changes (same as before).
- [ ] Toggle on, years = 3: staff has 2024 remain 5, 2025 remain 4, 2026 remain 10. Leave balances table on the request screen lists 2026, 2025, 2024 (newest first).
- [ ] Approve 12 days in 2026 with carry on: 2026 remain 0, then 2025 remain 2 (older years after current).
- [ ] Expiry: year N balance is usable in N+2 and not in N+3 (with years = 3).
- [ ] Change years to 1: only the current year row is listed and used.
- [ ] Saving the Manage leave screen stores the checkbox and years value.
- [ ] Reverting or deleting an approved leave still credits the request-year row (known limit).

## US4 Payroll posting to accounting
- [ ] Toggle on (default): closing a payslip creates journal entries as before.
- [ ] Toggle off: closing a payslip creates no journal entries and shows no PHP error; existing entries are not deleted.
- [ ] Toggle off: Accounting payslip mapping page shows the info message instead of the fields.
- [ ] Data integration page: checkbox shows the saved state after submit.
- [ ] Module upgrade runs migration 126 (timesheets) and 120 (hr_payroll) with no error; options exist.
