<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveWorkspace
{
    public function __construct(private readonly WorkspaceContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->is_active !== false, 403);
        $membership = $user->memberships()->with('workspace')->where('is_active', true)
            ->when($request->session()->get('active_workspace_id'), fn ($query, $id) => $query->where('workspace_id', $id))
            ->first() ?? $user->memberships()->with('workspace')->where('is_active', true)->first();
        abort_unless($membership?->workspace?->is_active, 403, 'No active workspace is available.');
        $request->session()->put('active_workspace_id', $membership->workspace_id);
        $this->context->set($membership->workspace);
        view()->share('activeWorkspace', $membership->workspace);

        return $next($request);
    }
}
