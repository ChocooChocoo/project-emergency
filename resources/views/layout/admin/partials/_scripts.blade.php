<script src="{{ asset('tabler/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script src="{{ asset('tabler/js/tabler.min.js') }}"></script>
<script>
    // Universal feedback/confirm modal — SweetAlert alternative. Single modal in the
    // layout (#app-feedback-modal); everything is set per call.
    (function () {
        const ID = 'app-feedback-modal';

        // type => [status bar color, icon, icon text color, confirm button color]
        const TYPES = {
            success: ['bg-success', 'ti-circle-check',     'text-green',   'btn-success'],
            error:   ['bg-danger',  'ti-circle-x',         'text-danger',  'btn-danger'],
            danger:  ['bg-danger',  'ti-alert-triangle',   'text-danger',  'btn-danger'],
            warning: ['bg-warning', 'ti-alert-triangle',   'text-warning', 'btn-warning'],
            info:    ['bg-info',    'ti-info-circle',       'text-info',    'btn-info'],
            primary: ['bg-primary', 'ti-help-circle',      'text-primary', 'btn-primary'],
        };

        // tabler.min.js bundles Bootstrap but does NOT expose window.bootstrap, so we open
        // via its data-API (synthetic data-bs-toggle click) instead of new bootstrap.Modal.
        // ponytail: synthetic-click open, switch to window.bootstrap if a bundle is ever vendored.
        function openModal(el) {
            const t = document.createElement('button');
            t.style.display = 'none';
            t.setAttribute('data-bs-toggle', 'modal');
            t.setAttribute('data-bs-target', '#' + el.id);
            el.parentNode.appendChild(t);
            t.click();
            t.remove();
        }

        // feedback({ type, title, message, confirm, cancel, onConfirm })
        // Returns nothing; onConfirm runs after the modal closes (so a follow-up modal opens clean).
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
                const onClick = () => { confirmed = true; }; // data-bs-dismiss closes the modal
                confirmBtn.addEventListener('click', onClick, { once: true });
                el.addEventListener('hidden.bs.modal', () => confirmBtn.removeEventListener('click', onClick), { once: true });
            } else {
                confirmCol.classList.add('d-none');
            }

            // fire callback only after fully hidden (backdrop gone) so chained modals open clean
            el.addEventListener('hidden.bs.modal', () => { if (confirmed) onConfirm?.(); }, { once: true });
            openModal(el);
        };

        // Convenience wrappers (SweetAlert-ish): feedback.success('Saved', 'All good')
        ['success', 'error', 'warning', 'info'].forEach((t) => {
            window.feedback[t] = (title, message) => window.feedback({ type: t, title, message });
        });

        // confirmAction(onConfirm, { type, title, message, confirm, cancel })
        window.confirmAction = function (onConfirm, opts = {}) {
            window.feedback({ type: 'warning', confirm: 'Confirm', cancel: 'Cancel', ...opts, onConfirm });
        };
    })();
</script>
@stack('scripts')
