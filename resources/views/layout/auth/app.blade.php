<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('layout.auth.partials._head')
</head>

<body class="d-flex flex-column antialiased">
    <div class="page page-center">
        <div class="container container-tight py-4">

            {{-- Logo --}}
            <div class="text-center mb-4">
                @yield('logo')
            </div>

            {{-- Page content (login form, register form, etc.) --}}
            @yield('content')

        </div>
    </div>

    @include('layout.auth.partials._scripts')
</body>

</html>
