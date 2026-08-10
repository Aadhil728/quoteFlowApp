<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\InstallApplication;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class InstallerController extends Controller
{
    public function show(): View
    {
        abort_if($this->installed(), 404);
        $checks = ['PHP 8.3 or newer' => version_compare(PHP_VERSION, '8.3.0', '>='), 'PDO MySQL extension' => extension_loaded('pdo_mysql'), 'BCMath extension' => extension_loaded('bcmath'), 'Intl extension' => extension_loaded('intl'), 'Storage is writable' => is_writable(storage_path()), 'Bootstrap cache is writable' => is_writable(base_path('bootstrap/cache'))];

        return view('installer', compact('checks'));
    }

    public function store(Request $request, InstallApplication $installer): RedirectResponse
    {
        abort_if($this->installed(), 404);
        $data = $request->validate(['mode' => ['required', 'in:single_business,saas'], 'workspace_name' => ['required', 'string', 'max:120'], 'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users'], 'password' => ['required', 'confirmed', 'min:12']]);
        $user = $installer->execute($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function installed(): bool
    {
        return Schema::hasTable('system_settings') && SystemSetting::valueOf('installed_at') !== null;
    }
}
