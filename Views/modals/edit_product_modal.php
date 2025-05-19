<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProductModalForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="edit_product_id" name="product_id_for_form_action_only"> <div class="mb-3">
                        <label for="edit_product_name" class="form-label">Nombre del Artículo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_product_name" name="product_name" required maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label for="edit_product_description" class="form-label">Descripción <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_product_description" name="product_description" rows="4" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_product_price" class="form-label">Precio (MXN) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_product_price" name="product_price" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_product_tag" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_product_tag" name="product_tag" required>
                                <option value="">Cargando categorías...</option>
                                </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Imágenes/Videos Actuales:</label>
                        <div id="edit_currentMediaPreviewContainer" class="mb-2">
                            </div>
                        <div id="edit_mediaToDeleteContainer" style="display:none;">
                            </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_product_media_new" class="form-label">Añadir Nuevas Imágenes/Videos (opcional)</label>
                        <input class="form-control" type="file" id="edit_product_media_new" name="edit_product_media_new[]" multiple accept="image/*,video/mp4,video/quicktime,video/webm">
                        <div id="edit_newMediaPreviewContainer" class="mt-2 d-flex flex-wrap_disabled" style="gap: 10px;"></div>
                    </div>
                    <div id="editProductErrorMessages" class="alert alert-danger d-none" role="alert"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="editProductModalForm">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>