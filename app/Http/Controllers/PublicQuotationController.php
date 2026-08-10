<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\PdfRenderer;
use App\Domain\Quotations\MoneyCalculator;
use App\Enums\QuotationStatus;
use App\Models\QuotationAcceptance;
use App\Models\QuotationComment;
use App\Models\QuotationEvent;
use App\Models\QuotationOptionalSelection;
use App\Models\QuotationPublicToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class PublicQuotationController extends Controller
{
    public function __construct(private readonly MoneyCalculator $calculator) {}

    public function show(string $token): View
    {
        $access = $this->resolve($token);
        DB::transaction(function () use ($access): void {
            $access->update(['last_accessed_at' => now()]);
            if ($access->quotation->status === QuotationStatus::Sent) {
                $access->quotation->update(['status' => QuotationStatus::Viewed]);
                $this->event($access, 'quotation.public_viewed');
            }
        });

        return view('public.quotation', ['access' => $access, 'document' => $access->quotation->acceptance?->snapshot ?? $this->document($access), 'token' => $token]);
    }

    public function select(Request $request, string $token): RedirectResponse
    {
        $access = $this->resolve($token);
        $this->ensureOpen($access);
        $data = $request->validate(['items' => ['nullable', 'array'], 'items.*' => ['integer']]);
        $selected = collect($data['items'] ?? [])->map(fn ($id) => (int) $id)->all();
        DB::transaction(function () use ($access, $selected): void {
            foreach ($access->revision->items->where('is_optional', true) as $item) {
                QuotationOptionalSelection::query()->updateOrCreate(
                    ['quotation_public_token_id' => $access->id, 'quotation_item_id' => $item->id],
                    ['workspace_id' => $access->workspace_id, 'quotation_revision_id' => $access->quotation_revision_id, 'is_selected' => in_array($item->id, $selected, true)],
                );
            }
            $this->event($access, 'quotation.optional_items_updated');
        });

        return back()->with('status', 'Your selections have been saved.');
    }

    public function comment(Request $request, string $token): RedirectResponse
    {
        $access = $this->resolve($token);
        $this->ensureOpen($access);
        $data = $request->validate(['author_name' => ['required', 'string', 'max:120'], 'author_email' => ['nullable', 'email', 'max:255'], 'message' => ['required', 'string', 'max:3000']]);
        QuotationComment::query()->create(['workspace_id' => $access->workspace_id, 'quotation_id' => $access->quotation_id, 'quotation_revision_id' => $access->quotation_revision_id, 'quotation_public_token_id' => $access->id, ...$data]);
        $this->event($access, 'quotation.public_comment');

        return back()->with('status', 'Your comment has been shared.');
    }

    public function requestRevision(Request $request, string $token): RedirectResponse
    {
        $access = $this->resolve($token);
        $this->ensureOpen($access);
        $data = $request->validate(['author_name' => ['required', 'string', 'max:120'], 'message' => ['required', 'string', 'max:3000']]);
        DB::transaction(function () use ($access, $data): void {
            QuotationComment::query()->create(['workspace_id' => $access->workspace_id, 'quotation_id' => $access->quotation_id, 'quotation_revision_id' => $access->quotation_revision_id, 'quotation_public_token_id' => $access->id, 'author_name' => $data['author_name'], 'message' => $data['message']]);
            $access->quotation->update(['status' => QuotationStatus::RevisionRequested]);
            $this->event($access, 'quotation.public_revision_requested');
        });

        return back()->with('status', 'Your revision request has been sent.');
    }

    public function decide(Request $request, string $token): RedirectResponse
    {
        $access = $this->resolve($token);
        $this->ensureOpen($access);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'printed_name' => ['required', 'string', 'max:120'],
            'terms_accepted' => ['required_if:decision,approved', 'accepted'],
            'reason' => ['nullable', 'string', 'max:3000'],
        ]);
        DB::transaction(function () use ($request, $access, $data): void {
            $document = $this->document($access);
            $snapshot = [...$document, 'decision' => ['value' => $data['decision'], 'printed_name' => $data['printed_name'], 'reason' => $data['reason'] ?? null, 'decided_at' => now()->toIso8601String()]];
            $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            QuotationAcceptance::query()->create([
                'workspace_id' => $access->workspace_id, 'quotation_id' => $access->quotation_id, 'quotation_revision_id' => $access->quotation_revision_id,
                'quotation_public_token_id' => $access->id, 'decision' => $data['decision'], 'printed_name' => $data['printed_name'],
                'terms_accepted' => $data['decision'] === 'approved', 'reason' => $data['reason'] ?? null, 'snapshot' => $snapshot,
                'snapshot_hash' => hash('sha256', $json), 'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), config('app.key')) : null,
                'user_agent_hash' => $request->userAgent() ? hash('sha256', $request->userAgent()) : null, 'decided_at' => now(),
            ]);
            $status = $data['decision'] === 'approved' ? QuotationStatus::Approved : QuotationStatus::Rejected;
            $access->quotation->update(['status' => $status]);
            $access->quotation->publicTokens()->whereKeyNot($access->id)->update(['revoked_at' => now()]);
            $this->event($access, 'quotation.public_'.$data['decision']);
        });

        return back()->with('status', $data['decision'] === 'approved' ? 'Quotation approved. Thank you.' : 'Your decision has been recorded.');
    }

    public function pdf(string $token, PdfRenderer $renderer): Response
    {
        $access = $this->resolve($token);
        $document = $access->quotation->acceptance?->snapshot ?? $this->document($access);
        $html = view('public.pdf', compact('document'))->render();

        return response($renderer->render($html), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$access->quotation->number.'.pdf"']);
    }

    private function resolve(string $raw): QuotationPublicToken
    {
        abort_unless(strlen($raw) === 96 && ctype_xdigit($raw), 404);
        $access = QuotationPublicToken::query()->with(['quotation.customer', 'quotation.acceptance', 'revision.items', 'revision.sections', 'selections', 'comments'])->where('token_hash', hash('sha256', $raw))->firstOrFail();
        abort_unless($access->isUsable(), 410, 'This quotation link has expired or was revoked.');

        return $access;
    }

    private function ensureOpen(QuotationPublicToken $access): void
    {
        abort_unless(in_array($access->quotation->status, [QuotationStatus::Sent, QuotationStatus::Viewed], true), 409, 'This quotation is no longer open for changes.');
    }

    private function document(QuotationPublicToken $access): array
    {
        $selectionMap = $access->selections->pluck('is_selected', 'quotation_item_id');
        $items = $access->revision->items->map(function ($item) use ($selectionMap): array {
            $selected = ! $item->is_optional || (bool) $selectionMap->get($item->id, false);

            return [...$item->toArray(), 'is_selected' => $selected];
        });
        $totals = $this->calculator->calculate($items->map(fn ($item) => ['quantity' => (string) $item['quantity'], 'unit_price_minor' => $item['unit_price_minor'], 'tax_rate_basis_points' => $item['tax_rate_basis_points'], 'is_optional' => $item['is_optional'], 'is_selected' => $item['is_selected']])->all(), $access->revision->tax_mode, $access->revision->discount_type, $access->revision->discount_value, $access->revision->deposit_percentage);

        return ['quotation' => $access->quotation->only(['number', 'currency', 'reference', 'issue_date', 'expiry_date', 'status']), 'customer' => $access->quotation->customer->only(['name', 'email']), 'revision' => $access->revision->only(['revision_number', 'title', 'introduction', 'terms', 'exclusions', 'client_responsibilities']), 'items' => $items->values()->all(), 'totals' => collect($totals)->except('lines')->all()];
    }

    private function event(QuotationPublicToken $access, string $event): void
    {
        QuotationEvent::query()->create(['workspace_id' => $access->workspace_id, 'quotation_id' => $access->quotation_id, 'quotation_revision_id' => $access->quotation_revision_id, 'event' => $event, 'metadata' => []]);
    }
}
