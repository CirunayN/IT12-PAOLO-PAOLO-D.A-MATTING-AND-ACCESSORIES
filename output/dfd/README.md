# Black-and-white Data Flow Diagram

The overview follows the supplied Figure 3: Reports at upper left, Owner above Inventory / Sales, Labor at right, Customer below, and D1–D3 at left. All diagram objects are black and white, square-cornered native draw.io shapes with editable text and arrows.

The four pages are Level 0, detailed Inventory (1.1–1.4), detailed Sales and services (2.1–2.5), and detailed Reports (3.1–3.3). Numbered process headers and open-ended stores follow the reference notation. All arrows describe data, not physical money, products or labor.

## Scope

This is the reference's intended business workflow. Labor and service-sale records are retained to honor the supplied photo. The current Laravel application implements product inventory, POS and reports; it does not yet implement Labor assignments or a service-sale table. Service steps on these diagrams are conceptual and are not a claim that those features were added to the application.

D1 is a logical product/inventory store (catalog, categories, statuses, photos and stock batches). D2 groups product-sale headers, line items and payment details. D3 represents the reference's service-sale records. D1 symbols on the detail page are views of the same logical store. Repeated Owner / Customer symbols identify the same external entities. Customer order and payment information is entered by POS staff.

Actual inventory/POS details include photo upload, category/archive management, batch expiration/condition, current remaining quantities, FIFO stock allocation, Cash/GCash details, receipts, automatic inventory filters, date-filtered reports, product rankings and PDF/print output. Existing controller behavior informed these details. No application source or database rows were modified.

`data-flows.csv` lists every displayed arrow. `01-level-0.png` and its SVG are the main diagram; remaining previews correspond to the detailed pages. Open `updated-system-dfd-2026-10-10.drawio` in diagrams.net to edit all elements.
