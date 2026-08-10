<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Support\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class TeamController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $workspace = $context->get();

        return view('team.index', ['members' => $workspace->users()->orderBy('name')->get(), 'invitations' => Invitation::query()->where('workspace_id', $workspace->id)->latest()->get(), 'roles' => WorkspaceRole::cases()]);
    }

    public function invite(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255'], 'role' => ['required', Rule::enum(WorkspaceRole::class)]]);
        $workspace = $context->get();
        abort_if($workspace->users()->where('email', $data['email'])->exists(), 422, 'This user is already a member.');
        $token = Str::random(64);
        Invitation::query()->updateOrCreate(['workspace_id' => $workspace->id, 'email' => strtolower($data['email'])], ['invited_by' => $request->user()->id, 'role' => $data['role'], 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(7), 'accepted_at' => null]);
        AuditLog::query()->create(['workspace_id' => $workspace->id, 'user_id' => $request->user()->id, 'event' => 'team.invited', 'metadata' => ['email' => strtolower($data['email']), 'role' => $data['role']]]);

        return back()->with('status', 'Invitation created.')->with('invitation_url', url('/invitations/'.$token));
    }

    public function deactivate(Request $request, int $membership, WorkspaceContext $context): RedirectResponse
    {
        $record = $request->user()->memberships()->getModel()->newQuery()->where('workspace_id', $context->id())->findOrFail($membership);
        abort_if($record->user_id === $request->user()->id, 422, 'You cannot deactivate yourself.');
        $record->update(['is_active' => false]);

        return back()->with('status', 'Member deactivated.');
    }
}
