<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class InvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = $this->resolve($token);

        return view('invitations.show', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->resolve($token);
        abort_unless(strcasecmp($request->user()->email, $invitation->email) === 0, 403, 'Sign in with the invited email address.');
        $invitation->workspace_id = (int) $invitation->workspace_id;
        $request->user()->workspaces()->syncWithoutDetaching([$invitation->workspace_id => ['role' => $invitation->role->value, 'is_active' => true]]);
        $invitation->update(['accepted_at' => now()]);
        AuditLog::query()->create(['workspace_id' => $invitation->workspace_id, 'user_id' => $request->user()->id, 'event' => 'team.invitation_accepted']);
        $request->session()->put('active_workspace_id', $invitation->workspace_id);

        return redirect()->route('dashboard')->with('status', 'Invitation accepted.');
    }

    private function resolve(string $token): Invitation
    {
        return Invitation::query()->where('token_hash', hash('sha256', $token))->whereNull('accepted_at')->where('expires_at', '>', now())->firstOrFail();
    }
}
