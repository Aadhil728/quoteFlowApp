# QuoteFlow AI Testing Strategy

## Layers

- Unit: money/exponents, tax modes, discounts, rounding, totals, state machines, entitlements, token accounting, schema mapping, and canonical snapshot hashing.
- Feature: authentication/onboarding, workspace context, members, customers/import/export, catalogue, quotations/revisions/public links, invoices, payments, requirements, settings, installer, and updates.
- Policy matrix: every role/resource/action, including inactive membership and time-boxed support access.
- Isolation: cross-workspace reads/writes, guessed identifiers, public links, jobs, cache, storage, notifications, exports, APIs, and webhook events.
- Integration: fake AI, mail, storage, PDF, Stripe, and PayPal adapters; signature and idempotency fixtures.
- Browser: manual and AI-assisted quotation creation, scope review, public optional-item/approval flow, payment flow, mobile navigation, light/dark mode, accessibility, and screenshots.
- Packaging: clean install, upgrade preflight, ZIP allowlist, license inventory, secret scan, and beginner documentation smoke test.

## Required checks

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

CI uses MySQL/MariaDB for database-sensitive behavior; SQLite may be used only for genuinely database-agnostic unit tests. External AI/payment calls are forbidden in the default suite. Each bug receives a regression test where practical.

## Release evidence

Record command, environment, result, and relevant screenshot/report for each release gate. Manually review representative PDFs and responsive widths 1440, 1024, 768, and 390px. A passing build cannot replace hydrated browser checks, authorization tests, or tenant-isolation evidence.
