# Phase 5 test checklist (US7) - NOT run, app could not be executed

- [ ] Entry list: long description wraps fully; entry number links to view
- [ ] Create entry with one JPG and one PDF; stored in modules/accounting/uploads/journal_entry/<id>/
- [ ] Upload .exe or >10 MB file: rejected with warning, entry still saved
- [ ] View page: logo, lines, totals, attachments with View (new tab) and Download
- [ ] Print: buttons hidden, image attachment shown inline
- [ ] PDF: image inline, PDF listed by name
- [ ] Excel: Attachments rows; currency/rate rows only if doc_currency exists
- [ ] Edit: remove attachment and new upload work
- [ ] New entry id is found by number after add_journal_entry (model returns only true)
