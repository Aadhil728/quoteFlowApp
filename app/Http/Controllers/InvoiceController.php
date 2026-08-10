<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConvertQuotationToInvoice;
use App\Actions\ReconcileInvoice;
use App\Actions\RecordManualPayment;
use App\Data\CreateInvoiceData;
use App\Data\RecordManualPaymentData;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Support\PaymentGatewayRegistry;
use App\Support\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class InvoiceController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $invoices = Invoice::query()->with('customer')->where('workspace_id', $context->id())
            ->when($request->string('search')->isNotEmpty(), fn ($query) => $query->where(fn ($nested) => $nested->where('number', 'like', '%'.$request->string('search').'%')->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$request->string('search').'%'))))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()->paginate(20)->withQueryString();

        return view('invoices.index', ['invoices' => $invoices, 'statuses' => InvoiceStatus::cases()]);
    }

    public function show(Invoice $invoice, WorkspaceContext $context): View
    {
        $invoice = $this->resolve($invoice, $context);

        return view('invoices.show', ['invoice' => $invoice->load(['customer', 'quotation', 'items', 'payments.receipt'])]);
    }

    public function convert(Request $request, Quotation $quotation, WorkspaceContext $context, ConvertQuotationToInvoice $action): RedirectResponse
    {
        $quotation = Quotation::query()->where('workspace_id', $context->id())->whereKey($quotation->id)->firstOrFail();
        $data = $request->validate(['type' => ['required', Rule::in(['deposit', 'full'])], 'issue_date' => ['required', 'date'], 'due_date' => ['required', 'date', 'after_or_equal:issue_date']]);
        $invoice = $action->execute($context->get(), $quotation, $request->user(), new CreateInvoiceData($data['type'], $data['issue_date'], $data['due_date']));

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice '.$invoice->number.' created from the accepted quotation.');
    }

    public function recordManualPayment(Request $request, Invoice $invoice, WorkspaceContext $context, RecordManualPayment $action): RedirectResponse
    {
        $invoice = $this->resolve($invoice, $context);
        $data = $request->validate(['amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'], 'reference' => ['required', 'string', 'max:180'], 'paid_at' => ['required', 'date']]);
        $payment = $action->execute($context->get(), $invoice, $request->user(), new RecordManualPaymentData($this->toMinor($data['amount']), $data['reference'], $data['paid_at']));

        return redirect()->route('invoices.show', $invoice)->with('status', 'Payment recorded. Receipt '.$payment->receipt->number.' was issued.');
    }

    public function checkout(Request $request, Invoice $invoice, WorkspaceContext $context, PaymentGatewayRegistry $gateways): RedirectResponse
    {
        $invoice = $this->resolve($invoice, $context);
        abort_if($invoice->balance_minor < 1, 409, 'This invoice has no outstanding balance.');
        $data = $request->validate(['provider' => ['required', Rule::in(['stripe', 'paypal'])]]);
        $session = $gateways->get($data['provider'])->createCheckout($invoice, route('invoices.show', $invoice).'?payment=return', route('invoices.show', $invoice).'?payment=cancelled');

        return redirect()->away($session->checkoutUrl);
    }

    public function reconcile(Request $request, Invoice $invoice, WorkspaceContext $context, ReconcileInvoice $action): RedirectResponse
    {
        $invoice = $this->resolve($invoice, $context);
        $action->execute($context->get(), $invoice, $request->user());

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice balance reconciled against successful payment records.');
    }

    private function resolve(Invoice $invoice, WorkspaceContext $context): Invoice
    {
        return Invoice::query()->where('workspace_id', $context->id())->whereKey($invoice->id)->firstOrFail();
    }

    private function toMinor(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
