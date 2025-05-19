<div class="modal fade" id="editGroupModal" tabindex="-1" aria-labelledby="editGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGroupModalLabel">Editar Información del Grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editGroupForm">
                    <input type="hidden" id="editGroupIdInputModal" name="group_id">

                    <div class="mb-3">
                        <label for="editGroupNameInputModal" class="form-label">Nombre del Grupo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editGroupNameInputModal" name="group_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="currentGroupPhotoPreview" class="form-label">Foto Actual del Grupo</label>
                        <div>
                            <img src="" id="currentGroupPhotoPreview" alt="Foto actual del grupo" class="img-thumbnail mb-2" style="max-width: 150px; max-height: 150px; display: none;">
                            <p id="noCurrentGroupPhotoText" class="text-muted small" style="display: block;">El grupo no tiene foto actualmente.</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editGroupPhotoInputModal" class="form-label">Subir Nueva Foto (Opcional)</label>
                        <input class="form-control" type="file" id="editGroupPhotoInputModal" name="group_photo" accept="image/*">
                        <small class="form-text text-muted">Si no seleccionas una nueva foto, se mantendrá la actual.</small>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-2"></i>Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>