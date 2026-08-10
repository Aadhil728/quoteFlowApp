# QuoteFlow AI — Codex Master Build Prompt

Copy this entire prompt into Codex from an empty project directory. Attach the UI reference screenshot to the same Codex conversation, or save it in the repository as `docs/references/dashboard-ui-reference.png` before starting.

---

## MASTER PROMPT START

You are my senior product engineer, software architect, UI/UX engineer, security reviewer, QA engineer, and technical documentation partner. Work with me to build a commercial, production-quality, self-hosted SaaS product intended for sale on CodeCanyon.

The working product name is **QuoteFlow AI**. Treat the name as provisional and keep branding configurable so it can be renamed before launch.

Do not generate a disposable demo, tutorial architecture, fake dashboard, or monolithic prototype. Build maintainable commercial software that another developer can install, understand, extend, test, and support. Avoid unnecessary abstraction, but do not sacrifice tenant isolation, authorization, data integrity, security, or testability.

## 1. Working method

Before writing application code:

1. Inspect the current directory, existing files, installed tools, language versions, Git state, and any repository instructions.
2. Inspect the attached UI screenshot or `docs/references/dashboard-ui-reference.png` if available. Describe the reusable visual patterns you observe, but do not copy its branding, logo, text, or product-specific content.
3. Ask me only questions that materially affect architecture, scope, licensing, payment gateways, or deployment. Group essential questions together. When a reasonable default is safe, state the assumption and proceed.
4. Create a phased execution plan with dependencies, risks, acceptance criteria, and verification commands.
5. Create and maintain these files:
   - `AGENTS.md`: concise repository rules, engineering conventions, commands, boundaries, and definition of done.
   - `docs/PRODUCT_SPEC.md`: product behavior, personas, workflows, roles, modules, and acceptance criteria.
   - `docs/ARCHITECTURE.md`: architecture decisions, tenancy model, data model, services, queues, storage, integrations, and security boundaries.
   - `docs/UI_SYSTEM.md`: design tokens, typography, spacing, responsive behavior, components, accessibility, and screenshot-derived visual direction.
   - `docs/IMPLEMENTATION_PLAN.md`: phased checklist with status, decisions, blockers, and next actions.
   - `docs/AI_SYSTEM.md`: AI use cases, schemas, prompts, privacy, quotas, fallbacks, evaluations, and cost controls.
   - `docs/TESTING.md`: test strategy and commands.
   - `CHANGELOG.md`: user-facing changes using Keep a Changelog conventions.
   - `.env.example`: documented environment variables with safe placeholder values only.
6. Implement one coherent phase at a time. At the end of every phase:
   - run formatting, static analysis, automated tests, and the production build;
   - review the diff for security, regressions, tenant leaks, authorization failures, and incomplete UI states;
   - update `docs/IMPLEMENTATION_PLAN.md` and `CHANGELOG.md`;
   - summarize what changed, what was verified, and the next phase.
7. Do not claim a feature works unless you have tested it. Never silently skip failing tests.
8. Do not delete or overwrite unrelated user work. Do not commit secrets. Do not make Git commits unless I explicitly ask.
9. Prefer stable, actively maintained packages. Before adding a production dependency, explain why it is needed and check license compatibility for commercial redistribution.
10. Keep the product fully functional without AI. AI must enhance the quotation workflow, not become a hard dependency.

If Codex Goal mode is available, treat the objective as: build and verify the complete QuoteFlow AI MVP described below, continuing phase by phase until every MVP acceptance criterion is satisfied or a genuine product decision requires my input.

## 2. Product vision

QuoteFlow AI is a polished quotation-to-approval platform for service businesses, freelancers, web agencies, consultants, contractors, printing companies, photographers, maintenance providers, and other small businesses.

It turns unstructured customer requests into clear quotations, detects missing requirements and scope risks, sends professional public quotation links, records client decisions, collects deposits, and converts approved quotations into invoices and handover workflows.

Primary value proposition:

> Turn customer requests into professional quotations, protect projects from scope creep, get client approval, and collect deposits faster.

The product must support two operating modes from the same codebase:

1. **Single-business mode:** one company installs QuoteFlow AI for its own use.
2. **SaaS mode:** a platform owner hosts multiple isolated business workspaces with plans, trials, limits, and subscriptions.

