# QuoteFlow AI Threat Model

## Assets and trust boundaries

Protect workspace/customer content, financial documents, acceptance evidence, payment state, public tokens, uploads, API/provider credentials, AI inputs, usage/plan data, audit history, and platform operations. Trust boundaries exist at browser/server, workspace/platform, public-link/authenticated UI, queue serialization, filesystem/object storage, mail, AI, payment providers, webhooks, installer/updater, and future WordPress API.

## Principal threats and controls

- Tenant leakage/IDOR: authenticated context, policies, scoped queries, ULID public IDs, isolation tests, tenant cache/storage namespaces, audited support access.
- Account takeover/privilege escalation: rate limits, verification, optional 2FA, session rotation/invalidation, expiring invitations, permission policies, self-protection rules, security audit events.
- Signed-link leakage/abuse: opaque revocable tokens stored hashed, expiry, throttling, no indexing, restrictive referrer policy, no third-party analytics, token rotation.
- Quotation/payment tampering: server-side integer money calculations, immutable revision snapshots and hashes, legal transition rules, idempotent conversion and payments.
- Webhook forgery/replay: raw-body signature verification, timestamp tolerance, unique provider event IDs, durable receipt, idempotent processing, reconciliation.
- Upload attack: allowlisted MIME/content checks, size/count limits, randomized tenant paths, private storage, download authorization, scanning hook, no executable public files.
- XSS/CSV injection: escaped output, allowlist sanitizer, CSP, formula neutralization, safe template variables.
- Secret/privacy leakage: encrypted settings, masked UI, redacted logs/AI payloads, no credentials in custom fields, retention/export/anonymization controls.
- AI prompt injection/fabrication: untrusted-content delimiters, strict schemas, server validation, preserved financial facts, human apply step, deterministic adversarial evals.
- Installer/update compromise: install lock, authorization, preflight, no stack traces/secrets, backup warning, signed/versioned release process, maintenance state.
- SSRF: outbound URL allowlists and resolved-address checks for configurable callbacks/connectors; block private/link-local metadata targets.
- Resource exhaustion: pagination, quotas, upload/export/AI rate limits, bounded jobs, timeouts, backoff, cancellation, plan enforcement.

Security review is a phase gate, not a final-phase-only activity.
