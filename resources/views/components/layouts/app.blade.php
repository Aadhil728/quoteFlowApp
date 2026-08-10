<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <link rel="preload" href="/fonts/MonaSans-Variable.woff2" as="font" type="font/woff2" crossorigin>
    <title>{{ $title ?? 'Dashboard' }} — QuoteFlow AI</title>
    <script>document.documentElement.dataset.theme=localStorage.getItem('qf-theme')||'light';document.documentElement.dataset.sidebar=localStorage.getItem('qf-sidebar')||'expanded'</script>
    @vite(['resources/css/app.css','resources/css/ui-refresh.css','resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="app-shell" data-shell>
    <aside class="sidebar" aria-label="Main navigation">
        <div class="sidebar-brand">
            <a class="wordmark" href="{{ route('dashboard') }}"><span>Q</span><i>QuoteFlow</i><b>AI</b></a>
            <button class="icon-button sidebar-close" data-nav-toggle aria-label="Close navigation"><x-icon name="close" /></button>
        </div>
        <button class="sidebar-collapse" type="button" data-sidebar-toggle aria-label="Collapse sidebar" aria-expanded="true" title="Collapse sidebar"><x-icon name="chevron" size="17" /></button>
        <nav>
            <p class="nav-label">Overview</p>
            <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard"><x-icon name="dashboard" /><i>Dashboard</i></a>
            <p class="nav-label">Sales & documents</p>
            @if(auth()->user()->canInWorkspace($activeWorkspace,'quotations.view'))
                <a class="nav-item {{ request()->routeIs('quotations.*') ? 'active' : '' }}" href="{{ route('quotations.index') }}" title="Quotations"><x-icon name="quote" /><i>Quotations</i></a>
                <a class="nav-item {{ request()->routeIs('templates.*') ? 'active' : '' }}" href="{{ route('templates.index') }}" title="Templates"><x-icon name="template" /><i>Templates</i></a>
            @endif
            @if(auth()->user()->canInWorkspace($activeWorkspace,'customers.view'))
                <a class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}" title="Customers"><x-icon name="customer" /><i>Customers</i></a>
            @endif
            @if(auth()->user()->canInWorkspace($activeWorkspace,'services.view'))
                <a class="nav-item {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index') }}" title="Products & Services"><x-icon name="services" /><i>Products & Services</i></a>
            @endif
            <p class="nav-label">Workspace</p>
            @if(auth()->user()->canInWorkspace($activeWorkspace,'team.view'))
                <a class="nav-item {{ request()->routeIs('team.*') ? 'active' : '' }}" href="{{ route('team.index') }}" title="Team"><x-icon name="users" /><i>Team</i></a>
            @endif
            @if(auth()->user()->canInWorkspace($activeWorkspace,'workspace.settings'))
                <a class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.business') }}" title="Settings"><x-icon name="settings" /><i>Settings</i></a>
            @endif
        </nav>
        <div class="sidebar-foot"><span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div></div>
    </aside>
    <div class="content-shell">
        <header class="topbar">
            <button class="icon-button nav-toggle" data-nav-toggle aria-label="Open navigation"><x-icon name="menu" /></button>
            <button class="search-button" aria-label="Open quick find"><x-icon name="search" /><span>Quick Find</span><kbd>Ctrl K</kbd></button>
            <div class="top-actions">
                <form method="POST" action="{{ route('workspaces.switch') }}">@csrf<select name="workspace_id" onchange="this.form.submit()" aria-label="Active workspace">@foreach(auth()->user()->workspaces as $workspace)<option value="{{ $workspace->id }}" @selected($workspace->is($activeWorkspace))>{{ $workspace->name }}</option>@endforeach</select></form>
                <button class="icon-button" data-theme-toggle aria-label="Toggle color theme"><x-icon name="sun" /></button>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="avatar button-avatar" title="Sign out">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</button></form>
            </div>
        </header>
        <main id="main" class="main-content">@if(session('status'))<div class="alert success" role="status">{{ session('status') }}</div>@endif{{ $slot }}</main>
    </div>
    <div class="scrim" data-nav-toggle></div>
</div>
</body>
</html>
