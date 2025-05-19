<div class="modal fade" id="shortCommentsModal" tabindex="-1" aria-labelledby="shortCommentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shortCommentsModalLabel">Comentarios del Short</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="shortCommentsModalShortInfo" class="mb-3 border-bottom pb-2 d-none">
                    </div>
                <div id="shortCommentsModalExistingComments" class="mb-3" style="max-height: 40vh; overflow-y: auto;">
                    <p class="text-muted text-center">Cargando comentarios...</p>
                </div>
                <div id="shortCommentsModalFormContainer">
                    <form id="newShortCommentFormInModal">
                        <input type="hidden" name="short_id_comment_modal" id="shortIdCommentModalInput">
                        <div class="d-flex align-items-start">
                            <img src="<?php echo htmlspecialchars(($_SESSION['user_profile_pic_url_data_uri'] ?? $base_path.'Views/pictures/defaultpfp.jpg')); ?>" 
                                 class="rounded-circle me-2" width="40" height="40" alt="Mi Perfil" style="object-fit: cover;">
                            <textarea name="comment_text_modal" class="form-control me-2" rows="2" placeholder="Escribe un comentario..." required></textarea>
                            <button type="submit" class="btn btn-custom mt-1 px-3 py-2">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>