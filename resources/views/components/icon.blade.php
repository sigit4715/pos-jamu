@php($iconClass = $class ?? 'h-4 w-4')
<svg class="{{ $iconClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect>
            @break
        @case('cart')
            <circle cx="9" cy="20" r="1"></circle><circle cx="20" cy="20" r="1"></circle><path d="M2 3h3l2.7 12.1a2 2 0 0 0 2 1.6h8.9a2 2 0 0 0 1.9-1.4L22 7H6"></path>
            @break
        @case('receipt')
            <path d="M4 2v20l2.5-1.5L9 22l2.5-1.5L14 22l2.5-1.5L20 22V2l-2.5 1.5L15 2l-2.5 1.5L10 2 7.5 3.5z"></path><path d="M8 8h8M8 12h8M8 16h5"></path>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3.5 2"></path>
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
            @break
        @case('package-plus')
            <path d="m16.5 9.4-9-5.2M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l3-1.7"></path><path d="M3.3 7 12 12l8.7-5M12 22V12M19 17v6M16 20h6"></path>
            @break
        @case('package-minus')
            <path d="m16.5 9.4-9-5.2M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l3-1.7"></path><path d="M3.3 7 12 12l8.7-5M12 22V12M16 20h6"></path>
            @break
        @case('boxes')
            <path d="M3 7.5 8 4l5 3.5-5 3zM13 7.5 18 4l3 2v6l-3 2-5-3.5zM3 13.5l5-3 5 3-5 3zM3 13.5v5l5 3v-5M13 13.5v5l-5 3"></path>
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18M8 14h3M8 17h6"></path>
            @break
        @case('wallet')
            <path d="M19 7V5a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a3 3 0 0 1-3-3V6"></path><path d="M16 14h.01"></path>
            @break
        @case('clipboard')
            <path d="M9 5h6M9 3h6a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h1V5a2 2 0 0 1 2-2Z"></path><path d="m9 14 2 2 4-4"></path>
            @break
        @case('undo')
            <path d="M9 14 4 9l5-5"></path><path d="M4 9h10a6 6 0 0 1 6 6v1"></path>
            @break
        @case('cube')
            <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"></path><path d="m4 7.5 8 4.5 8-4.5M12 12v9"></path>
            @break
        @case('database')
            <ellipse cx="12" cy="5" rx="8" ry="3"></ellipse><path d="M4 5v7c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 12v7c0 1.7 3.6 3 8 3s8-1.3 8-3v-7"></path>
            @break
        @case('barcode')
            <path d="M3 5v3M3 16v3M21 5v3M21 16v3M7 5v14M10 5v14M14 5v14M17 5v14"></path>
            @break
        @case('tag')
            <path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"></path><circle cx="8" cy="8" r="1"></circle>
            @break
        @case('coins')
            <circle cx="8" cy="8" r="5"></circle><path d="M13 8h3a5 5 0 0 1 0 10H8a5 5 0 0 1-4.9-4"></path><path d="M8 5v6M6.5 6.5h2.1a1.4 1.4 0 0 1 0 2.8H7.4a1.4 1.4 0 0 0 0 2.8h2.1"></path>
            @break
        @case('landmark')
            <path d="m3 10 9-6 9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 21h18M2 18h20"></path>
            @break
        @case('search')
            <circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path>
            @break
        @case('user')
            <circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path>
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.8 1.8 0 0 0 .4 2l.1.1-2.1 2.1-.1-.1a1.8 1.8 0 0 0-2-.4 1.8 1.8 0 0 0-1.1 1.7v.2h-3v-.2a1.8 1.8 0 0 0-1.1-1.7 1.8 1.8 0 0 0-2 .4l-.1.1-2.1-2.1.1-.1a1.8 1.8 0 0 0 .4-2 1.8 1.8 0 0 0-1.7-1.1h-.2v-3H5a1.8 1.8 0 0 0 1.7-1.1 1.8 1.8 0 0 0-.4-2l-.1-.1 2.1-2.1.1.1a1.8 1.8 0 0 0 2 .4 1.8 1.8 0 0 0 1.1-1.7v-.2h3v.2a1.8 1.8 0 0 0 1.1 1.7 1.8 1.8 0 0 0 2-.4l.1-.1 2.1 2.1-.1.1a1.8 1.8 0 0 0-.4 2 1.8 1.8 0 0 0 1.7 1.1h.2v3h-.2a1.8 1.8 0 0 0-1.7 1.1Z"></path>
            @break
        @case('chart')
            <path d="M4 19V5M4 19h16M8 16v-4M12 16V8M16 16v-7M20 16v-10"></path>
            @break
        @case('trending')
            <path d="m3 17 6-6 4 4 7-8"></path><path d="M15 7h5v5"></path>
            @break
        @case('arrows')
            <path d="M7 3v14M4 6l3-3 3 3M17 21V7M14 18l3 3 3-3"></path>
            @break
        @case('download')
            <path d="M12 3v12M7 10l5 5 5-5M5 21h14"></path>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"></path>
            @break
        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"></path>
            @break
        @case('alert')
            <path d="M10.3 3.7 2.5 18a2 2 0 0 0 1.8 3h15.4a2 2 0 0 0 1.8-3L13.7 3.7a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path>
            @break
        @case('logout')
            <path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 0 0-2-2h-6"></path>
            @break
        @case('leaf')
            <path d="M20 4C11 4 5 8.5 5 15c0 2.8 1.9 5 4.7 5C16.2 20 20 12.7 20 4Z"></path><path d="M4 21c2.5-5.3 6.4-8.7 12-11"></path>
            @break
        @default
            <circle cx="12" cy="12" r="8"></circle>
    @endswitch
</svg>
