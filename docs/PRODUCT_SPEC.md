# QuoteFlow AI Product Specification

## Product promise

QuoteFlow AI turns unstructured customer requests into professional, reviewable quotations, protects service businesses from scope creep, captures client decisions, collects deposits, and converts accepted work into invoices and requirements handover.

Branding is configurable; `QuoteFlow AI` is a working name. The same installation supports `single_business` and `saas` operating modes selected during protected installation and changeable only through an audited system operation.

## People and permissions

- **Super Administrator:** installation, tenants, plans, global providers, operations, updates, and audited support access.
- **Platform Support:** limited diagnostics and explicitly granted, time-boxed tenant support; no implicit content access.
- **Workspace Owner:** business, billing, integrations, team, branding, AI, and all workspace records.
- **Administrator:** operational administration under assigned permissions.
- **Sales Member:** assigned customers and quotations.
- **Finance Member:** invoices, payments, taxes, and reports.
- **Viewer:** authorized read-only access.
- **Client:** tokenized public access to view, comment, revise, decide, download, and pay.

Roles are permission bundles. Policies, not role-name conditionals, enforce access.

## MVP journeys

1. Install on cPanel/shared hosting or a managed VPS, choose operating mode, and create the first administrator.
2. Create a workspace, complete company/tax/currency/payment onboarding, invite members, and configure branding.
3. Add/import customers and reusable services with scoped search, validation, duplicate detection, and export.
4. Build a quotation manually, from a template, or from an explicitly reviewed AI draft; autosave, calculate on the server, preview, send, and revise using immutable versions.
5. Let a client use a revocable signed link to select optional items, comment, request revision, approve/reject, and download a stable PDF.
6. Convert an approved quotation into a deposit or full invoice; accept manual, Stripe, or PayPal payment and issue a receipt.
7. Collect approved-project requirements through a safe upload and checklist portal.
8. Use queued email and user-reviewed WhatsApp share links for communication.
9. View real, workspace-scoped operational KPIs without mixing currencies.
10. In SaaS mode, manage plans, trials, subscriptions, entitlements, quotas, platform health, and audited support access.

## State models

- Quotation: `draft`, `ready`, `sent`, `viewed`, `revision_requested`, `approved`, `rejected`, `expired`, `converted`, `cancelled`.
- Invoice: `draft`, `sent`, `viewed`, `partial`, `paid`, `overdue`, `void`.
- Payment: `pending`, `processing`, `succeeded`, `failed`, `cancelled`, `refunded` (refund execution is post-MVP; status supports imported/provider truth).
- Requirement item: `requested`, `submitted`, `accepted`, `changes_requested`, `waived`.

All transitions are explicit domain operations with actor, timestamp, authorization, idempotency where relevant, and audit events.

## Product rules

- Sent and accepted revisions never change when a service, template, tax setting, or optional selection changes later.
- AI never confirms prices, sends documents, changes financial records, or takes client decisions.
- Acceptance records printed name, affirmative checkbox, accepted revision hash, timestamp, and a privacy-conscious security event; it is not marketed as a qualified electronic signature.
- Currency totals are grouped by ISO currency unless configured exchange-rate support exists.
- Single-business mode hides plans, tenant switching, and platform billing UI while retaining the same tenant-safe internals.

## MVP completion

The 18 acceptance criteria in the master brief are normative. Each phase adds traceable automated and browser evidence; a feature remains incomplete if its failure, empty, no-permission, responsive, or dark-mode state is missing.
