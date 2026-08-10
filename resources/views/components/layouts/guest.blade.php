<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <link rel="preload" href="/fonts/MonaSans-Variable.woff2" as="font" type="font/woff2" crossorigin>
    <title>{{ $title ?? 'QuoteFlow AI' }}</title>
    @vite(['resources/css/app.css','resources/css/ui-refresh.css','resources/js/app.js'])
</head>
<body class="guest-body">
<a class="skip-link" href="#main">Skip to content</a>
<main id="main" class="guest-shell">
    <section class="brand-panel">
        <a class="wordmark" href="/" aria-label="QuoteFlow AI home"><span>Q</span><i>QuoteFlow</i><b>AI</b></a>
        <div><p class="eyebrow">Quotation intelligence</p><h1>Move from customer brief to approved work—clearly.</h1><p>Build precise quotations, protect scope, collect decisions, and keep every workspace isolated.</p></div>
        <div class="brand-proof"><span>Human-reviewed AI</span><span>Secure client approvals</span><span>Self-hosted control</span></div>
    </section>
    <section class="auth-panel">{{ $slot }}</section>
</main>
</body>
</html>
