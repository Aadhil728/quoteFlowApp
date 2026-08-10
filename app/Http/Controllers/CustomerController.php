<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\CustomerContact;
use App\Support\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CustomerController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $customers = Customer::query()->where('workspace_id', $context->id())->when($request->string('search')->toString(), fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")))->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))->orderBy('name')->paginate(15)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $customer = Customer::query()->create(['workspace_id' => $context->id(), ...$this->validated($request)]);
        $this->activity($customer, $request, 'customer.created', 'Customer created');

        return redirect()->route('customers.show', $customer)->with('status', 'Customer created.');
    }

    public function show(Customer $customer, WorkspaceContext $context): View
    {
        $this->guard($customer, $context);

        return view('customers.show', ['customer' => $customer->load(['contacts', 'activities'])]);
    }

    public function edit(Customer $customer, WorkspaceContext $context): View
    {
        $this->guard($customer, $context);

        return view('customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($customer, $context);
        $customer->update($this->validated($request));
        $this->activity($customer, $request, 'customer.updated', 'Customer details updated');

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated.');
    }

    public function destroy(Request $request, Customer $customer, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($customer, $context);
        $this->activity($customer, $request, 'customer.archived', 'Customer archived');
        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer archived.');
    }

    public function storeContact(Request $request, Customer $customer, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($customer, $context);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'position' => ['nullable', 'string', 'max:100'], 'is_primary' => ['sometimes', 'boolean']]);
        if ($request->boolean('is_primary')) {
            CustomerContact::query()->where('customer_id', $customer->id)->update(['is_primary' => false]);
        }
        CustomerContact::query()->create(['workspace_id' => $context->id(), 'customer_id' => $customer->id, ...$data, 'is_primary' => $request->boolean('is_primary')]);
        $this->activity($customer, $request, 'contact.created', 'Customer contact added');

        return back()->with('status', 'Contact added.');
    }

    public function destroyContact(Request $request, Customer $customer, CustomerContact $contact, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($customer, $context);
        abort_unless($contact->workspace_id === $context->id() && $contact->customer_id === $customer->id, 404);
        $contact->delete();
        $this->activity($customer, $request, 'contact.deleted', 'Customer contact removed');

        return back()->with('status', 'Contact removed.');
    }

    public function export(WorkspaceContext $context): StreamedResponse
    {
        return response()->streamDownload(function () use ($context): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Type', 'Email', 'Phone', 'Tax ID', 'Currency', 'Locale', 'Status']);
            Customer::query()->where('workspace_id', $context->id())->orderBy('name')->chunk(200, fn ($rows) => $rows->each(fn ($c) => fputcsv($out, [$this->safeCsv($c->name), $c->type, $this->safeCsv($c->email), $this->safeCsv($c->phone), $this->safeCsv($c->tax_id), $c->currency, $c->locale, $c->status])));
            fclose($out);
        }, 'customers.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        abort_unless(is_resource($handle), 422, 'The CSV could not be read.');
        $header = array_map(fn ($value) => strtolower(trim((string) $value)), fgetcsv($handle) ?: []);
        if (! in_array('name', $header, true)) {
            fclose($handle);

            return back()->withErrors(['csv' => 'The CSV must contain a name header.']);
        }
        $created = 0;
        $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $values = array_slice(array_pad($row, count($header), ''), 0, count($header));
            $data = array_combine($header, $values) ?: [];
            $name = trim((string) ($data['name'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));
            if ($name === '' || ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL))) {
                $skipped++;

                continue;
            }
            $duplicate = Customer::query()->where('workspace_id', $context->id())->where(fn ($query) => $query->where('name', $name)->when($email !== '', fn ($query) => $query->orWhere('email', $email)))->exists();
            if ($duplicate) {
                $skipped++;

                continue;
            }
            Customer::query()->create(['workspace_id' => $context->id(), 'name' => $name, 'type' => in_array($data['type'] ?? '', ['company', 'individual'], true) ? $data['type'] : 'company', 'email' => $email ?: null, 'phone' => trim((string) ($data['phone'] ?? '')) ?: null, 'currency' => strtoupper(trim((string) ($data['currency'] ?? ''))) ?: null, 'locale' => trim((string) ($data['locale'] ?? 'en')) ?: 'en', 'status' => 'active']);
            $created++;
        }
        fclose($handle);

        return back()->with('status', "Import complete: {$created} created, {$skipped} skipped.");
    }

    private function validated(Request $request): array
    {
        return $request->validate(['type' => ['required', 'in:company,individual'], 'name' => ['required', 'string', 'max:180'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'tax_id' => ['nullable', 'string', 'max:80'], 'currency' => ['nullable', 'string', 'size:3'], 'locale' => ['required', 'string', 'max:12'], 'billing_address' => ['nullable', 'string', 'max:1000'], 'notes' => ['nullable', 'string', 'max:3000'], 'status' => ['required', 'in:active,inactive']]);
    }

    private function guard(Customer $customer, WorkspaceContext $context): void
    {
        abort_unless($customer->workspace_id === $context->id(), 404);
    }

    private function activity(Customer $customer, Request $request, string $event, string $summary): void
    {
        CustomerActivity::query()->create(['workspace_id' => $customer->workspace_id, 'customer_id' => $customer->id, 'user_id' => $request->user()->id, 'event' => $event, 'summary' => $summary]);
    }

    private function safeCsv(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
