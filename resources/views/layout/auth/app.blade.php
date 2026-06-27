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

            {{-- Dev-only OTP banner (log mailer; surfaces the code so the flow is testable) --}}
            @if (config('app.debug') && session('dev_otp'))
                <div class="alert alert-warning" role="alert">
                    <strong>Dev code:</strong> {{ session('dev_otp') }}
                    <div class="text-secondary small">Shown only with APP_DEBUG=true. Real deployments email this.</div>
                </div>
            @endif

            {{-- Page content (login form, register form, etc.) --}}
            @yield('content')

        </div>
    </div>

    @include('layout.auth.partials._scripts')

    {{-- Feedback modal (SweetAlert alternative) — available on auth pages --}}
    <x-feedback-modal />
    <script>
        (function () {
            const ID = 'app-feedback-modal';
            const TYPES = {
                success: ['bg-success', 'ti-circle-check',   'text-green',   'btn-success'],
                error:   ['bg-danger',  'ti-circle-x',       'text-danger',  'btn-danger'],
                danger:  ['bg-danger',  'ti-alert-triangle', 'text-danger',  'btn-danger'],
                warning: ['bg-warning', 'ti-alert-triangle', 'text-warning', 'btn-warning'],
                info:    ['bg-info',    'ti-info-circle',    'text-info',    'btn-info'],
                primary: ['bg-primary', 'ti-help-circle',   'text-primary', 'btn-primary'],
            };
            function openModal(el) {
                const t = document.createElement('button');
                t.style.display = 'none';
                t.setAttribute('data-bs-toggle', 'modal');
                t.setAttribute('data-bs-target', '#' + el.id);
                el.parentNode.appendChild(t); t.click(); t.remove();
            }
            window.feedback = function ({ type = 'info', title = '', message = '',
                                          confirm = null, cancel = 'Close', onConfirm } = {}) {
                const el = document.getElementById(ID);
                if (!el) return;
                const [status, icon, textColor, btnColor] = TYPES[type] || TYPES.info;
                el.querySelector('[data-feedback-status]').className = 'modal-status ' + status;
                el.querySelector('[data-feedback-icon]').className = 'ti mb-2 ' + icon + ' ' + textColor;
                el.querySelector('[data-feedback-title]').textContent = title;
                el.querySelector('[data-feedback-message]').textContent = message;
                const cancelBtn = el.querySelector('[data-feedback-cancel]');
                cancelBtn.textContent = confirm ? cancel : (cancel || 'Close');
                const confirmCol = el.querySelector('[data-feedback-confirm-col]');
                const confirmBtn = el.querySelector('[data-feedback-confirm]');
                let confirmed = false;
                if (confirm) {
                    confirmBtn.textContent = confirm;
                    confirmBtn.className = 'btn w-100 ' + btnColor;
                    confirmCol.classList.remove('d-none');
                    const onClick = () => { confirmed = true; };
                    confirmBtn.addEventListener('click', onClick, { once: true });
                    el.addEventListener('hidden.bs.modal', () => confirmBtn.removeEventListener('click', onClick), { once: true });
                } else {
                    confirmCol.classList.add('d-none');
                }
                el.addEventListener('hidden.bs.modal', () => { if (confirmed) onConfirm?.(); }, { once: true });
                openModal(el);
            };
            ['success', 'error', 'warning', 'info'].forEach((t) => {
                window.feedback[t] = (title, message) => window.feedback({ type: t, title, message });
            });
            window.confirmAction = function (onConfirm, opts = {}) {
                window.feedback({ type: 'warning', confirm: 'Confirm', cancel: 'Cancel', ...opts, onConfirm });
            };

            @if (session('status'))
            document.addEventListener('DOMContentLoaded', () =>
                window.feedback.success('Done', @js(session('status')))
            );
            @endif
        })();
    </script>
</body>

</html>
