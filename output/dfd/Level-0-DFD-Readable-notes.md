# Readable Level 0: spacing-only revision

The 12 processes, four entities, six stores and 55 named connections are identical to the accepted Redone file. Only positions, spacing, font sizes and line styles are changed. Tight parallel routing lanes are spread apart, and labels have larger text and padding.

This revision has 12 process boxes, four external entities and six shared logical stores. Each entity/store appears once. All nodes belong to the same connected data-flow network. Inventory, Sales and Reports are separated into three, three and two distinct operations; Accounts and Backup/Restore each have two operations.

Add/update product details are one Maintain Product Records transformation, and account creation/access changes are one Manage User Accounts transformation. Sales and inventory reports include the requested print/download output. This reduces process counts while preserving the supplied use-case capabilities. Each process has at least one input and one output. All arrows carry named data; confirmations that are not essential to the overview are omitted.

Owner is labelled Admin/Owner; Cashier, Customer and Labor are retained. The original product, product-sale and service-sale stores remain. D4 contains accounts/sessions, D5 contains backups, and D6 is an aggregate database state, not another physical database. Cashier history is restricted to their own sales.

Marked Labor/service flows retain the earlier conceptual business workflow and are outside the supplied use case. They are not claims of implemented Laravel service functions. No application source or database records were modified. Previous revisions are preserved. Earlier context and detailed pages need corresponding scope/numbering changes before use together in the final document.

Regenerate with `scripts/render-connected-dfd.py --spaced` using the accepted Redone layout data.