The mode must be selected during installation or through a protected system configuration. Do not maintain separate forks.

## 3. Users and roles

Implement explicit permissions rather than scattered role-name checks.

### Platform roles

- Super Administrator: owns the SaaS installation, system settings, plans, tenants, platform usage, global AI settings, queues, logs, and updates.
- Platform Support: can inspect permitted tenant metadata and support diagnostics without automatically seeing sensitive customer content.

### Workspace roles

- Workspace Owner: full company access, billing, integrations, members, branding, and AI configuration.
- Administrator: manages customers, quotations, invoices, templates, and team members according to permissions.
- Sales Member: manages assigned customers and quotations.
- Finance Member: manages invoices, payments, taxes, and reports.
- Viewer: read-only access to permitted workspace data.

### External user

- Client/Customer: accesses a signed public link or optional portal account to view, comment, request revisions, approve/reject, download, and pay.

Authorization must be implemented with policies/gates and covered by tests. Every tenant-owned query must be tenant-scoped. Prevent IDOR by using authorization in addition to non-sequential public identifiers.

## 4. Recommended technical foundation

Use the latest stable, mutually compatible releases available at project initialization. Record exact selected versions in `docs/ARCHITECTURE.md` rather than blindly using versions written in this prompt.

- PHP 8.3 or newer when compatible with the selected Laravel version.
- Laravel using the current stable supported release.
- MySQL 8+ and MariaDB-compatible schema where practical.
- Blade + Livewire + Alpine.js for a server-rendered, shared-hosting-friendly application.
- Tailwind CSS with an original component layer and design tokens.
- Vite for frontend assets.
- Pest or PHPUnit for automated tests.
- Laravel queues using the database driver by default; Redis optional.
- Laravel scheduler for reminders, cleanup, expiry, and recurring jobs.
- Local filesystem by default; S3-compatible storage optional.
- SMTP email with queued notifications.
- A reliable PDF solution with a shared-hosting-compatible default. Abstract PDF rendering so a higher-fidelity renderer can be configured on VPS deployments.
- Stripe, PayPal, and manual bank transfer behind payment-provider interfaces. Begin with manual payment, then add online gateways phase by phase.
- OpenAI integration through a provider abstraction and server-side API calls.
- A separate WordPress connector plugin only after the core MVP is stable.

Do not create a separate SPA unless a verified product requirement justifies it. Do not require Docker in production. Provide Docker only as an optional local-development environment if useful. The final package must support common cPanel/shared-hosting installation within clearly documented server requirements.

Use strict types where practical, typed data-transfer objects for service boundaries, enums for stable states, form requests for validation, policies for authorization, actions/services for meaningful use cases, database transactions for multi-record state changes, queued jobs for slow work, domain events where they improve decoupling, and clear exception handling.

## 5. Tenancy architecture

Use a single database with shared tables and a required `workspace_id` on tenant-owned records unless investigation finds a stronger reason for another approach. Document the decision.

Requirements:

- Resolve the active workspace through authenticated membership, not an untrusted request field.
- Provide a workspace-switcher for users belonging to multiple workspaces.
- Apply tenant scoping centrally and defensively.
- Composite unique indexes must include `workspace_id` where appropriate.
- Jobs, notifications, exports, webhook handlers, and scheduled tasks must carry and re-establish tenant context safely.
- Cache keys and storage paths must be namespaced per workspace.
- Platform administrators must use explicit audited support access rather than bypassing tenant scope globally.
- Add automated tenant-isolation tests for reads, writes, exports, signed URLs, jobs, and API endpoints.

## 6. MVP modules

### 6.1 Authentication and onboarding

- Registration, login, logout, password reset, email verification, session management, and optional two-factor authentication.
- Create or join a workspace.
- Guided onboarding: company details, logo, business address, currency, timezone, tax, quotation prefix, default validity, payment instructions, and branding.
- Seed a safe demo workspace and sample data only in demo mode.
- Display useful empty states instead of fake metrics.

### 6.2 Workspace and team management

- Workspace profile and branding.
- Member invitations with expiring tokens.
- Roles and permissions.
- Member activation/deactivation.
- Audit important membership, permission, billing, integration, and security changes.

### 6.3 Customers

