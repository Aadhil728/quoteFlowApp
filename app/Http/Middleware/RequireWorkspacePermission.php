<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireWorkspacePermission
{
    public function __construct(private readonly WorkspaceContext $context) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless($request->user()?->canInWorkspace($this->context->get(), $permission), 403);

        return $next($request);
    }
}
