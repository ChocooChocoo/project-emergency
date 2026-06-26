<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>

<link rel="stylesheet" href="{{ asset('tabler/css/tabler.min.css') }}"/>
@stack('styles')