- Customer/company details, contacts, billing address, email, phone/WhatsApp, tax ID, currency, locale, internal notes, tags, and status.
- Customer activity timeline covering quotations, revisions, approvals, invoices, payments, files, and messages.
- Search, filters, sorting, pagination, CSV import/export, validation report, duplicate detection, and bulk actions with authorization.
- Data export and safe deletion/anonymization behavior.

### 6.4 Products and service catalogue

- Reusable products/services with name, description, SKU/code, unit, rate, tax behavior, category, active status, and optional cost metadata.
- Workspace-specific catalogue only.
- Quick insertion into quotations.
- Do not implement inventory management in the MVP.

### 6.5 Quotation builder

This is the product's core experience and must receive the highest UI and test quality.

- Create from blank, template, duplicate, customer brief, or AI-generated draft.
- Header, customer, issue date, expiry, currency, salesperson, reference, introduction, sections, line items, optional items, notes, terms, exclusions, client responsibilities, milestones, attachments, tax, fixed or percentage discounts, shipping/fees when enabled, deposit request, and signature/acceptance settings.
- Drag-and-drop ordering for sections and line items with keyboard-accessible alternatives.
- Autosave drafts with a clear saving/saved/error state.
- Server-authoritative monetary calculations using decimal values; never floating point.
- Support tax-inclusive and tax-exclusive modes with documented rounding rules.
- Maintain immutable/versioned sent snapshots so previously sent or accepted quotations do not change when catalogue items or templates change.
- Revision workflow that creates a new revision while preserving prior versions and the activity timeline.
- Status state machine: draft, ready, sent, viewed, revision_requested, approved, rejected, expired, converted, cancelled.
- Enforce valid transitions in domain logic and tests.
- Responsive live preview and print/PDF preview.

### 6.6 Templates

- Reusable quotation templates with default sections, items, terms, notes, colors, and layout style.
- At least three original sample layouts: Minimal, Professional, and Modern.
- Do not copy templates or assets from the screenshot or other commercial products.
- Template preview and duplication.

### 6.7 Public client quotation experience

- Secure signed link using an opaque public identifier and revocable token.
- Mobile-first, branded, fast, accessible public page.
- View quotation, select permitted optional items, recalculate total, comment, request a revision, approve, reject, enter printed name, record acceptance checkbox, and download PDF.
- Explain that the simple acceptance record is not represented as a regulated qualified electronic signature.
- Record timestamps and a privacy-conscious audit event. Do not expose sensitive device data in normal UI.
- Provide token rotation/revocation, expiry behavior, and rate limiting.
- Prevent indexing by search engines and prevent leakage through analytics or referrer headers.
- Show a stable accepted snapshot after approval.

### 6.8 Invoices and payments

- Convert an approved quotation into an invoice without retyping data.
- Full or deposit invoice; fixed or percentage deposit.
- Invoice status: draft, sent, viewed, partial, paid, overdue, void.
- Manual bank-transfer instructions and manual payment recording with audit log.
- Receipt generation.
- Stripe and PayPal integrations behind provider interfaces after manual payments are complete.
- Verify webhook signatures, make handlers idempotent, store provider event IDs, and never trust a browser redirect as payment confirmation.
- Refunds and accounting integrations are post-MVP unless needed for correctness.

### 6.9 Client requirements and handover

- Create requirement checklists from approved quotations or reusable templates.
- Request text, logos, images, documents, domain details, and other project inputs.
- Customer upload page with file restrictions, scanning hook/interface, status, due date, comments, and reminders.
- Do not collect passwords in normal custom fields. A credential vault is excluded from MVP until separately security-designed.

### 6.10 Communication

- Queue email delivery for quotation, revision, approval, rejection, invoice, receipt, expiry, and reminder events.
- Workspace-editable templates with safe variables and preview.
- WhatsApp share links with professional prefilled messages and proper URL encoding.
- No official WhatsApp Cloud API dependency in MVP; design an integration boundary for a future add-on.
- Store message intent/status without pretending an external WhatsApp message was delivered.

### 6.11 Dashboard and reports

- Date-range selector.
- KPI cards: quotations created, sent, viewed, approved, rejected, expired, approval rate, quoted value, approved value, deposits collected, outstanding invoices, and average approval time.
- Recent activity, quotations requiring action, expiring quotations, overdue invoices, AI usage, and plan usage.
- Charts only where they add decision value.
- All metrics must come from real scoped queries and respect currency constraints. Do not incorrectly sum different currencies; group or convert only when exchange-rate functionality exists.
- Empty, loading, partial-error, and no-permission states.

