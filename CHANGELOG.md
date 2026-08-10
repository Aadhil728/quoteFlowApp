# Changelog

All notable changes follow [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). This project uses semantic versioning after the first release.

## [Unreleased]

### Added

- Phase 0 product, architecture, UI, AI, testing, threat-model, and implementation documentation.
- Repository engineering rules and safe environment-variable template.
- Recorded dual-mode, three-payment-provider, and shared-hosting/VPS deployment decisions.
- Local Composer bootstrap under the ignored `.tools` directory for Phase 1 preparation.
- Phase 1 local database preparation using the `quoteflow` UTF-8 development database.
- Laravel 13.24 foundation with Livewire 4, Tailwind CSS 4, Vite 8, Pest 4, and Larastan.
- Web and CLI installers with server preflight, dual-mode selection, and atomic first-administrator/workspace creation.
- Registration, login/logout, email verification, password recovery, session hardening, and optional two-factor storage foundation.
- Tenant-safe workspace context and switching, centralized role permissions, audit records, and isolation tests.
- Original responsive QuoteFlow dashboard shell with light/dark tokens, accessible navigation, real empty states, and no remote font dependency.
- Queue and scheduler foundations with a verified scheduler heartbeat.
- Workspace business profile defaults for legal identity, contact details, tax, currency, timezone, locale, quotation prefix, validity, payment instructions, and brand color.
- Permission-aware team screen with hashed seven-day invitation tokens, invited-email acceptance, role assignment, and member deactivation safeguards.
- Tenant-scoped customer management with contacts, activity history, search, filtering, pagination, soft archive, duplicate-aware CSV import, and formula-safe CSV export.
- Tenant-scoped products and services catalogue with SKU validation, categories, integer-minor-unit pricing, tax behavior, search, pagination, and deactivation.
- Phase 2 policy and isolation coverage, bringing the suite to 14 tests and 49 assertions.
- Softer application typography capped at 600 weight, with controls and labels reduced to 500.
- Phase 3 quotation persistence with tenant-scoped ULIDs, revisions, sections, line items, events, template versions, and explicit workflow states.
- Integer-minor-unit quotation calculator with decimal quantities, half-up rounding, inclusive/exclusive tax, fixed or percentage discounts, optional selections, and deposit totals.
- Responsive quotation builder with dynamic line items, autosave state, server-authoritative totals, internal preview, revision cloning, and locked SHA-256 sent snapshots.
- Contract-backed downloadable PDF generation using the shared-hosting-compatible Dompdf adapter with remote and PHP execution disabled.
- Three original versioned quotation layouts—Essential, Studio, and Signature—with tenant provisioning, preview, and duplication.
- Phase 3 isolation, workflow, snapshot, revision, PDF, template, and monetary tests, bringing the suite to 22 tests and 95 assertions.
- Application-wide UI refresh inspired by the supplied reference: lighter 400–600 typography, compact navigation, consistent custom stroke icons, refined toolbars and forms, softer surfaces, and responsive density.
- Icon-only, accessible row actions with tooltips for customer, catalogue, and quotation directories, including view, edit, and PDF download actions.
- Phase 4 secure client quotation links with high-entropy opaque values, stored token hashes, expiry, rotation, revocation, and tenant/revision binding.
- Mobile-first public quotation experience with optional item selection, authoritative recalculation, comments, revision requests, approve/reject flows, and stable PDF download.
- Immutable acceptance/rejection snapshots with content hashes, printed-name evidence, explicit terms acceptance, decision timestamps, and privacy-conscious request evidence.
- Public-document protections including noindex/noarchive, no-referrer, no-store caching, frame denial, and per-token/IP throttling.
- Phase 4 public-link, selection, comment, revision, acceptance, PDF, rotation, revocation, and rate-limit coverage, bringing the suite to 30 tests and 159 assertions.
