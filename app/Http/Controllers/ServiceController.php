<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ServiceController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $services = Service::query()->with('category')->where('workspace_id', $context->id())->when($request->string('search')->toString(), fn ($q, $s) => $q->where('name', 'like', "%$s%"))->orderBy('name')->paginate(15)->withQueryString();

        return view('services.index', compact('services'));
    }

    public function create(WorkspaceContext $context): View
    {
        return view('services.form', ['service' => new Service, 'categories' => ServiceCategory::query()->where('workspace_id', $context->id())->orderBy('name')->get()]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $data = $this->validated($request, $context);
        unset($data['rate']);
        Service::query()->create(['workspace_id' => $context->id(), ...$data, 'rate_minor' => $this->minor($request->string('rate')->toString())]);

        return redirect()->route('services.index')->with('status', 'Service created.');
    }

    public function edit(Service $service, WorkspaceContext $context): View
    {
        $this->guard($service, $context);

        return view('services.form', ['service' => $service, 'categories' => ServiceCategory::query()->where('workspace_id', $context->id())->orderBy('name')->get()]);
    }

    public function update(Request $request, Service $service, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($service, $context);
        $data = $this->validated($request, $context, $service);
        unset($data['rate']);
        $service->update([...$data, 'rate_minor' => $this->minor($request->string('rate')->toString())]);

        return redirect()->route('services.index')->with('status', 'Service updated.');
    }

    public function destroy(Service $service, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($service, $context);
        $service->update(['is_active' => false]);

        return back()->with('status', 'Service deactivated.');
    }

    private function validated(Request $request, WorkspaceContext $context, ?Service $service = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('services')->where('workspace_id', $context->id())->ignore($service)],
            'description' => ['nullable', 'string', 'max:3000'],
            'unit' => ['required', 'string', 'max:30'],
            'rate' => ['required', 'decimal:0,2', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'tax_behavior' => ['required', 'in:standard,exempt,zero_rated'],
            'service_category_id' => ['nullable', 'integer', Rule::exists('service_categories', 'id')->where('workspace_id', $context->id())],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function minor(string $rate): int
    {
        [$whole, $fraction] = array_pad(explode('.', $rate, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function guard(Service $service, WorkspaceContext $context): void
    {
        abort_unless($service->workspace_id === $context->id(), 404);
    }
}
