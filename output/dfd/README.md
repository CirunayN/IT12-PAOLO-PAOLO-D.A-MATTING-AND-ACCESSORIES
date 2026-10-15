# Black-and-white Data Flow Diagram

## Readable connected Level 0 — latest revision

Use `Level-0-DFD-Readable.drawio` for the accepted diagram with larger text, more space between routing lanes, and more padding around flow labels. It remains one page with the same 12 processes, four external entities, six shared stores and 55 data flows. All element names, process numbers, arrow directions and connections match `Level-0-DFD-Redone.drawio` exactly.

Matching PNG/SVG previews, flow CSV, use-case mapping CSV and scope notes use the `Level-0-DFD-Readable` prefix. Regenerate this spacing revision with `scripts/render-connected-dfd.py --spaced`. The accepted original and earlier revisions are retained below.

## Redone connected Level 0 — accepted structure

Use `Level-0-DFD-Redone.drawio`: **one connected page with 12 processes**, four external entities appearing once each, and six shared logical stores. Inventory has Manage Product Records, Record Incoming Stock and View Inventory. Sales has Process Sales Transaction, View Sales History and Print Sales Receipt. Sales and inventory reporting, login/account management, and backup/restore each have their own processes. Add/update products and create/enable/disable users are grouped by the records they change; report processes include print/download output.

This layout preserves Owner, Cashier, Customer, Labor and the original product/product-sale/service-sale stores, with account and backup stores. Flow labels are positioned clear of nodes and other text. Marked service flows retain the prior conceptual business scope. Matching PNG/SVG, flow CSV, use-case mapping CSV and scope notes have the `Level-0-DFD-Redone` prefix. Regenerate with `scripts/layout-connected-dfd.mjs` then `scripts/render-connected-dfd.py`. Earlier versions below are preserved.

## Compact Level 0 — earlier single-page version

Use `Level-0-DFD-Compact.drawio` for the simplified revision: **one page, 15 processes**, matching the 15 operations in the supplied use-case image. Inventory still has separate Add Product, Update Product, View Inventory and Record Incoming Stock processes. Checkout is one Process Sales Transaction process; its calculation, payment, saving and stock deduction steps are not extra boxes. There are no additional focus pages or service-process boxes. Owner, Cashier, Customer, Labor and the logical stores remain; the original service business flows are retained within Sales and marked with an asterisk.

Matching PNG/SVG previews, flow CSV and scope notes use the `Level-0-DFD-Compact` prefix. Regenerate with `scripts/generate-compact-dfd.py`. The larger versions below are retained as earlier revisions.

## Dissected Level 0 — separate processes

`Level-0-DFD-Dissected.drawio` is the earlier detailed revision. It has **21 distinct operations**, each in its own numbered process box with labelled inputs and outputs. The first page is the complete Level 0; the six focus pages show Accounts, Inventory, Sales, Reports, Backup/Restore and retained Services using the same process numbers. These focus pages are views of the same diagram, not additional DFD levels.

Inventory is separated into Add Product, Update Product, View Inventory and Record Incoming Stock. Sales checkout is separated into Validate Sales Order, Calculate Sales Total, Verify Payment, Record Sales Transaction and Update Stock After Sale; View Sales History and Print Sales Receipt have their own processes. Report generation and output, login/account/access operations, and backup/restore are also separated. Owner, Cashier, Customer, Labor and the logical stores are retained. The two conceptual service processes remain marked with an asterisk.

Matching full and module PNG/SVG previews, data-flow CSV, use-case mapping CSV and scope notes have the `Level-0-DFD-Dissected` prefix. Regenerate them with `scripts/generate-dissected-dfd.py`. Earlier diagrams below are preserved as prior revisions.

## Level 0 matched to the supplied use case — 15 October 2026

Open `Level-0-DFD-Use-Case-Matched.drawio` for the updated single-page Level 0. Matching PNG and SVG previews, data-flow CSV and a 15-use-case mapping CSV use the same filename prefix. Inventory (1.0), Sales (2.0) and Reports (3.0) are retained; Accounts and Access (4.0) and Database Backup/Restore (5.0) are added. Owner is labelled Admin/Owner, Customer and Labor remain, and Cashier is added with sales-history access restricted to their own transactions.

The previous D1–D3 stores are retained. Labor/service flows are marked with an asterisk because the supplied use case does not cover them. See `Level-0-DFD-Use-Case-Matched-notes.md` for the retained conceptual business scope and the distinction from implemented application features. The earlier context and detailed pages below retain their previous scope; they need matching revisions before inclusion with this expanded Level 0 in the final document.

Regenerate the updated artifacts with `scripts/generate-use-case-matched-dfd.py`. Original diagrams are preserved.

## Figure 2 — Matching context diagram

`00-context-diagram.png` and `.svg` show the context diagram matched to the supplied Figure 3. Open `00-context-diagram.drawio` to edit its native shapes and arrows. One process numbered **0** exchanges eight data flows with **Owner**, **Customer**, and **Labor**. Its external flows exactly balance those on the reference's Level 0 page. Internal Inventory, Sales, Reports, and D1–D3 stores are inside the system boundary and are omitted from the context view. The reference's separate worker roles are grouped as Labor; payroll and expenses are absent because Figure 3 does not include them.

`00-context-data-flows.csv` lists the eight matching flows. Regenerate these context artifacts with `scripts/generate-context-dfd.py`. Existing Level 0 and detailed pages remain available below.

The overview follows the supplied Figure 3: Reports at upper left, Owner above Inventory / Sales, Labor at right, Customer below, and D1–D3 at left. All diagram objects are black and white, square-cornered native draw.io shapes with editable text and arrows.

The four pages are Level 0, detailed Inventory (1.1–1.4), detailed Sales and services (2.1–2.5), and detailed Reports (3.1–3.3). Numbered process headers and open-ended stores follow the reference notation. All arrows describe data, not physical money, products or labor.

## Scope

This is the reference's intended business workflow. Labor and service-sale records are retained to honor the supplied photo. The current Laravel application implements product inventory, POS and reports; it does not yet implement Labor assignments or a service-sale table. Service steps on these diagrams are conceptual and are not a claim that those features were added to the application.

D1 is a logical product/inventory store (catalog, categories, statuses, photos and stock batches). D2 groups product-sale headers, line items and payment details. D3 represents the reference's service-sale records. D1 symbols on the detail page are views of the same logical store. Repeated Owner / Customer symbols identify the same external entities. Customer order and payment information is entered by POS staff.

Actual inventory/POS details include photo upload, category/archive management, batch expiration/condition, current remaining quantities, FIFO stock allocation, Cash/GCash details, receipts, automatic inventory filters, date-filtered reports, product rankings and PDF/print output. Existing controller behavior informed these details. No application source or database rows were modified.

`data-flows.csv` lists every displayed arrow. `01-level-0.png` and its SVG are the main diagram; remaining previews correspond to the detailed pages. Open `updated-system-dfd-2026-10-10.drawio` in diagrams.net to edit all elements.
