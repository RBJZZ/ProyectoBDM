<div class="modal fade" id="uploadShortModal" tabindex="-1" aria-labelledby="uploadShortModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="uploadShortForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadShortModalLabel">Subir Nuevo Short</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="shortVideoFile" class="form-label">Archivo de Video (MP4, WebM, Max 100MB)</label>
                        <input class="form-control" type="file" id="shortVideoFile" name="shortVideoFile" accept="video/mp4,video/webm" required>
                        <div id="videoPreviewContainer" class="mt-2" style="display: none;">
                            <video id="videoPreview" width="100%" controls></video>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="shortTitle" class="form-label">Título</label>
                        <input type="text" class="form-control" id="shortTitle" name="shortTitle" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="shortDescription" class="form-label">Descripción (Opcional)</label>
                        <textarea class="form-control" id="shortDescription" name="shortDescription" rows="3" maxlength="1000"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="shortTags" class="form-label">Tags (separados por coma o espacio, ej: #divertido #musica #baile)</label>
                        <input type="text" class="form-control" id="shortTags" name="shortTags" placeholder="Ej: #gatos #fails #tecnologia">
                        <small class="form-text text-muted">Los hashtags se crearán automáticamente.</small>
                    </div>

                    <div id="uploadShortMessage" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submitUploadShortButton">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Publicar Short
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


</script>