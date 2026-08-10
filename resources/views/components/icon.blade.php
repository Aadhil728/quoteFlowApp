@props(['name', 'size' => 18])
@php
$paths = [
    'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    'quote' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h8M9 17h6"/>',
    'invoice' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 12h8M9 16h8M9 20h5"/>',
    'template' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 3v18M8 9h13"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'customer' => '<rect x="3" y="4" width="18" height="17" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M7 21v-1a5 5 0 0 1 10 0v1M9 4V2h6v2"/>',
    'services' => '<path d="M20.6 13.1 11 22.7a2.4 2.4 0 0 1-3.4 0l-6.3-6.3a2.4 2.4 0 0 1 0-3.4L10.9 3.4A2 2 0 0 1 12.3 3H19a2 2 0 0 1 2 2v6.7a2 2 0 0 1-.4 1.4Z"/><circle cx="16" cy="8" r="1"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9A1.7 1.7 0 0 0 21 10h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'filter' => '<path d="M4 5h16l-6 7v5l-4 2v-7z"/>',
    'export' => '<path d="M12 3v12M7 8l5-5 5 5M5 14v6h14v-6"/>',
    'import' => '<path d="M12 15V3M7 10l5 5 5-5M5 14v6h14v-6"/>',
    'refresh' => '<path d="M20 6v5h-5M4 18v-5h5"/><path d="M6.1 9a7 7 0 0 1 11.5-2.6L20 11M4 13l2.4 4.6A7 7 0 0 0 17.9 15"/>',
    'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z"/>',
    'view' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'trash' => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v6M14 11v6"/>',
    'download' => '<path d="M12 3v12M7 10l5 5 5-5M5 20h14"/>',
    'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'chevron' => '<path d="m15 18-6-6 6-6"/>',
    'sparkle' => '<path d="m12 3 1.4 4.1L17.5 8.5l-4.1 1.4L12 14l-1.4-4.1-4.1-1.4 4.1-1.4zM19 15l.7 2.3L22 18l-2.3.7L19 21l-.7-2.3L16 18l2.3-.7z"/>',
    'check' => '<path d="m5 12 4 4L19 6"/>',
];
@endphp
<svg {{ $attributes->merge(['class' => 'ui-icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['dashboard'] !!}</svg>
