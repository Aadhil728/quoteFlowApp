# QuoteFlow AI System

## Safety contract

AI drafts and reviews; humans decide. It cannot send quotations, set confirmed prices, approve/reject, change invoices/payments, or trigger external sharing. Every applied suggestion is an explicit authorized action. Core workflows remain usable when AI is disabled or unavailable.

## Provider design

`AiProvider` receives a versioned task definition, separated trusted instructions and untrusted source content, a strict JSON Schema, privacy-safe user/workspace safety identifier, and runtime options. It returns validated structured output plus provider/model/request/usage metadata. `OpenAiProvider` uses the Responses API; controllers and Livewire components depend only on application actions.

The model is administrator-configurable. Start evaluation with a balanced current model rather than hard-coding a permanent default. Requests use `store: false`; API keys are encrypted at rest, masked after save, and never sent to the browser or logs.

## Tasks and schemas

- Brief draft: title, summary, sections, catalogue matches with confidence, unpriced items, milestones, deliverables, questions, assumptions, exclusions, responsibilities, risks, and suggested deposit percentage.
- Scope review: finding ID, severity, category, explanation, affected stable element ID, and proposed correction.
- Rewrite/translation: rewritten content plus immutable-fact preservation report and warnings.
- Follow-up: status-specific subject/body/channel draft and review warnings.
- Summary: concise client-facing summary and warnings.

Schemas reject additional properties and require all declared fields. Server-side DTO validation, length limits, enum validation, stable-ID checks, financial fact comparison, and explicit user confirmation are required after provider validation.

## Execution and failure handling

Long calls are queued with tenant context, deduplication key, timeout, bounded retries, cancellation, and visible states: queued, running, completed, refused, invalid, quota_exceeded, provider_unavailable, cancelled. Retries cannot create duplicate quotation drafts or usage charges in the local ledger.

Failures preserve user input and offer retry or manual continuation. Refusals and malformed responses are not coerced into content. Provider usage is recorded separately from estimated cost; quotas use a transactionally updated workspace ledger and centralized entitlements.

## Privacy and prompt-injection controls

- Minimize and redact email, phone, address, credentials, tokens, and unrelated identifiers before transmission when feasible.
- Delimit customer content as untrusted data and explicitly prohibit following embedded instructions.
- Never request or store chain-of-thought. Store prompt version, sanitized inputs or hashes according to retention settings, structured result, model/provider metadata, latency, and usage.
- Support configurable retention, deletion, BYOK, platform-managed keys, warnings, monthly resets, soft warnings, and hard caps.

## Evaluation

Normal tests use a fake provider and sanitized fixtures; live calls require an explicit opt-in command and separate credentials. Fixtures cover agencies, photographers, contractors, consultants, repair, printing, short/vague/long/Arabic/contradictory briefs, and malicious embedded instructions.

Gates measure schema validity, explicit requirement preservation, absence of confirmed fabricated prices, missing-scope detection, injection resistance, stable fact preservation, useful fallbacks, latency, and token/cost budgets. The web-agency fixture must detect translation ownership, revision count, unpriced hosting, content deadline, and reservation-payment ambiguity.
