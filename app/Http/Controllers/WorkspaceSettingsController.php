<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\CurrencyCatalog;
use App\Support\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class WorkspaceSettingsController extends Controller
{
    public function edit(WorkspaceContext $context, CurrencyCatalog $currencies): View
    {
        return view('settings.business', ['workspace' => $context->get(), 'currencies' => $currencies->selectOptions()]);
    }

    public function update(Request $request, WorkspaceContext $context, CurrencyCatalog $currencies): RedirectResponse
    {
        $workspace = $context->get();
        $request->merge(['currency' => strtoupper((string) $request->input('currency'))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'legal_name' => ['nullable', 'string', 'max:180'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'address' => ['nullable', 'string', 'max:1000'], 'tax_id' => ['nullable', 'string', 'max:80'], 'currency' => ['required', Rule::in($currencies->codes())], 'timezone' => ['required', 'timezone:all'], 'locale' => ['required', 'string', 'max:12'], 'quotation_prefix' => ['required', 'alpha_num', 'max:12'], 'default_validity_days' => ['required', 'integer', 'between:1,365'], 'payment_instructions' => ['nullable', 'string', 'max:2000'], 'brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/']]);
        $workspace->update($data);
        AuditLog::query()->create(['workspace_id' => $workspace->id, 'user_id' => $request->user()->id, 'event' => 'workspace.settings_updated']);

        return back()->with('status', 'Business settings saved.');
    }
}
