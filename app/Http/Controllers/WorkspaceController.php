<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WorkspaceController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate(['workspace_id' => ['required', 'integer']]);
        abort_unless($request->user()->memberships()->where('workspace_id', $data['workspace_id'])->where('is_active', true)->exists(), 403);
        $request->session()->put('active_workspace_id', $data['workspace_id']);

        return back();
    }
}