### 6.12 SaaS plans and platform administration

- Plans, monthly/yearly/lifetime/manual plans, trials, feature flags, quotas, and grace behavior.
- Limits for team members, customers, quotations, storage, templates, AI requests/tokens, and integrations.
- Entitlement checks must be centralized and testable.
- Super-admin dashboard for tenants, plans, subscriptions, platform AI consumption, mail/queue health, scheduled jobs, storage, and version information.
- Safe tenant suspension that preserves data.
- Impersonation/support access only with explicit reason, time limit, visible banner, and audit log.
- Single-business mode must hide SaaS-only navigation and complexity.

## 7. AI system: AI Quotation Copilot and Scope Guardian

AI is an assistive drafting and review layer. It must never autonomously send a quotation, approve a document, determine binding prices, charge a customer, or modify financial records.

### 7.1 AI features for MVP

1. **Brief-to-Quotation Copilot**
   - Accept a typed brief, pasted email, copied WhatsApp conversation, or notes.
   - Produce a structured editable draft: title, summary, sections, suggested line items, optional items, milestones, deliverables, questions, assumptions, exclusions, client responsibilities, risks, and suggested deposit percentage.
   - Do not invent final prices. Match items to the workspace service catalogue when confidence is sufficient; otherwise leave price empty and visibly require confirmation.

2. **AI Scope Guardian**
   - Review a draft for ambiguous deliverables, unpriced requirements, missing dependencies, unrealistic or inconsistent timelines, missing revision limits, unclear content ownership, weak payment terms, missing acceptance criteria, and scope-creep risk.
   - Return severity, category, explanation, affected section/item, and suggested correction.
   - Let the user apply suggestions individually; never rewrite the entire quotation invisibly.

3. **Professional rewrite**
   - Rewrite selected text as professional, concise, friendly, detailed, or non-technical.
   - Preserve facts, numbers, dates, prices, names, and commitments unless the user explicitly requests changes.

4. **Follow-up generator**
   - Generate a professional message based on quotation status: sent but unopened, viewed but unanswered, revision pending, expiring soon, approved awaiting deposit, or overdue invoice.
   - Require human review before sharing.

5. **Summary and translation assistance**
   - Generate a concise client summary.
   - Translate client-facing content with RTL-ready Arabic support.
   - Display a clear review warning for legal, financial, and technical language.

### 7.2 AI architecture

- Create an `AiProvider` contract and an initial `OpenAiProvider`. Avoid coupling controllers or Livewire components directly to a vendor SDK.
- Use the OpenAI Responses API for the initial provider.
- Use strict Structured Outputs/JSON Schema for quotation drafts, scope reviews, follow-ups, rewrites, and summaries.
- Validate every response again on the server and handle refusals, invalid responses, timeouts, rate limits, exhausted credit, and provider downtime.
- Queue long operations where appropriate and show cancellable progress/status.
- Use idempotency/deduplication so retries do not create duplicate drafts.
- Store prompt template versions and model/provider metadata for reproducibility, but do not expose chain-of-thought or request it.
- Keep model selection configurable by the platform owner; do not hard-code a model assumed to remain current.
- Provide BYOK mode and platform-managed mode.
- Encrypt API keys at rest, never return them to the browser after saving, mask them in UI, and provide a connection test.
- Add per-workspace request and token limits, monthly reset rules, usage ledger, cost-estimation hooks, warnings, and hard caps.
- Minimize personal and commercially sensitive data sent to the provider. Redact email addresses, phone numbers, addresses, access credentials, and unrelated identifiers when feasible.
- Offer `store: false` and explain provider data handling in the admin UI and documentation.
- The UI and all core workflows must degrade gracefully when AI is disabled or unavailable.

### 7.3 AI quality and safety evaluation

Create deterministic fixtures and an evaluation suite containing briefs for web agencies, photographers, contractors, consultants, repair services, and printing companies. Test:

