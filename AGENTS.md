# QuoteFlow AI Repository Guide

## Product boundaries

- Build a production-quality, self-hosted quotation-to-approval product for CodeCanyon.
- One codebase supports `single_business` and `saas` modes. Both are first-class release targets.
- Do not add CRM pipelines, inventory, payroll, native apps, autonomous AI pricing/sending, credential storage, or official WhatsApp automation without approval.
- AI is optional and assistive. Core quotation, approval, invoice, and payment workflows must work without it.

## Architecture rules

- Use PHP 8.3+, Laravel 13, Blade, Livewire 4, Alpine bundled by Livewire, Tailwind CSS 4, Vite, MySQL 8+/MariaDB-compatible SQL, and Pest 4.
- Use strict types where practical, enums for states, DTOs at service boundaries, Form Requests for validation, policies for authorization, actions/services for use cases, and transactions for multi-record changes.
- Every tenant-owned record and query is workspace-scoped. Resolve workspace context from authenticated membership, never request input.
- Use integers plus ISO currency metadata for money; never binary floats. Preserve immutable sent/accepted snapshots.
- Queue slow work. Jobs must carry workspace context, be idempotent, and define retries/backoff.
- Keep payment, AI, PDF, mail, and storage behind contracts. Never call vendor SDKs from controllers or Livewire components.

## UI and accessibility

- Follow `docs/UI_SYSTEM.md`; do not copy the reference product's branding, content, or exact layout.
- All components support light/dark modes, keyboard access, visible focus, reduced motion, semantic labels, and WCAG 2.1 AA contrast.
- Never use fake dashboard metrics outside explicit demo mode. Implement loading, empty, forbidden, partial-error, and failure states.

## Security and quality gates

- Validate and authorize every state change. Add policy and tenant-isolation tests with each protected resource.
- Never log secrets, signed URLs, authorization headers, payment data, or sensitive full AI prompts.
- Do not commit `.env`, credentials, generated uploads, vendor dependencies, build caches, or distributable archives.
- Do not claim completion until formatting, static analysis, tests, production assets, migrations, and relevant browser checks pass.
- Update `docs/IMPLEMENTATION_PLAN.md` and `CHANGELOG.md` at the end of each phase. Do not make commits unless requested.

## Intended commands after Phase 1 scaffold

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
