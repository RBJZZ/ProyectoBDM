<div class="modal fade" id="editShortModal" tabindex="-1" aria-labelledby="editShortModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="editShortForm"> <input type="hidden" id="editShortId" name="short_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editShortModalLabel">Editar Short</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Video Actual:</label>
                        <div id="editVideoPreviewContainer" class="mt-2">
                            <video id="editVideoPreview" width="100%" controls></video>
                            <small>La edición del archivo de video no está soportada en esta versión.</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editShortTitle" class="form-label">Título</label>
                        <input type="text" class="form-control" id="editShortTitle" name="shortTitle" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="editShortDescription" class="form-label">Descripción (Opcional)</label>
                        <textarea class="form-control" id="editShortDescription" name="shortDescription" rows="3" maxlength="1000"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editShortTags" class="form-label">Tags (separados por coma o espacio)</label>
                        <input type="text" class="form-control" id="editShortTags" name="shortTags" placeholder="Ej: #gatos #fails #tecnologia">
                        <small class="form-text text-muted">Los hashtags se crearán/actualizarán.</small>
                    </div>
                    <div id="editShortMessage" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submitEditShortButton">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>