<div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createGroupModalLabel">Crear Nuevo Grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createGroupForm">
                    <div class="mb-3">
                        <label for="groupNameInputModal" class="form-label">Nombre del Grupo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="groupNameInputModal" name="group_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="groupPhotoInputModal" class="form-label">Foto del Grupo (Opcional)</label>
                        <input class="form-control" type="file" id="groupPhotoInputModal" name="group_photo" accept="image/*">
                    </div>
                    
                    <hr>

                    <div class="mb-3">
                        <label for="userSearchInputForGroupModal" class="form-label">Buscar Miembros para Añadir:</label>
                        <input type="text" class="form-control" id="userSearchInputForGroupModal" placeholder="Escribe nombre o @username...">
                        
                        <div id="userSearchResultsForGroupModal" class="list-group mt-2" style="max-height: 150px; overflow-y: auto; border: 1px solid #ced4da; border-radius: 0.25rem;">
                            <p class="text-muted small p-2 text-center">Escribe para buscar usuarios.</p> 
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Miembros Seleccionados:</label>
                        <div id="selectedGroupMembersDisplayModal" class="border p-2 rounded bg-light" style="min-height: 50px; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <small class="text-muted align-self-center placeholder-selected-members">Ningún miembro añadido (aparte de ti).</small>

                            </div>

                        <input type="hidden" name="participant_ids" id="groupParticipantIdsInputModal"> 
                        
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-2"></i>Crear Grupo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>