# QuoteFlow AI Architecture

## Recorded baseline (2026-08-09)

- PHP `8.3.x` minimum; local PHP is 8.3.31 with PDO MySQL, BCMath, Intl, GD, Fileinfo, OpenSSL, ZIP, and required XML extensions.
- Laravel `^13.8` resolved to `13.24.0`, supported on PHP 8.3 and receiving security fixes through March 2028.
- Livewire `^4.0`, Blade, Livewire-bundled Alpine.js, Tailwind CSS `^4.0`, and Vite 8.
- Pest `^4.0` because Pest 5 requires PHP 8.4; Laravel Pint and Larastan/PHPStan for formatting and static analysis.
- MySQL 8.0+ as release reference; migrations remain MariaDB-compatible where doing so does not weaken constraints or correctness.
- Database queue/cache/session defaults for shared hosting; Redis may be selected on VPS without changing domain code.

Pin resolved patch versions in lockfiles during Phase 1. Production dependency licenses must be recorded before release. Laravel, Livewire, Tailwind, Alpine, and Pest use permissive open-source licenses suitable for commercial distribution; the release audit must verify every transitive package and asset.

## Runtime shape

One Laravel application serves authenticated Blade/Livewire pages, public quotation pages, installer pages, and `/api/v1` integrations. Domain actions sit between delivery components and persistence. Contracts isolate AI, payment, PDF, mail, and storage adapters. Queued jobs handle mail, PDF, import/export, webhook follow-up, reminders, and longer AI work.

Shared hosting uses cron-driven scheduler plus `queue:work --stop-when-empty` invocations. VPS deployments may use Supervisor/systemd and Redis. No production feature requires Docker or a permanent Node process.

## Tenancy and authorization

- Shared database/shared schema; tenant-owned tables require `workspace_id` and indexed foreign keys.
- `WorkspaceContext` is established from authenticated active membership or a validated public token. Request-supplied workspace IDs cannot select context.
- Tenant models use a defensive scope plus explicit repository/action constraints; unscoped access is confined to reviewed platform services.
- Jobs serialize the workspace ULID, restore context before work, and fail closed if membership/workspace state is invalid.
- Cache keys, storage paths, exports, notifications, signed links, and rate-limit keys include workspace identity.
- Platform support access requires reason, expiry, visible banner, restricted permissions, and immutable audit events.

## Data model outline

Internal primary keys use unsigned big integers; external and public references use indexed ULIDs. Tenant-owned unique keys include `workspace_id`.

- Identity: `users`, `workspaces`, `workspace_memberships`, `roles`, `permissions`, role grants, `invitations`, sessions, recovery codes.
- CRM-lite: `customers`, `customer_contacts`, `tags`, `taggables`, activity events.
- Catalogue: `service_categories`, `services`.
- Quotes: `quotations`, `quotation_revisions`, `quotation_sections`, `quotation_items`, optional selections, comments, events, acceptances, public tokens, attachments.
- Templates: `templates`, immutable template versions.
- Finance: `invoices`, `invoice_items`, `payments`, provider events, receipts, tax definitions.
- Delivery: requirement lists/items, submissions, uploads.
- SaaS: plans, plan features, subscriptions, entitlement overrides, usage ledgers.
- AI: provider configs, prompt versions, requests, usage records, structured scope reviews.
- Platform: notifications, audit logs, system settings, installation/update records.

Money stores signed integer minor units plus ISO currency and currency exponent. Percentage rates use fixed-scale decimals. Every revision stores normalized calculated totals and a canonical content hash. Foreign-key deletion is restrictive for financial/audit history; customer erasure anonymizes personal fields where retention obligations apply.

## Integration boundaries

- `PaymentProvider`: create checkout, retrieve status, verify/normalize webhook, reconcile. Adapters: manual, Stripe, PayPal.
- `AiProvider`: schema-bound response request, usage metadata, availability test. Initial adapter: OpenAI Responses API.
- `PdfRenderer`: render versioned document HTML to bytes. Shared-hosting adapter is pure PHP; an optional headless renderer may be configured on VPS.
- `FileStore`, `Mailer`, and future `MessagingProvider` contracts prevent infrastructure leakage into domain actions.

Webhook events have provider-scoped unique IDs, raw-body signature validation, replay windows, durable receipts, idempotent processing, and reconciliation. Browser redirects never mark payments successful.

## Deployment and migration policy

The web installer performs preflight checks, database connection, key generation, migrations, mode selection, and first-admin creation before writing an installation lock. CLI installation invokes the same services. Updates use preflight, backup warning, maintenance mode, ordered migrations, and explicit recovery guidance; no false automatic-rollback promise.