- Schema validity.
- Preservation of explicit requirements.
- No fabricated price presented as confirmed.
- Detection of deliberately omitted requirements.
- Stable handling of malicious prompt-injection text copied from customer messages.
- No execution of instructions embedded inside customer content.
- Clear separation between source content and system instructions.
- Useful output for short, vague, long, multilingual, and contradictory briefs.

Do not make external API calls in the normal automated test suite. Use fakes and recorded sanitized fixtures. Put live-provider tests behind an explicit opt-in command.

## 8. UI/UX direction from the reference screenshot

Use the attached screenshot only as visual inspiration. Create an original design and original QuoteFlow AI branding. Do not reproduce the Wazely logo, name, navigation labels, copy, exact layout, or proprietary assets.

### 8.1 Overall visual language

- Clean modern B2B SaaS dashboard.
- Bright neutral canvas with crisp white cards.
- Strong near-black/navy headings and muted blue-gray secondary text.
- Emerald/teal primary brand color, supported by subtle violet, blue, amber, rose, and mint status accents.
- Low visual noise, generous whitespace, compact information density, and clear hierarchy.
- Thin cool-gray borders and very subtle shadows; avoid heavy floating-card shadows and excessive glassmorphism.
- Rounded rectangles with a consistent modest radius, approximately 10–14px for cards and 8–10px for controls.
- Use one accessible sans-serif family with a distinctive but highly readable display weight. Prefer a self-hostable, commercially redistributable font or a system fallback. Do not depend on remote font loading.
- Use a consistent open-source icon set. Do not mix icon styles.

### 8.2 Application shell

- Desktop: fixed/collapsible left sidebar approximately 248px expanded and 76px collapsed.
- Sidebar contains original QuoteFlow AI wordmark, workspace-aware grouped navigation, active item with pale mint background, small section labels, and accessible tooltips when collapsed.
- Sticky top bar with global search/command palette, workspace switcher, notifications, theme toggle, locale/currency context, and user menu.
- Main content uses a wide responsive canvas with approximately 24–32px desktop padding.
- Tablet and mobile: sidebar becomes an accessible drawer; primary actions remain reachable; tables adapt to cards or controlled horizontal scrolling.
- Respect keyboard navigation, focus management, skip links, reduced motion, and screen-reader labels.

### 8.3 Dashboard composition

- Page heading and one-line supporting description.
- Prominent emerald welcome/action card using a subtle geometric grid or abstract pattern created in CSS/SVG, not copied imagery.
- Primary workflow card for creating a quotation or launching the AI Copilot.
- Plan/usage card visible only where relevant.
- Responsive KPI grid with softly tinted icon tiles, strong numeric values, concise labels, and optional progress bars.
- Recent activity and action-required sections below metrics.
- Avoid filling the dashboard with vanity data.

### 8.4 Component system

Build reusable, documented components for:

- App shell, sidebar groups/items, top bar, command palette, breadcrumbs, page header.
- Buttons, icon buttons, badges, alerts, toasts, dropdowns, tabs, modals, drawers, tooltips, popovers.
- Inputs, currency fields, percentage fields, date pickers, searchable selects, tag inputs, file uploads, rich-text/structured text editor.
- Cards, KPI cards, usage cards, timeline, activity feed, empty states, skeletons, error states.
- Tables with search, filters, sorting, pagination, row selection, responsive behavior, and bulk actions.
- Quotation line editor, optional-item selector, totals summary, status timeline, AI suggestion panel, AI risk badge, and public quotation sections.

Every component must support light and dark modes. Dark mode must be intentionally designed, not an inverted afterthought. Store preference and also respect the system preference initially.

### 8.5 Design tokens

Define semantic CSS variables/Tailwind tokens rather than scattering color literals:

- `background`, `surface`, `surface-muted`, `surface-elevated`.
- `foreground`, `foreground-muted`, `foreground-subtle`.
- `border`, `border-strong`, `ring`.
- `primary`, `primary-hover`, `primary-soft`, `primary-foreground`.
- `success`, `warning`, `danger`, `info` and their soft variants.
- A documented spacing scale, typography scale, radius scale, shadow scale, control heights, sidebar widths, and content max widths.

Meet WCAG 2.1 AA contrast requirements. Never communicate status by color alone.

### 8.6 Key screens to design and verify

