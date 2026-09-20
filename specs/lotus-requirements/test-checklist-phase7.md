# Phase 7 test notes: Monthly cash flow (US8)

Not run yet (no app access when implemented). To do in the running app:

- [ ] Reports menu shows "Monthly Cash Flow" (both tabs that list Statement of Cash Flows) and opens /admin/accounting/rp_cashflow_monthly.
- [ ] T080: on a Bank account create two entries in different months (e.g. a receipt in Jan, a payment in Mar) with a non-cash counterpart account each.
  - [ ] Each amount lands in the right month column of its counterpart account row.
  - [ ] Row Total = sum of the 12 months; footer Total = sum of row totals.
  - [ ] Footer total equals net (debit - credit) of the bank/cash accounts for the year.
- [ ] Transfer between two bank/cash accounts adds nothing (cash-to-cash is excluded, nets to zero).
- [ ] Entry with no rel_id shows under "Unallocated".
- [ ] Changing year reloads the table.
- [ ] Export to Excel downloads an xlsx with same figures.
- [ ] Export to PDF opens landscape PDF via covert_pdf_report (existing dompdf route).
- [ ] User without accounting_report view is denied.
