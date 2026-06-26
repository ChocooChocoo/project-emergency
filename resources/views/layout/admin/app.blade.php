<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('layout.admin.partials._head')
</head>

<body class="antialiased">
    {{-- Theme script must run before page renders to avoid flash --}}
    <script src="{{ asset('tabler/js/tabler-theme.min.js') }}"></script>

    <div class="page">

        @include('layout.admin.partials._sidebar')

        @include('layout.admin.partials._navbar')

        <div class="page-wrapper">

            @yield('content')

            {{-- Footer --}}
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center flex-row-reverse">
                        <div class="col-lg-auto ms-lg-auto">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item"><a href="#" class="link-secondary">Documentation</a></li>
                                <li class="list-inline-item"><a href="#" class="link-secondary">Support</a></li>
                            </ul>
                        </div>
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    Copyright &copy; {{ date('Y') }} {{ config('app.name') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>

        </div>
    </div>

    <x-feedback-modal />

    @include('layout.admin.partials._scripts')
</body>

</html>