- Login, registration, forgot/reset password, 2FA.
- Workspace onboarding.
- Main dashboard.
- Customer list/details/form/import.
- Service catalogue.
- Quotation list and filters.
- Quotation builder with desktop and mobile behavior.
- AI brief modal/full page and generated draft review.
- Scope Guardian side panel with severity and apply/dismiss controls.
- Quotation preview and revision history.
- Public customer quotation on mobile and desktop.
- Approval/rejection/revision-request flows.
- Invoice, payment, and receipt screens.
- Requirements/handover portal.
- Templates.
- Workspace team, branding, tax, email, payment, AI, and storage settings.
- Subscription/usage page.
- Super-admin dashboard and tenant details.
- Installer and system-requirements checker.
- 403, 404, 419, 429, 500, maintenance, empty, loading, and offline/error states.

Use browser screenshots at desktop, tablet, and mobile widths to review the implementation against `docs/UI_SYSTEM.md`. Fix overflow, clipping, weak hierarchy, inconsistent spacing, inaccessible focus, and unreadable dark-mode states before marking a screen complete.

## 9. Navigation information architecture

Keep navigation smaller and clearer than the reference application.

Suggested workspace navigation:

- Overview
  - Dashboard
- Sales
  - Quotations
  - Customers
  - Products & Services
  - Templates
- Finance
  - Invoices
  - Payments
- Delivery
  - Requirements & Handover
- Intelligence
  - AI Copilot
  - Scope Reviews
  - AI Usage
- Reports
- Team
- Settings
  - Business Profile
  - Branding
  - Taxes & Currency
  - Email
  - Payments
  - AI Providers
  - Storage
  - Security

Super-admin navigation must be separate and must not appear to normal workspace members.

## 10. Data model

Design and document an ER diagram before finalizing migrations. At minimum consider:

- users
- workspaces
- workspace_memberships
- roles/permissions or an equivalent permission model
- invitations
- customers
- customer_contacts
- tags and taggables
- service_categories
- services
- quotations
- quotation_revisions
- quotation_sections
- quotation_items
- quotation_optional_selections
- quotation_events
- quotation_comments
- quotation_acceptances
- quotation_public_tokens
- templates and template versions
- invoices
- invoice_items
- payments
- payment_provider_events
- receipts
- requirement_lists
- requirement_items
- uploads/attachments
- plans
- plan_features/entitlements
- subscriptions
- usage_ledgers
- ai_provider_configs
- ai_requests
- ai_usage_records
- ai_prompt_versions
- ai_scope_reviews
- notifications
- audit_logs
- system_settings

Use ULIDs or UUIDs for public-facing identifiers where useful while keeping efficient indexed internal keys if appropriate. Define foreign keys, cascade/restrict behavior, unique constraints, indexes, retention behavior, and monetary precision deliberately.

## 11. Security and privacy requirements

- Follow Laravel security conventions and OWASP guidance.
- Validate all input server-side and authorize every action.
- Escape output by default; sanitize any permitted rich text with an allowlist.
- CSRF protection for state-changing browser actions.
- Rate limits for authentication, public quotation actions, AI generation, contact forms, uploads, exports, and payment endpoints.
- Secure session cookies, session rotation, logout invalidation, and production HTTPS enforcement guidance.
- Encrypt secrets and sensitive integration settings at rest.
- Never log passwords, API keys, payment details, signed links, complete AI prompts containing sensitive data, or authorization headers.
- File upload allowlist, MIME/content validation, randomized storage names, size limits, tenant namespaces, and no executable public uploads.
- Signed URLs must be scoped, expiring where appropriate, revocable, and safe from token leakage.
- Webhook signature verification, idempotency, replay protection, and audit logs.
- Content Security Policy strategy compatible with the application.
- Protect against mass assignment, SQL injection, XSS, broken access control, IDOR, SSRF in configurable webhooks/URLs, formula injection in CSV exports, and prompt injection in AI source content.
- Add security-focused tests for critical controls.
- Provide privacy tools for customer/workspace export and deletion, with documented retention exceptions for financial/audit records.

## 12. Performance and reliability

