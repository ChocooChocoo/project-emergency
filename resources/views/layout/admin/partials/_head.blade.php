<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>

<link rel="stylesheet" href="{{ asset('tabler/css/tabler.min.css') }}"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont/dist/tabler-icons.min.css"/>
@stack('styles')
