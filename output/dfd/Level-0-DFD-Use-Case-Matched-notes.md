# Level 0 DFD matched to the supplied use case

Open `Level-0-DFD-Use-Case-Matched.drawio` for editable native shapes, labels and arrows. The PNG and SVG are previews of that single Level 0 page. Original artifacts are preserved.

The Owner is labelled Admin/Owner to match the use case. Customer and Labor remain, and Cashier is added. Repeated actor/store symbols identify the same entity or logical store.

## Use-case mapping

| Supplied use case | Level 0 process | Actor |
| --- | --- | --- |
| Log In | 4.0 | Admin/Owner; Cashier |
| Create User Account | 4.0 | Admin/Owner |
| Enable or Disable User Account | 4.0 | Admin/Owner |
| Add Product | 1.0 | Admin/Owner |
| Update Product | 1.0 | Admin/Owner |
| View Inventory | 1.0 | Admin/Owner |
| Record Incoming Stock | 1.0 | Admin/Owner |
| Process Sales Transaction | 2.0 | Admin/Owner; Cashier |
| View Sales History | 2.0 | Admin/Owner; Cashier (own transactions only) |
| Print Sales Receipt | 2.0 | Admin/Owner; Cashier |
| Generate Sales Report | 3.0 | Admin/Owner |
| Generate Inventory Report | 3.0 | Admin/Owner |
| Print or Download Reports | 3.0 | Admin/Owner |
| Create Database Backup | 5.0 | Admin/Owner |
| Restore Database Backup | 5.0 | Admin/Owner |

## Retained business scope

The prior Level 0 included Labor, service jobs and D3 service-sale records. These are retained at the user's request and marked with an asterisk. The supplied use-case image does not include service management. These flows describe the previous conceptual business workflow, not implemented Laravel service functions. Customer and Labor exchange information through the Owner/Cashier; the use case gives neither a system login. Full documentation alignment would require adding these service interactions to the use case or explaining their business-only scope.

D1 Product Records and D2 Product Sale Records are retained; D4 covers user accounts and sessions. D5 contains backup files. D6 denotes the aggregate database state of D1-D4 for backup and restore, not a second physical database. Sales read and write stock through D1, so no separate inventory process-to-sales arrow is needed. Cashier history returns only their own transactions.

The earlier context and Level 1 pages have not been changed; their older scope is not balanced with this expanded Level 0. Update those pages before using them together in the final document.

No application source or database rows were changed. Regenerate with `scripts/generate-use-case-matched-dfd.py`.