- Prevent N+1 queries and add query-count awareness to important listings/dashboard tests.
- Paginate all unbounded collections.
- Cache safe reference/configuration data with tenant-aware keys and correct invalidation.
- Queue emails, PDFs, exports, bulk imports, reminders, and longer AI operations.
- Jobs must be idempotent and have sensible timeout, retry, backoff, and failed-job behavior.
- Provide queue and scheduler health information to administrators.
- Add database indexes based on real query patterns.
- Use lazy loading/code splitting only where supported and valuable.
- Target good Core Web Vitals for public quotation pages.

## 13. Localization, currencies, dates, and accessibility

- Translation-ready UI using framework localization files; no hard-coded user-facing strings in core components.
- English included; architecture ready for Arabic RTL.
- Workspace timezone and locale-aware display; store timestamps consistently in UTC.
- ISO currency codes and decimal precision rules. Never assume every currency has two decimal places.
- Do not perform automatic cross-currency totals without configured exchange rates.
- Use semantic HTML, visible focus, correct labels, error associations, keyboard operation, reduced motion, and accessible status announcements.
- Test core flows with keyboard-only navigation and automated accessibility checks where feasible.

## 14. Installer, updater, demo, and CodeCanyon packaging

Build distribution features as product features, not last-minute scripts:

- Web installer with server-requirement checks, writable-directory checks, database connection test, application key generation, migration, first admin creation, operating-mode selection, queue/scheduler guidance, and installation lock.
- CLI installation path for advanced users.
- Do not expose stack traces or secrets on installer failures.
- Provide a safe update mechanism with versioned migrations, preflight checks, maintenance mode, backup warning, and rollback guidance. Do not promise automatic rollback for irreversible database migrations.
- License/purchase-code verification must be modular and must never lock customers out of their own data when the licensing service is unavailable. Do not add obfuscated backdoors, destructive remote controls, or hidden telemetry.
- Demo mode must reset safely, disable destructive/integration changes, hide secrets, and rate-limit abuse.
- Prepare a clean distributable ZIP structure with source, compiled assets, documentation, license notices, and no development secrets, tests containing sensitive fixtures, node_modules, local caches, or unnecessary files.
- Create public HTML documentation suitable for beginners: requirements, installation, cron/queue configuration, SMTP, payments, OpenAI configuration, AI privacy, backups, updates, troubleshooting, customization, and support scope.
- Track third-party packages, fonts, icons, and sample assets with licenses and attribution requirements.

## 15. WordPress connector — post-core phase

After the Laravel MVP is stable, create a separately packaged **QuoteFlow Connector** WordPress plugin:

- Secure settings screen for QuoteFlow base URL and generated API credentials.
- Request-a-quote form block/shortcode.
- Elementor widget only if it can be implemented cleanly without making Elementor mandatory.
- Submit leads/customer briefs to QuoteFlow through a versioned API.
- Webhook signature validation and retry-safe delivery.
- Connection test, status, logs without secrets, and uninstall behavior.
- Follow WordPress coding standards, capability checks, nonce validation, sanitization, escaping, prepared queries, i18n, and privacy requirements.
- Do not bundle the Laravel application inside WordPress.

## 16. API and integration principles

- Version external APIs from the beginning.
- Use scoped, revocable credentials with hashed tokens where possible.
- Apply rate limits and audit integration activity.
- Return consistent JSON errors and validation details.
- Document APIs used by the WordPress connector.
- Do not expose internal numeric IDs unnecessarily.
- Build payment, AI, mail, PDF, storage, and future WhatsApp providers behind clear interfaces.

## 17. Testing requirements

Maintain a layered test suite:

- Unit tests for money, tax, discounts, totals, state transitions, entitlement rules, AI response mapping, and token/usage calculations.
- Feature tests for authentication, onboarding, customers, quotations, revisions, public links, optional items, approval, invoices, payments, plans, and settings.
- Policy tests for every role and important resource.
- Tenant-isolation tests across HTTP, jobs, exports, notifications, storage, signed links, and API access.
- Integration tests with fake payment and AI providers.
- Browser/end-to-end tests for quotation creation, AI draft review, scope review, public approval, deposit flow, and mobile navigation.
- Snapshot or PDF content tests where stable, plus visual inspection of representative PDFs.
- Accessibility checks for major screens.

Every bug fix should include a regression test when practical.

Required release checks should include the project equivalents of:

- PHP formatting.
- Static analysis.
- Complete automated test suite.
- Frontend linting where configured.
- Production asset build.
- Database migration test from an empty database.
- Installer smoke test.
- Queue/scheduler smoke test.
- Security review of dependencies and application diff.
- Manual responsive review of critical pages.

