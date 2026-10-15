# Compact Level 0 — one page

Exactly 15 processes correspond to the supplied use-case image. Checkout calculation, payment checking, sale saving and stock deduction are within Process Sales Transaction rather than extra process boxes. There are no extra focus pages or service-process boxes.

| Process | Operation |
| --- | --- |
| 1.0 | Log In |
| 2.0 | Create User Account |
| 3.0 | Enable or Disable User Account |
| 4.0 | Add Product |
| 5.0 | Update Product |
| 6.0 | View Inventory |
| 7.0 | Record Incoming Stock |
| 8.0 | Process Sales Transaction |
| 9.0 | View Sales History |
| 10.0 | Print Sales Receipt |
| 11.0 | Generate Sales Report |
| 12.0 | Generate Inventory Report |
| 13.0 | Print or Download Reports |
| 14.0 | Create Database Backup |
| 15.0 | Restore Database Backup |

Owner, Cashier, Customer, Labor and D1-D6 are retained. Repeated labels identify the same entity or store. D6 is the aggregate database state, not another physical database. Cashier history is limited to their own transactions. Labor and D3 service exchanges remain conceptual business flows within Sales, marked with an asterisk because they are not in the supplied use case. These are not new application functions.

Earlier diagrams are preserved. The older context and Level 1 pages require matching scope and numbering revisions before inclusion with this Level 0 in the final document. No application files or database data were changed. Regenerate with `scripts/generate-compact-dfd.py`.
