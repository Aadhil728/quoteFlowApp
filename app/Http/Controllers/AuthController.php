<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SeedWorkspaceTemplates;
use App\Enums\WorkspaceRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

final class AuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The provided credentials are incorrect.'])->onlyInput('email');
        }
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', 'min:12'], 'workspace_name' => ['required', 'string', 'max:120'],
        ]);
        $user = User::query()->create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
        $workspace = Workspace::query()->create(['name' => $data['workspace_name']]);
        app(SeedWorkspaceTemplates::class)->execute($workspace);
        $workspace->users()->attach($user->getKey(), ['role' => WorkspaceRole::Owner->value, 'is_active' => true]);
        AuditLog::query()->create(['workspace_id' => $workspace->getKey(), 'user_id' => $user->getKey(), 'event' => 'workspace.created']);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