## 18. Seed/demo scenarios

Create realistic, clearly fictional demo data for:

- A web agency preparing a bilingual restaurant website quotation.
- A photographer preparing an event package with optional albums.
- A contractor preparing a renovation quotation with milestones.
- A consultant preparing a monthly retainer proposal.

The web-agency scenario should demonstrate the AI Scope Guardian finding:

- unspecified translation responsibility;
- unclear number of revisions;
- hosting mentioned but not priced;
- missing content-delivery deadline;
- unclear reservation-payment requirement.

## 19. MVP acceptance criteria

The MVP is complete only when all of the following are demonstrably true:

1. A new installation can pass requirements, install, and create the first admin without manual code edits.
2. Single-business and SaaS modes both work from the same codebase.
3. Two workspaces cannot access or infer each other's records through UI, API, public links, jobs, exports, storage paths, or guessed identifiers.
4. A workspace owner can onboard, invite a member, add a customer and service, and configure branding/tax/payment instructions.
5. An authorized user can create a quotation manually with correct decimal totals, optional items, tax, discount, milestones, deposit, and terms.
6. A user can paste a customer brief, receive a schema-valid AI draft, review it, edit it, and explicitly save it. No final prices are fabricated.
7. Scope Guardian returns structured findings and the user can apply or dismiss each suggestion.
8. The application remains usable when AI is disabled, misconfigured, rate-limited, or unavailable.
9. A customer can open a secure mobile-friendly link, select optional items, request changes, approve/reject, and download the stable quotation PDF.
10. Approval creates a tamper-resistant snapshot and audit event.
11. An approved quotation can become a deposit/full invoice, accept a manual payment, and generate a receipt.
12. Email notifications are queued and WhatsApp share links generate correct professional messages.
13. Plan entitlements and AI usage limits are enforced server-side.
14. Major screens match the original QuoteFlow AI design system in both light and dark modes at mobile, tablet, and desktop widths.
15. Critical flows meet the accessibility requirements and have visible loading, empty, validation, success, and error states.
16. Automated tests, static analysis, formatting, and production builds pass.
17. Documentation allows a beginner buyer to install and configure the product.
18. The distributable package contains no secrets, copied branding, unlicensed assets, development debris, or hidden dependencies.

## 20. Explicit non-goals for MVP

Do not expand into these areas unless I explicitly approve a scoped change:

- Full CRM with pipelines and marketing automation.
- Inventory, POS, payroll, HR, or complete accounting.
- Native iOS or Android apps.
- Automatic AI pricing or autonomous sending.
- Regulated/qualified electronic signatures.
- Credential/password vault.
- Official WhatsApp Cloud API automation.
- Dozens of payment gateways.
- Marketplace functionality.
- Complex workflow builder.
- AI chatbot unrelated to quotations.

## 21. First execution request

Begin now with **Discovery and Phase 0 only**:

1. Inspect the environment and reference screenshot.
2. Ask only blocking product/technical questions.
3. Propose the exact versions and packages, with reasons and commercial license considerations.
4. Produce the initial product specification, architecture, data model outline, UI token proposal, threat model summary, phased implementation plan, and `AGENTS.md`.
5. Define Phase 1 acceptance criteria and verification commands.
6. Stop for my review before generating the main application scaffold if any proposed architectural decision would be expensive to reverse. Otherwise, proceed to scaffold Phase 1 after clearly recording the decisions.

Maintain a professional standard throughout. Challenge requirements that create unnecessary complexity, weak security, poor UX, CodeCanyon support problems, or unclear commercial value. When offering alternatives, recommend one and explain the tradeoff concisely.

## MASTER PROMPT END

---

## Recommended way to use this prompt

1. Create an empty folder named `quoteflow-ai` and initialize Git.
2. Place the reference screenshot at `docs/references/dashboard-ui-reference.png`, or attach it directly in Codex.
3. Start Codex from the repository root.
4. Use Plan mode first and paste the master prompt.
5. Keep the same Codex conversation for related phases so decisions remain connected.
6. Review each phase before allowing major architectural changes.

The product name, supported gateways, and precise framework versions should remain adjustable until Phase 0 is approved.
