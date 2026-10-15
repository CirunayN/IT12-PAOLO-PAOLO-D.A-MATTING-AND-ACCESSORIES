# Level 0 DFD with separate processes

The first draw.io page is the complete expanded Level 0. The six remaining pages are focus views of the same diagram for reading and printing, with identical process numbers. They are not child decompositions or additional DFD levels. Every numbered box performs one distinct operation with labelled inputs and outputs. A process may have several related input/output arrows; it is separated by the operation it performs.

## Processes

| Area | Separate processes |
| --- | --- |
| Accounts and Access | 1.0 Log In; 2.0 Create User Account; 3.0 Enable or Disable User Account |
| Inventory | 4.0 Add Product; 5.0 Update Product; 6.0 View Inventory; 7.0 Record Incoming Stock |
| Sales | 8.0 Validate Sales Order; 9.0 Calculate Sales Total; 10.0 Verify Payment; 11.0 Record Sales Transaction; 12.0 Update Stock After Sale; 13.0 View Sales History; 14.0 Print Sales Receipt |
| Reports | 15.0 Generate Sales Report; 16.0 Generate Inventory Report; 17.0 Print or Download Reports |
| Database Backup and Restore | 18.0 Create Database Backup; 19.0 Restore Database Backup |
| Retained Service Business Processes* | 20.0 Record Service Request*; 21.0 Record Service Status* |

## Scope and consistency

Admin/Owner, Cashier, Customer and Labor remain. D1 Product Records, D2 Product Sale Records and D3 Service Sale Records are retained, alongside D4 accounts, D5 backup files and the D6 aggregate database state. Repeated rectangles identify the same actor/store. D6 is not another physical database.

All 15 supplied use cases map to the 19 supported-system operations. The Process Sales Transaction use case is split into order validation, amount calculation, payment checking, saving the sale and deducting stock. Receipt printing and history retrieval have their own processes. Cashier history is limited to their own transactions. These are logical steps; saving a sale and updating stock still belong to the same atomic checkout in the application.

The two marked service processes (20.0 and 21.0) retain the original Labor/service business flows. They are outside the supplied use case and are not claims of implemented Laravel service functions. Customer and Labor are business data sources/recipients, not login actors in the supplied use case. Payment checking describes validation of recorded payment information, not an automated connection to GCash.

The preceding grouped Level 0, context and older Level 1 files are preserved. Earlier context/Level 1 pages require corresponding scope and numbering revisions before inclusion with this expanded Level 0 in the final document.

No application source or database rows were changed. Regenerate documentation with `scripts/generate-dissected-dfd.py`.
