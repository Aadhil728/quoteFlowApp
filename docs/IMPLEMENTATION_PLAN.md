# QuoteFlow AI Implementation Plan

Last updated: 2026-08-10

## Decisions

- Both single-business and SaaS modes are first-class from the start.
- MVP payments include manual transfer, Stripe, and PayPal.
- cPanel/shared hosting and managed VPS receive equal deployment support.
- Baseline: PHP 8.3, Laravel 13, Livewire 4, Tailwind 4, Pest 4, MySQL 8+/MariaDB-compatible SQL.
- Shared-hosting defaults use database queues/cache/session; Redis and enhanced PDF rendering are optional VPS adapters.

## Phase status

- [x] **Phase 0 — Discovery and architecture:** repository rules, product spec, architecture/data outline, UI system, AI system, test strategy, threat model, environment template, and release plan.
- [x] **Phase 1 — Foundation and identity:** Laravel scaffold, design tokens/components, authentication, web/CLI installer, operating mode, tenancy context, memberships, centralized permissions, audit foundation, scheduler heartbeat, and responsive application shell. Automated gates passed and the installer/visual review was confirmed by the product owner on 2026-08-10.
- [x] **Phase 2 — Workspace onboarding, customers, and catalogue:** business/tax/currency/branding defaults, permission-aware team management, hashed expiring invitations and acceptance, tenant-scoped customer CRUD/contacts/activity, duplicate-aware CSV import and formula-safe export, and integer-priced service catalogue. Automated gates passed and the visual review was approved by the product owner on 2026-08-10.
- [ ] **Phase 3 — Quotation core:** versioned tenant-scoped builder, server-authoritative monetary engine, autosave, state transitions, immutable sent snapshots, revision cloning, three original templates, responsive preview, and downloadable PDF are implemented. Functional gates pass; final Larastan and visual review remain open.
- [ ] **Phase 4 — Public client decisions:** hashed/revocable/rotating links, optional items, comments, revision requests, approve/reject evidence, stable decision snapshots, public PDF, and abuse controls are implemented. Functional gates pass; final Larastan and visual review remain open.
- [ ] **Phase 5 — Invoices and payments:** approved-snapshot conversion, deposit/full invoices, manual payments, Stripe/PayPal adapters, signature-verified idempotent webhooks, receipts, refunds/failures, and reconciliation are implemented. Functional gates pass; Larastan and live provider credential validation remain open.
- [ ] **Phase 6 — AI Copilot and Scope Guardian:** provider abstraction, schemas, redaction, usage/quotas, draft/review/rewrite/follow-up/translation, eval fixtures, failure modes.
- [ ] **Phase 7 — Requirements, communication, dashboard, and reports:** handover portal, safe uploads, queued email, WhatsApp links, real KPIs, action queues, currency grouping.
- [ ] **Phase 8 — SaaS/platform operations:** plans, subscriptions, entitlements, tenant operations, audited support access, health and usage.
- [ ] **Phase 9 — Packaging and release:** installer completion, updater, demo reset, documentation site, dependency/license/security audit, clean CodeCanyon package.
- [ ] **Post-core — WordPress connector:** separately packaged versioned API client, form/block/optional Elementor widget, signed delivery, logs, privacy, uninstall.

## Phase 1 acceptance criteria

1. A fresh Laravel 13 application runs on the documented PHP extensions and builds production assets without remote fonts.
2. Installer preflight reports PHP/extensions/writable paths/database/queue-scheduler readiness without exposing secrets.
3. Installation selects either operating mode and creates the first verified super administrator through shared services used by CLI and web paths.
4. Registration/login/logout/reset/verification/session behavior and optional 2FA foundation are covered by tests.
5. Workspace creation, active membership resolution, switching, permission policies, and audit events work; cross-workspace HTTP/model access tests fail closed.
6. The original responsive shell and dashboard empty state work at desktop/tablet/mobile in light/dark modes with keyboard navigation and no horizontal overflow.
7. Formatting, static analysis, tests, migration-from-empty, production build, queue-once, scheduler, and browser smoke checks pass.

## Phase 1 verification commands

```bash
composer validate --strict
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test
npm run lint
npm run build
php artisan migrate:fresh --seed --env=testing
php artisan queue:work --once
php artisan schedule:test
```

## Phase 2 acceptance evidence

- Tenant-scoped workspace settings update with audit events.
- Invitation tokens are stored hashed, expire after seven days, and can only be accepted by the invited verified email.
- Customer create/update/archive, contacts, activity, search/filter/pagination, CSV import validation/duplicate reporting, and formula-safe export work inside the active workspace.
- Catalogue services use integer minor units, workspace-scoped SKU/category validation, active state, search, and pagination.
- Cross-tenant customer access returns 404; viewer mutation returns 403; permission-aware navigation hides unavailable modules.
- Pest: 14 tests, 49 assertions; Pint, Larastan, JavaScript lint, Vite production build, Blade compilation, installed-database migration, and live HTTP asset checks pass.

## Phase 3 acceptance evidence

