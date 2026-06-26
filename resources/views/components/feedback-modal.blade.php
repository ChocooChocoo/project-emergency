{{-- Universal feedback/confirm modal (SweetAlert alternative). Render once (in the admin
     layout). Open it via window.feedback() / window.confirmAction() in _scripts. All content
     — type, icon, colors, title, message, button labels — is set at runtime by JS. --}}
<div class="modal modal-blur fade" id="app-feedback-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status" data-feedback-status></div>
            <div class="modal-body text-center py-4">
                <i class="ti mb-2" data-feedback-icon style="font-size: 3rem;"></i>
                <h3 data-feedback-title></h3>
                <div class="text-secondary" data-feedback-message></div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <a href="#" class="btn w-100" data-bs-dismiss="modal" data-feedback-cancel></a>
                        </div>
                        <div class="col d-none" data-feedback-confirm-col>
                            <a href="#" class="btn w-100" data-bs-dismiss="modal" data-feedback-confirm></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
