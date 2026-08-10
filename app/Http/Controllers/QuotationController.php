<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\PdfRenderer;
use App\Domain\Quotations\MoneyCalculator;
use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationEvent;
use App\Models\QuotationItem;
use App\Models\QuotationPublicToken;
use App\Models\QuotationRevision;
use App\Models\Service;
use App\Support\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class QuotationController extends Controller
{
    public function __construct(private readonly MoneyCalculator $calculator) {}

    public function index(Request $request, WorkspaceContext $context): View
    {
        $quotations = Quotation::query()->with(['customer', 'currentRevision'])->where('workspace_id', $context->id())
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($query) => $query->where('number', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%")))
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))->latest()->paginate(15)->withQueryString();

        return view('quotations.index', ['quotations' => $quotations, 'statuses' => QuotationStatus::cases()]);
    }

    public function create(WorkspaceContext $context): View
    {
        return view('quotations.builder', $this->builderData(new Quotation, new QuotationRevision, $context));
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $data = $this->validated($request, $context);
        $quotation = DB::transaction(function () use ($request, $context, $data): Quotation {
            $workspace = $context->get();
            $workspace->newQuery()->whereKey($workspace->id)->lockForUpdate()->first();
            $sequence = Quotation::withTrashed()->where('workspace_id', $workspace->id)->whereYear('created_at', now()->year)->count() + 1;
            $quotation = Quotation::query()->create(['workspace_id' => $workspace->id, 'customer_id' => $data['customer_id'], 'created_by' => $request->user()->id, 'assigned_to' => $request->user()->id, 'number' => sprintf('%s-%d-%04d', $workspace->quotation_prefix, now()->year, $sequence), 'status' => QuotationStatus::Draft, 'currency' => strtoupper($data['currency']), 'reference' => $data['reference'] ?? null, 'issue_date' => $data['issue_date'], 'expiry_date' => $data['expiry_date'], 'last_saved_at' => now()]);
            $revision = QuotationRevision::query()->create(['workspace_id' => $workspace->id, 'quotation_id' => $quotation->id, 'revision_number' => 1, ...$this->revisionFields($data)]);
            $this->replaceItems($revision, $data['items'], $context);
            $this->recalculate($revision);
            $quotation->update(['current_revision_id' => $revision->id]);
            $this->event($quotation, $request, 'quotation.created');

            return $quotation;
        });

        return redirect()->route('quotations.edit', $quotation)->with('status', 'Quotation draft created.');
    }

    public function show(Quotation $quotation, WorkspaceContext $context): View
    {
        $this->guard($quotation, $context);
        $quotation->load(['customer', 'currentRevision.items', 'currentRevision.sections', 'revisions', 'events']);

        return view('quotations.show', compact('quotation'));
    }

    public function pdf(Quotation $quotation, WorkspaceContext $context, PdfRenderer $renderer): Response
    {
        $this->guard($quotation, $context);
        $quotation->load(['customer', 'currentRevision.items', 'currentRevision.sections']);
        $html = view('quotations.pdf', compact('quotation'))->render();

        return response($renderer->render($html), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$quotation->number.'.pdf"',
        ]);
    }

    public function edit(Quotation $quotation, WorkspaceContext $context): View
    {
        $this->guard($quotation, $context);
        $revision = $quotation->currentRevision()->with('items')->firstOrFail();
        abort_if($revision->isLocked(), 409, 'Locked revisions cannot be edited.');

        return view('quotations.builder', $this->builderData($quotation, $revision, $context));
    }

    public function update(Request $request, Quotation $quotation, WorkspaceContext $context): RedirectResponse|JsonResponse
    {
        $this->guard($quotation, $context);
        $revision = $quotation->currentRevision()->firstOrFail();
        abort_if($revision->isLocked(), 409, 'Locked revisions cannot be edited.');
        $data = $this->validated($request, $context);
        DB::transaction(function () use ($request, $quotation, $revision, $context, $data): void {
            $quotation->update(['customer_id' => $data['customer_id'], 'currency' => strtoupper($data['currency']), 'reference' => $data['reference'] ?? null, 'issue_date' => $data['issue_date'], 'expiry_date' => $data['expiry_date'], 'last_saved_at' => now()]);
            $revision->update($this->revisionFields($data));
            $this->replaceItems($revision, $data['items'], $context);
            $this->recalculate($revision);
            $this->event($quotation, $request, 'quotation.saved');
        });
        if ($request->expectsJson()) {
            return response()->json(['saved_at' => $quotation->fresh()->last_saved_at?->toIso8601String(), 'totals' => $revision->fresh()->only(['subtotal_minor', 'discount_minor', 'tax_minor', 'total_minor', 'deposit_minor'])]);
        }

        return back()->with('status', 'Quotation saved.');
    }

    public function transition(Request $request, Quotation $quotation, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($quotation, $context);
        $data = $request->validate(['status' => ['required', Rule::enum(QuotationStatus::class)]]);
        $target = QuotationStatus::from($data['status']);
        abort_unless($quotation->status->canTransitionTo($target), 422, 'Invalid quotation status transition.');
        DB::transaction(function () use ($request, $quotation, $target): void {
            if ($target === QuotationStatus::Sent) {
                $revision = $quotation->currentRevision()->with(['items', 'sections'])->firstOrFail();
                $snapshot = ['quotation' => $quotation->only(['number', 'currency', 'reference', 'issue_date', 'expiry_date']), 'customer' => $quotation->customer()->firstOrFail()->only(['name', 'email', 'phone', 'tax_id', 'billing_address']), 'revision' => $revision->only(['revision_number', 'title', 'introduction', 'notes', 'terms', 'exclusions', 'client_responsibilities', 'tax_mode', 'subtotal_minor', 'discount_minor', 'tax_minor', 'total_minor', 'deposit_minor']), 'items' => $revision->items->toArray(), 'sections' => $revision->sections->toArray()];
                $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $revision->update(['snapshot' => $snapshot, 'content_hash' => hash('sha256', $json), 'locked_at' => now()]);
            }
            $from = $quotation->status->value;
            $quotation->update(['status' => $target]);
            $this->event($quotation, $request, 'quotation.status_changed', ['from' => $from, 'to' => $target->value]);
        });

        return back()->with('status', 'Quotation status updated.');
    }

    public function revise(Request $request, Quotation $quotation, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($quotation, $context);
        abort_unless(in_array($quotation->status, [QuotationStatus::RevisionRequested, QuotationStatus::Rejected, QuotationStatus::Expired], true), 422);
        DB::transaction(function () use ($request, $quotation): void {
            $source = $quotation->currentRevision()->with(['items', 'sections'])->firstOrFail();
            $revision = $source->replicate(['snapshot', 'content_hash', 'locked_at']);
            $revision->revision_number = $quotation->revisions()->max('revision_number') + 1;
            $revision->save();
            $sectionMap = [];
            foreach ($source->sections as $section) {
                $copy = $section->replicate();
                $copy->quotation_revision_id = $revision->id;
                $copy->save();
                $sectionMap[$section->id] = $copy->id;
            }
            foreach ($source->items as $item) {
                $copy = $item->replicate();
                $copy->quotation_revision_id = $revision->id;
                $copy->quotation_section_id = $item->quotation_section_id ? ($sectionMap[$item->quotation_section_id] ?? null) : null;
                $copy->save();
            }
            $quotation->update(['current_revision_id' => $revision->id, 'status' => QuotationStatus::Draft, 'last_saved_at' => now()]);
            $this->event($quotation, $request, 'quotation.revised', ['revision' => $revision->revision_number]);
        });

        return redirect()->route('quotations.edit', $quotation)->with('status', 'New revision created.');
    }

    public function share(Request $request, Quotation $quotation, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($quotation, $context);
        $revision = $quotation->currentRevision()->firstOrFail();
        abort_unless($revision->isLocked() && in_array($quotation->status, [QuotationStatus::Sent, QuotationStatus::Viewed], true), 409, 'Send the quotation before creating a client link.');
        $raw = bin2hex(random_bytes(48));
        DB::transaction(function () use ($request, $quotation, $revision, $context, $raw): void {
            $quotation->publicTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $maximumExpiry = now()->addDays(30);
            $expiresAt = $quotation->expiry_date->endOfDay()->isBefore($maximumExpiry) ? $quotation->expiry_date->endOfDay() : $maximumExpiry;
            QuotationPublicToken::query()->create(['workspace_id' => $context->id(), 'quotation_id' => $quotation->id, 'quotation_revision_id' => $revision->id, 'created_by' => $request->user()->id, 'token_hash' => hash('sha256', $raw), 'expires_at' => $expiresAt]);
            $this->event($quotation, $request, 'quotation.public_link_created');
        });

        return back()->with('status', 'Secure client link created.')->with('public_url', route('public.quotation.show', $raw));
    }

    public function revokePublicLinks(Request $request, Quotation $quotation, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($quotation, $context);
        $quotation->publicTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        $this->event($quotation, $request, 'quotation.public_links_revoked');

        return back()->with('status', 'All client links have been revoked.');
    }

    private function validated(Request $request, WorkspaceContext $context): array
    {
        return $request->validate(['customer_id' => ['required', Rule::exists('customers', 'id')->where('workspace_id', $context->id())], 'currency' => ['required', 'string', 'size:3'], 'reference' => ['nullable', 'string', 'max:120'], 'issue_date' => ['required', 'date'], 'expiry_date' => ['required', 'date', 'after_or_equal:issue_date'], 'title' => ['required', 'string', 'max:180'], 'introduction' => ['nullable', 'string', 'max:5000'], 'notes' => ['nullable', 'string', 'max:5000'], 'terms' => ['nullable', 'string', 'max:10000'], 'exclusions' => ['nullable', 'string', 'max:5000'], 'client_responsibilities' => ['nullable', 'string', 'max:5000'], 'tax_mode' => ['required', 'in:exclusive,inclusive'], 'discount_type' => ['nullable', 'in:fixed,percentage'], 'discount_value' => ['nullable', 'integer', 'min:0', Rule::when($request->input('discount_type') === 'percentage', ['max:10000'])], 'deposit_percentage' => ['required', 'integer', 'between:0,100'], 'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.name' => ['required', 'string', 'max:180'], 'items.*.description' => ['nullable', 'string', 'max:2000'], 'items.*.quantity' => ['required', 'decimal:0,4', 'gt:0'], 'items.*.unit' => ['required', 'string', 'max:30'], 'items.*.unit_price_minor' => ['required', 'integer', 'min:0'], 'items.*.tax_rate_basis_points' => ['required', 'integer', 'between:0,10000'], 'items.*.is_optional' => ['sometimes', 'boolean'], 'items.*.is_selected' => ['sometimes', 'boolean']]);
    }

    private function revisionFields(array $data): array
    {
        return collect($data)->only(['title', 'introduction', 'notes', 'terms', 'exclusions', 'client_responsibilities', 'tax_mode', 'discount_type', 'discount_value', 'deposit_percentage'])->all();
    }

    private function replaceItems(QuotationRevision $revision, array $items, WorkspaceContext $context): void
    {
        $revision->items()->delete();
        $section = $revision->sections()->firstOrCreate(
            ['workspace_id' => $context->id(), 'title' => 'Scope'],
            ['description' => 'Products and services included in this quotation.', 'position' => 0],
        );
        foreach (array_values($items) as $position => $item) {
            QuotationItem::query()->create(['workspace_id' => $context->id(), 'quotation_revision_id' => $revision->id, 'quotation_section_id' => $section->id, 'name' => $item['name'], 'description' => $item['description'] ?? null, 'quantity' => $item['quantity'], 'unit' => $item['unit'], 'unit_price_minor' => $item['unit_price_minor'], 'tax_rate_basis_points' => $item['tax_rate_basis_points'], 'is_optional' => $item['is_optional'] ?? false, 'is_selected' => $item['is_selected'] ?? true, 'position' => $position]);
        }
    }

    private function recalculate(QuotationRevision $revision): void
    {
        $items = $revision->items()->orderBy('position')->get();
        $result = $this->calculator->calculate($items->map(fn ($item) => ['quantity' => (string) $item->quantity, 'unit_price_minor' => $item->unit_price_minor, 'tax_rate_basis_points' => $item->tax_rate_basis_points, 'is_optional' => $item->is_optional, 'is_selected' => $item->is_selected])->all(), $revision->tax_mode, $revision->discount_type, $revision->discount_value, $revision->deposit_percentage);
        foreach ($items as $index => $item) {
            $item->update(['line_subtotal_minor' => $result['lines'][$index]['subtotal_minor'], 'line_tax_minor' => $result['lines'][$index]['tax_minor'], 'line_total_minor' => $result['lines'][$index]['total_minor']]);
        }
        unset($result['lines']);
        $revision->update($result);
    }

    private function builderData(Quotation $quotation, QuotationRevision $revision, WorkspaceContext $context): array
    {
        return ['quotation' => $quotation, 'revision' => $revision, 'customers' => Customer::query()->where('workspace_id', $context->id())->where('status', 'active')->orderBy('name')->get(), 'services' => Service::query()->where('workspace_id', $context->id())->where('is_active', true)->orderBy('name')->get()];
    }

    private function guard(Quotation $quotation, WorkspaceContext $context): void
    {
        abort_unless($quotation->workspace_id === $context->id(), 404);
    }

    private function event(Quotation $quotation, Request $request, string $event, array $metadata = []): void
    {
        QuotationEvent::query()->create(['workspace_id' => $quotation->workspace_id, 'quotation_id' => $quotation->id, 'quotation_revision_id' => $quotation->current_revision_id, 'user_id' => $request->user()->id, 'event' => $event, 'metadata' => $metadata]);
    }
}