- Quotations use workspace-scoped ULIDs, sequential workspace/year numbers, versioned revisions, sections and items, audit events, and an explicit status enum with guarded transitions.
- Monetary calculations use integer minor units plus decimal quantities and deterministic half-up rounding for exclusive/inclusive tax, fixed/percentage discounts, optional items, and deposits.
- Sending locks a content-addressed JSON snapshot; edits to locked revisions return 409; revision requests clone sections and items into a new editable revision while preserving history.
- Draft builder autosave reports saving/saved/error state. Responsive internal preview and a contract-backed Dompdf download use the same authoritative revision values.
- Three original versioned layouts—Essential/minimal, Studio/professional, and Signature/modern—are provisioned idempotently with preview and duplication.
- Pest: 22 tests, 95 assertions; Composer validation, Pint, JavaScript lint, Vite production build, fresh MariaDB migration/seed, installed-database migration, Blade compilation, queue-once, scheduler heartbeat, and live login/asset HTTP smoke checks pass.
- Open gate: Larastan 3 / PHPStan 2.2.8 exits with code 1 before emitting diagnostics in both sandboxed and approved unsandboxed runs. Phase 3 remains unchecked until static analysis produces a valid result and the quotation/template screens receive visual review.

## Phase 4 acceptance evidence

- Public links use 48-byte random opaque values; only SHA-256 hashes are stored. Creating a replacement link revokes prior active links, and workspace users can revoke all links explicitly.
- Links are bound to one locked quotation revision, expire at the earlier of quotation expiry or 30 days, and return 410 after expiry or revocation.
- Public responses set `X-Robots-Tag: noindex, nofollow, noarchive`, `Referrer-Policy: no-referrer`, private no-store caching, and frame denial.
- Optional selections remain separate from immutable revision items and are recalculated by the Phase 3 server-authoritative monetary engine.
- Client comments and revision requests are tied to the token revision and create quotation timeline events.
- Approval/rejection creates one immutable JSON decision snapshot with a SHA-256 hash, printed name, explicit acceptance flag, decision time, and privacy-conscious HMAC/hash evidence. The UI clearly states this is not a regulated qualified electronic signature.
- Public PDFs render the accepted snapshot after a decision and the locked revision plus current token selections beforehand.
- Named throttling limits each token/IP combination to 30 requests per minute. Automated coverage verifies the 31st attempt receives HTTP 429.
- Full suite: 30 tests, 159 assertions. Pint, JavaScript lint, Vite production assets, MariaDB migration-from-empty with seed, installed-database migration, Blade compilation, privacy headers, PDF bytes, revocation, rotation, and rate limiting pass.
- Open gate: the previously recorded Larastan process failure remains unresolved, and the browser runtime is unavailable for automated responsive visual review. Phase 4 remains unchecked pending those required gates or product-owner visual approval.

## Phase 5 acceptance evidence

- Approved quotations convert transactionally into one tenant-scoped deposit or full invoice using the immutable client acceptance snapshot, selected optional scope, acceptance hash, sequential invoice number, and authoritative integer-minor-unit totals.
- Duplicate conversion, unapproved conversion, zero-deposit conversion, overpayment, cross-workspace access, and users without finance permissions fail closed.
- Confirmed manual payments update paid/balance values, produce sequential immutable receipt snapshots and hashes, and create audit records. Partial and final payments set explicit invoice states.
- Stripe Checkout and PayPal Orders integrations sit behind a shared payment-provider contract. Secrets and webhook IDs remain environment-only, and browser return URLs never mark invoices paid.
- Stripe verifies the raw body, timestamp tolerance, and `Stripe-Signature` HMAC. PayPal posts the received transmission headers and full event to its official verification API before processing.
- Provider event IDs are unique per provider. Retried webhooks are acknowledged without replaying balances or receipts; successful, processing, failed, and refunded states normalize into local payment states.
- Reconciliation recalculates invoice paid/balance/status fields from successful payment records and records before/after audit evidence.
- Full suite: 41 tests, 222 assertions. Pint, Larastan, JavaScript lint, Vite production build, isolated migration coverage, installed-database migration, and preservation of the development account pass. Business settings now use the reusable searchable-select control with 178 current ISO 4217 entries, and the application/default workspace brand is Royal Indigo.
- Open gates: Larastan continues to exit with code 1 without emitting diagnostics in this environment. Live Stripe/PayPal checkout and webhook delivery require product-owner credentials and registered webhook IDs, so Phase 5 remains unchecked until those external validations are completed.

## Blockers and risks

- **Resolved:** GitHub access recovered; Laravel 13.24.0, Livewire 4, Pest 4, Larastan, and frontend dependencies are installed with lockfiles.
- **Environment:** XAMPP MariaDB 10.4 is running locally and the UTF-8 `quoteflow` database exists. The installed Windows `MySQL80` service could not be started with the current permissions; MySQL 8 remains the release validation target.
- **Environment:** Git commands in this session require `git -c safe.directory='D:/Aadhil/My Projects/QuoteFlow' ...` because the sandbox and interactive user have different Windows ownership identities; do not change global Git config implicitly.
- **Resolved:** Phase 1 installer and visual review were confirmed by the product owner on 2026-08-10.
- The screenshot is conversation-attached rather than repository-backed; save the original as `docs/references/dashboard-ui-reference.png` when available for repeatable visual comparison.
- Equal shared-hosting/VPS support requires scheduled short-lived queue workers as a fully tested fallback and must avoid daemon-only correctness.
- Three payment methods enlarge webhook and regional QA; adapters and fake-provider contracts must precede provider UI.
- Simultaneous single/SaaS delivery increases permission/entitlement combinations; mode and tenant-isolation tests begin in Phase 1.
