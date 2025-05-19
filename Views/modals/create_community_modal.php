
<div class="modal fade" id="createCommunityModal" tabindex="-1" aria-labelledby="createCommunityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="createCommunityForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCommunityModalLabel">Crear Nueva Comunidad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="community_name" class="form-label">Nombre de la Comunidad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="community_name" name="community_name" required>
                        <div class="invalid-feedback">El nombre de la comunidad es obligatorio.</div>
                    </div>
                    <div class="mb-3">
                        <label for="community_description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="community_description" name="community_description" rows="3"></textarea>
                    </div>
                      <div class="mb-3">
                        <label for="community_pfp" class="form-label">Foto de Perfil (Opcional)</label>
                        <input type="file" class="form-control" id="community_pfp" name="community_pfp" accept="image/*">
                        <div id="pfpPreviewArea" class="preview-area mt-2"></div> 
                    </div>
                    <div class="mb-3">
                        <label for="community_cover" class="form-label">Foto de Portada (Opcional)</label>
                        <input type="file" class="form-control" id="community_cover" name="community_cover" accept="image/*">
                        <div id="coverPreviewArea" class="preview-area mt-2"></div> 
                    </div>
                     <div id="createCommunityError" class="alert alert-danger d-none" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="submitCreateCommunityBtn" class="btn btn-primary btn-custom">Crear Comunidad</button>
                </div>
            </form>
        </div>
    </div>
</div>