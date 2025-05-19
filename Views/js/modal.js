/**
 * modal.js
 * Manejo de todos los modales de la aplicación StarNest.
 */

//////////////// VENTANA MODAL DE NOTIFICACIONES
function createNotificationsModal() {
    const modalHTML = `
    <div class="modal fade" id="notificationsModal" tabindex="-1" aria-labelledby="notificationsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" 
        style="position: fixed; 
                    right: 20px; 
                    margin: 0; 
                    width: 450px; 
                    max-height: calc(100vh - 100px);">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Notificaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- Reacciones -->
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle p-2 me-3">
                                    <i class="bi bi-heart-fill"></i>
                                </div>
                                <div>
                                    <p class="mb-0">A 15 personas les gustó tu publicación</p>
                                    <small class="">Hace 2 horas</small>
                                </div>
                            </div>
                        </div>

                     
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="bg-info text-white rounded-circle p-2 me-3">
                                    <i class="bi bi-chat-dots-fill"></i>
                                </div>
                                <div>
                                    <p class="mb-0">Nuevo comentario: "¡Excelente publicación!"</p>
                                    <small class="">Hace 4 horas</small>
                                </div>
                            </div>
                        </div>

                        
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <img src="/ProyectoBDM/Views/pictures/fuyu.jpg" class="rounded-circle me-3" width="40" height="40">
                                <div>
                                    <p class="mb-0"><span class="fw-bold">@chifuyu</span> compartió tu post</p>
                                    <small class="">Hace 1 día</small>
                                </div>
                            </div>
                        </div>

                        
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="bg-success text-white rounded-circle p-2 me-3">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                                <div>
                                    <p class="mb-0">Publicación exitosa en el mercado</p>
                                    <small class="">Ayer</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <small class="">Mostrando 4 de 5 notificaciones</small>
                </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}
////////////// VENTANA MODAL "PUBLICAR"

function createPostModal() {

    const basePath = window.basePath || '/';
    const profilePic = window.currentUserData?.profilePicSrc || (basePath + 'Views/pictures/defaultpfp.jpg'); 
    const userFullName = window.currentUserData?.nombreCompleto || 'Usuario';
    const userFirstName = window.currentUserData?.nombre || 'Usuario';
    const defaultPrivacy = window.currentUserData?.privacidad || 'Publico';

    const modalHTML = `
    <div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="postModalLabel">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="createPostForm" enctype="multipart/form-data"> 
                    <div class="modal-header border-0 pb-0">
                        <div class="d-flex align-items-center w-100">
                            <img src="${profilePic}" class="rounded-circle me-2" width="45" height="45">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold">${userFullName}</h6>
                               
                                <select class="form-select form-select-sm border-0 p-0" name="post_privacy" style="width: 80px;">
                                    <option value="Publico" ${defaultPrivacy === 'Publico' ? 'selected' : ''}>Público</option>
                                    <option value="Amigos" ${defaultPrivacy === 'Amigos' ? 'selected' : ''}>Amigos</option> 
                                    <option value="Privado" ${defaultPrivacy === 'Privado' ? 'selected' : ''}>Privado</option>
                                </select>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                    </div>

                    <div class="modal-body p-5 pb-2">
                         
                        <textarea class="form-control border-0 fs-5"
                                  name="post_text"
                                  placeholder="¿Qué estás pensando, ${userFirstName}?"
                                  rows="5"
                                  style="resize: none;"></textarea>

                        
                        <div id="postPreviewArea" class="preview-area mb-3 mt-3 d-flex flex-wrap gap-2">
                           
                        </div>

                        <div class="d-flex align-items-center gap-2 border-top pt-3">
                            <label class="btn btn-custom rounded-pill px-3 py-2">
                                <i class="bi bi-image me-2"></i>Foto/Video
                                
                                <input type="file" id="postMediaInput" name="post_media[]" hidden accept="image/*,video/*" multiple>
                            </label>

                            <button type="button" class="btn btn-custom rounded-pill px-3 py-2">
                                <i class="bi bi-filetype-gif me-2"></i>GIF
                            </button>
                        </div>
                    </div>

                    <div class="modal-footer border-0">
                       
                        <button type="submit" id="submitPostBtn" form="createPostForm" class="btn btn-custom rounded-pill px-4 py-2 w-100">
                            Publicar
                        </button>
                    </div>
                </form> 
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const postModalElement = document.getElementById('postModal');
    const fileInput = document.getElementById('postMediaInput');
    const previewArea = document.getElementById('postPreviewArea');
    const postForm = document.getElementById('createPostForm'); 
    const submitButton = document.getElementById('submitPostBtn'); 

    if (fileInput && previewArea) {
        fileInput.addEventListener('change', (event) => {
            handleFileSelect(event, previewArea, fileInput, null); ///modificada, checar.
        });
    } else {
        console.error("Error: No se encontró el input de archivo (#postMediaInput) o el área de preview (#postPreviewArea) para el modal de post.");
    }


    if (postModalElement) {
        postModalElement.addEventListener('hidden.bs.modal', () => {
            if (previewArea) {
                previewArea.querySelectorAll('video[data-object-url]').forEach(video => {
                    URL.revokeObjectURL(video.src);
                });
                previewArea.innerHTML = ''; 
            }
            if (fileInput) {
                fileInput.value = ''; 
            }
             if (submitButton) {
                 submitButton.disabled = false;
                 submitButton.innerHTML = 'Publicar';
             }
             
        });
    }

     if (postForm && submitButton) {
         postForm.addEventListener('submit', async (e) => {
             e.preventDefault(); 
             console.log("Formulario de creación de post enviado.");

             const formData = new FormData(postForm);
             const postText = formData.get('post_text')?.trim();
             const postFiles = fileInput.files; 


             if (!postText && (!postFiles || postFiles.length === 0)) {
                 alert('Debes escribir algo o seleccionar al menos un archivo.');
                 return;
             }

           
             submitButton.disabled = true;
             submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Publicando...`;

             try {
                 const response = await fetch(window.basePath + 'post/create', { 
                     method: 'POST',
                     body: formData 
                 });

                
                 const responseData = await response.json().catch(() => null); 

                 if (!response.ok) {
                     
                     console.error("Error en la respuesta del servidor:", response.status, responseData);
                     const errorMessage = responseData?.message || `Error del servidor (${response.status}). Inténtalo de nuevo.`;
                     alert(`Error al publicar: ${errorMessage}`);
                     throw new Error(errorMessage); 
                 }

                 
                 console.log("Respuesta del servidor (Éxito):", responseData);

                 if (responseData?.success) {
                     alert(responseData.message || 'Publicación creada con éxito.');
                    
                     const modalInstance = bootstrap.Modal.getInstance(postModalElement);
                     if (modalInstance) {
                         modalInstance.hide();
                     }
                     
                 } else {
                     
                     alert(`Error al publicar: ${responseData?.message || 'Respuesta inesperada del servidor.'}`);
                 }

             } catch (error) {
                 console.error('Error durante la publicación (Fetch):', error);
                 
                 if (!response?.ok) { 
                      alert('Error de conexión o al procesar la solicitud. Revisa la consola para más detalles.');
                 }
             } finally {
                
                 submitButton.disabled = false;
                 submitButton.innerHTML = 'Publicar';
             }
         });
     } else {
          console.error("Error: No se encontró el formulario (#createPostForm) o el botón de submit (#submitPostBtn).");
     }

    document.querySelectorAll('.feed-input').forEach(input => {
        input.addEventListener('click', () => {
            const postModal = new bootstrap.Modal(document.getElementById('postModal'));
            postModal.show();
        });
    });
}

function handleFileSelect(event, previewAreaElement,specificFileInputElement, removedIdsInputElement = null) {
    previewAreaElement.innerHTML = ''; 
    const files = event.target.files;
    const MAX_FILES = 10; 
    const MAX_FILE_SIZE_MB = 100; 
    if (!files || files.length === 0) {
        return; 
    }

    if (files.length > MAX_FILES) {
        alert(`Puedes seleccionar un máximo de ${MAX_FILES} archivos a la vez.`);
        event.target.value = ''; 
        return;
    }

    const filePromises = [];

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileSizeMB = file.size / 1024 / 1024;

        if (fileSizeMB > MAX_FILE_SIZE_MB) {
            console.warn(`Archivo omitido por tamaño: ${file.name} (${fileSizeMB.toFixed(2)} MB)`);
             
             const errorPreview = createErrorPreview(`"${file.name}" excede el límite de ${MAX_FILE_SIZE_MB} MB.`);
             previewAreaElement.appendChild(errorPreview);
            continue; 
        }

        if (file.type.startsWith('image/')) {
            filePromises.push(createImagePreview(file, previewAreaElement, specificFileInputElement, removedIdsInputElement));
        } else if (file.type.startsWith('video/')) {
            filePromises.push(createVideoPreview(file, previewAreaElement, specificFileInputElement, removedIdsInputElement));
        } else {
            console.warn(`Archivo omitido por tipo no soportado: ${file.name} (${file.type})`);
             const errorPreview = createErrorPreview(`"${file.name}" (Tipo no soportado).`);
             previewAreaElement.appendChild(errorPreview);
        }
    }

}

function createImagePreview(file, previewAreaElement, fileInputElement, removedIdsInputElement = null) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = (e) => {
            const previewWrapper = document.createElement('div');
            previewWrapper.className = 'preview-item position-relative border rounded p-1'; 

            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = `Previsualización de ${file.name}`;
            img.style.width = '100px'; 
            img.style.height = '100px';
            img.style.objectFit = 'cover'; 

            const removeBtn = createRemoveButton(previewWrapper, file, fileInputElement, null, removedIdsInputElement);

            previewWrapper.appendChild(img);
            previewWrapper.appendChild(removeBtn);
            previewAreaElement.appendChild(previewWrapper);
            resolve(); 
        };

        reader.onerror = (error) => {
            console.error("Error al leer archivo de imagen:", file.name, error);
             const errorPreview = createErrorPreview(`Error al leer "${file.name}".`);
             previewAreaElement.appendChild(errorPreview);
            reject(error);
        };

        reader.readAsDataURL(file);
    });
}

function createVideoPreview(file, previewAreaElement, fileInputElement, removedIdsInputElement = null) {
    return new Promise((resolve) => {
        const previewWrapper = document.createElement('div');
        
        previewWrapper.className = 'preview-item position-relative border rounded p-1 video-preview-item w-100 mb-2'; 
        previewWrapper.style.backgroundColor = '#222'; 
        previewWrapper.style.overflow = 'hidden'; 

        const video = document.createElement('video');
        const objectURL = URL.createObjectURL(file);
        video.src = objectURL;
        video.dataset.objectUrl = 'true'; 
        video.muted = true; 
        video.playsInline = true; 
        video.preload = 'metadata'; 
        video.controls = true;
        video.style.width = '100%';
        video.style.display = 'block';
        video.style.maxHeight = '400px';

        video.onloadedmetadata = () => {
            console.log(`Video cargado: ${file.name}, Duración: ${video.duration}s`);
        };

        video.onerror = (e) => {
            console.error("Error al cargar la previsualización del video:", file.name, e);
            previewWrapper.innerHTML = ''; 
            const errorMsg = createErrorPreview(`Error al cargar video "${file.name}"`);
            errorMsg.style.width = '100%';
            errorMsg.style.height = '80px';
            previewWrapper.appendChild(errorMsg);
            URL.revokeObjectURL(objectURL);
        };

        const removeBtn = createRemoveButton(previewWrapper, file, fileInputElement, objectURL, removedIdsInputElement);
        
        removeBtn.style.top = '5px';
        removeBtn.style.right = '5px'; 

        previewWrapper.appendChild(video);
        previewWrapper.appendChild(removeBtn);
        previewAreaElement.appendChild(previewWrapper);
        resolve();
    });
}
///////////// VENTANAS MODALES EDITAR - ELIMINAR POST

function createEditPostModalTemplate() {
    const profilePic = window.currentUserData?.profilePicSrc || (window.basePath + 'Views/pictures/defaultpfp.jpg');
    const userFullName = window.currentUserData?.nombreCompleto || 'Usuario';

    const modalHTML = `
    <div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="editPostForm" enctype="multipart/form-data"> <!- Añadido enctype -->
                    <div class="modal-header border-0 pb-0">
                        <div class="d-flex align-items-center w-100">
                            <img src="${profilePic}" class="rounded-circle me-2" width="45" height="45">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold">${userFullName}</h6>
                                <select class="form-select form-select-sm border-0 p-0" id="editPostPrivacy" name="post_privacy" style="width: 80px;">
                                    <option value="Publico">Público</option>
                                    <option value="Amigos">Amigos</option>
                                    <option value="Privado">Privado</option>
                                </select>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                    </div>

                    <div class="modal-body p-5 pb-2">
                        <input type="hidden" id="editPostIdInput" name="post_id">
                        <!-- Input oculto para IDs de media existente a eliminar -->
                        <input type="hidden" id="removedMediaIdsInput" name="removed_media_ids" value="">

                        <textarea class="form-control border-0 fs-5"
                                  id="editPostTextArea"
                                  name="post_text"
                                  rows="5"
                                  style="resize: none;"
                                  placeholder="Edita tu publicación..."></textarea>

                        <!-- Área de previsualización combinada (existente + nueva) -->
                        <div id="editPostPreviewArea" class="preview-area mb-3 mt-3 d-flex flex-wrap gap-2">
                            <!-- Las previews de media existente y nueva irán aquí -->
                        </div>

                        <!-- Botones para añadir media (como en crear post) -->
                        <div class="d-flex align-items-center gap-2 border-top pt-3">
                            <label class="btn btn-custom rounded-pill px-3 py-2">
                                <i class="bi bi-image me-2"></i>Foto/Video
                                <!-- Input de archivo para NUEVA media -->
                                <input type="file" id="editPostMediaInput" name="new_post_media[]" hidden accept="image/*,video/*" multiple>
                            </label>
                            <button type="button" class="btn btn-custom rounded-pill px-3 py-2">
                                <i class="bi bi-filetype-gif me-2"></i>GIF
                            </button>
                            <!-- Puedes añadir más botones si es necesario -->
                        </div>
                    </div>

                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="saveEditPostBtn" class="btn btn-custom">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // --- Añadir Listeners específicos para el modal de edición ---
    const editForm = document.getElementById('editPostForm');
    const fileInput = document.getElementById('editPostMediaInput');
    const previewArea = document.getElementById('editPostPreviewArea');
    const modalElement = document.getElementById('editPostModal');

    if (editForm) {
        editForm.addEventListener('submit', handleEditPostSubmit); // Ya lo teníamos
    }
    if (fileInput && previewArea) {
        // Usamos la misma función handleFileSelect, pasándole los elementos correctos
        fileInput.addEventListener('change', (event) => {
            handleFileSelect(event, previewArea, fileInput, document.getElementById('removedMediaIdsInput')); // Pasamos el input de IDs eliminados
        });
    } else {
        console.error("Error: No se encontró #editPostMediaInput o #editPostPreviewArea para el modal de edición.");
    }

    // Limpiar al cerrar (similar a crear post)
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', () => {
            if (previewArea) {
                // Revocar Object URLs de videos NUEVOS
                previewArea.querySelectorAll('video[data-object-url="true"]').forEach(video => {
                   if(video.src && video.src.startsWith('blob:')) {
                       URL.revokeObjectURL(video.src);
                   }
                });
                previewArea.innerHTML = ''; // Limpiar previews
            }
            if (fileInput) {
                fileInput.value = ''; // Limpiar selección de archivos nuevos
            }
            const removedInput = document.getElementById('removedMediaIdsInput');
            if(removedInput) removedInput.value = ''; // Limpiar IDs a eliminar
            // Resetear botón submit si es necesario
            const submitBtn = document.getElementById('saveEditPostBtn');
             if(submitBtn) {
                 submitBtn.disabled = false;
                 submitBtn.innerHTML = 'Guardar Cambios';
             }
        });
    }
}

function createDeleteConfirmModalTemplate() {
    const modalHTML = `
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar esta publicación? Esta acción no se puede deshacer.</p>
                    <!-- Input oculto para el ID del post a eliminar -->
                    <input type="hidden" id="deletePostIdInput">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Eliminar</button>
                </div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

     // Añadir Listener para el botón de confirmación de borrado
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if(confirmBtn) {
        confirmBtn.addEventListener('click', handleDeletePostConfirm);
    }
}




//////////// UPLOAD AND PREVIEW DE IMAGENES Y VIDEOS PARA EL MODAL DE CREATE POST Y EDIT POST

function createErrorPreview(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'preview-item preview-error text-danger border rounded p-2 d-flex align-items-center';
    errorDiv.style.width = '100px';
    errorDiv.style.height = '100px';
    errorDiv.style.fontSize = '0.8em';
    errorDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i> ${message}`;
    return errorDiv;
}

function createRemoveButton(wrapper, fileOrMediaId, fileInputElement, objectUrlToRemove = null, removedIdsInputElement = null) {
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0 d-flex justify-content-center align-items-center';
    removeBtn.innerHTML = '×';
    removeBtn.style.width = '20px';
    removeBtn.style.height = '20px';
    removeBtn.style.borderRadius = '50%';
    removeBtn.style.lineHeight = '1';
    removeBtn.setAttribute('aria-label', 'Quitar medio'); // Accesibilidad

    removeBtn.onclick = () => {
        wrapper.remove(); // Eliminar visualmente la previsualización

        if (objectUrlToRemove && objectUrlToRemove.startsWith('blob:')) {
            URL.revokeObjectURL(objectUrlToRemove); // Liberar memoria si es un blob URL
        }

        // Determinar si es un archivo nuevo o un ID existente
        if (fileOrMediaId instanceof File && fileInputElement) {
            // --- Es un archivo NUEVO ---
            const fileToRemove = fileOrMediaId;
            const dataTransfer = new DataTransfer();
            const currentFiles = Array.from(fileInputElement.files);

            currentFiles.forEach(file => {
                if (file !== fileToRemove) {
                    dataTransfer.items.add(file);
                }
            });
            fileInputElement.files = dataTransfer.files; // Actualizar la lista de archivos en el input
            console.log(`Nuevo archivo "${fileToRemove.name}" quitado de la selección.`);
            removeBtn.title = `Quitar ${fileToRemove.name}`; // Actualizar tooltip

        } else if ((typeof fileOrMediaId === 'number' || typeof fileOrMediaId === 'string') && removedIdsInputElement) {
            // --- Es un ID de media EXISTENTE ---
            const mediaIdToRemove = String(fileOrMediaId); // Asegurar que sea string
            let currentRemovedIds = removedIdsInputElement.value ? removedIdsInputElement.value.split(',') : [];
            // Filtrar posibles vacíos por comas extra y asegurar que no se duplique
            currentRemovedIds = currentRemovedIds.filter(id => id.trim() !== '');
            if (!currentRemovedIds.includes(mediaIdToRemove)) {
                currentRemovedIds.push(mediaIdToRemove);
                removedIdsInputElement.value = currentRemovedIds.join(','); // Guardar IDs separados por coma
            }
            console.log(`Media existente ID ${mediaIdToRemove} marcada para eliminar. Lista actual: ${removedIdsInputElement.value}`);
            removeBtn.title = `Quitar medio existente (ID: ${mediaIdToRemove})`; // Actualizar tooltip
        } else {
            console.warn("createRemoveButton: No se pudo determinar si era archivo nuevo o ID existente, o faltó un input requerido.");
        }
    };
    // Establecer tooltip inicial
    if (fileOrMediaId instanceof File) {
         removeBtn.title = `Quitar ${fileOrMediaId.name}`;
    } else {
         removeBtn.title = `Quitar medio existente (ID: ${fileOrMediaId})`;
    }
    return removeBtn;
}

///////////// VENTANA MODAL "AJUSTES"

function createSettingsModal() {

    const profilePic = window.currentUserData?.profilePicSrc || '';
    const coverPic = window.currentUserData?.coverPicSrc || '';
    const userNombre = window.currentUserData?.nombre || '';
    const userApellidoP = window.currentUserData?.apellidoPaterno || '';
    const userApellidoM = window.currentUserData?.apellidoMaterno || '';
    const userBio = window.currentUserData?.biografia || '';
    const userTelefono = window.currentUserData?.telefono || '';
    const userGenero = window.currentUserData?.genero || '';
    const savedPais = window.currentUserData?.pais || '';
    const savedProvincia = window.currentUserData?.provincia || ''; 
    const savedCiudad = window.currentUserData?.ciudad || '';
    const userPrivacidad = window.currentUserData?.privacidad || 'Publico';
    const userFechaNac = window.currentUserData?.fechaNacimiento || '';

    const modalHTML = `
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body m-4">
                    
                    <form id="profileSettingsForm" enctype="multipart/form-data">
                    
                        <div class="mb-4">
                            <label class="form-label">Foto de portada</label>
                            <div class="cover-preview-container mb-3">
                                <img id="coverPreview" class="cover-preview" src="${coverPic}">
                               
                                <input type="file" id="coverInput" name="foto_portada" accept="image/*" hidden>
                                <button type="button" class="btn btn-upload-cover btn-custom border" onclick="document.getElementById('coverInput').click()">
                                    <i class="bi bi-camera"></i> Cambiar portada
                                </button>
                            </div>
                        </div>

                        
                        <div class="mb-4">
                            <label class="form-label">Foto de perfil</label>
                            <div class="avatar-preview-container mb-3">
                                <img id="avatarPreview" class="avatar-preview rounded-circle" src="${profilePic}">
                                 <!-- Input de archivo para perfil -->
                                <input type="file" id="avatarInput" name="foto_perfil" accept="image/*" hidden>
                                <button type="button" class="btn btn-upload-avatar btn-custom" onclick="document.getElementById('avatarInput').click()">
                                    <i class="bi bi-camera"></i>
                                </button>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="editNombre" class="form-label">Nombre(s)</label>
                                <!-- Usar nombre y value, añadir name -->
                                <input type="text" class="form-control" id="editNombre" name="nombre" value="${userNombre}" required>
                            </div>
                            <div class="col-md-4">
                                <label for="editApellidoP" class="form-label">Apellido Paterno</label>
                                <!-- Usar nombre y value, añadir name -->
                                <input type="text" class="form-control" id="editApellidoP" name="apellidoPaterno" value="${userApellidoP}" required>
                            </div>
                             <div class="col-md-4">
                                <label for="editApellidoM" class="form-label">Apellido Materno</label>
                                 <!-- Usar nombre y value, añadir name -->
                                <input type="text" class="form-control" id="editApellidoM" name="apellidoMaterno" value="${userApellidoM}">
                            </div>
                        </div>

                         <div class="mb-3">
                            <label for="editFechaNac" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="editFechaNac" name="fechaNacimiento" value="${userFechaNac}">
                        </div>

                         
                         <div class="mb-3">
                            <label for="editGenero" class="form-label">Género</label>
                            <select class="form-select" id="editGenero" name="genero">
                                <option value="" ${userGenero === '' ? 'selected' : ''}>Selecciona...</option>
                                <option value="Masculino" ${userGenero === 'Masculino' ? 'selected' : ''}>Masculino</option>
                                <option value="Femenino" ${userGenero === 'Femenino' ? 'selected' : ''}>Femenino</option>
                                <option value="Otro" ${userGenero === 'Otro' ? 'selected' : ''}>Otro</option>
                                <option value="Prefiero no decirlo" ${userGenero === 'Prefiero no decirlo' ? 'selected' : ''}>Prefiero no decirlo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editBio" class="form-label">Biografía</label>
                            
                            <textarea class="form-control" id="editBio" name="biografia" rows="3"
                                placeholder="Cuéntanos algo sobre ti...">${userBio}</textarea>
                        </div>

                         
                         <div class="mb-3">
                            <label for="editTelefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="editTelefono" name="telefono" value="${userTelefono}" placeholder="Ingresa un número de teléfono">
                        </div>

                       
                          <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="editPais" class="form-label">País:</label>
                                <select class="form-select" id="editPais" name="pais" required>
                                    <option value="">Cargando países...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="editProvincia" class="form-label">Estado/Provincia:</label>
                                <select class="form-select" id="editProvincia" name="provincia" disabled required>
                                     <!-- El name aquí debe coincidir con tu BD/Modelo -->
                                    <option value="">Selecciona un país primero</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="editCiudad" class="form-label">Ciudad:</label>
                                <select class="form-select" id="editCiudad" name="ciudad" disabled required>
                                    <option value="">Selecciona un estado primero</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="editPrivacy" class="form-label">Privacidad del perfil</label>
                             <!-- Usar nombre, preseleccionar opción correcta -->
                            <select class="form-select" id="editPrivacy" name="privacidad">
                                <option value="Publico" ${userPrivacidad === 'Publico' ? 'selected' : ''}>Público</option>
                                <option value="Privado" ${userPrivacidad === 'Privado' ? 'selected' : ''}>Privado</option>
                                <option value="Solo Amigos" ${userPrivacidad === 'Solo Amigos' ? 'selected' : ''}>Solo Amigos</option>
                                <!-- Eliminado 'custom' si no lo manejas -->
                            </select>
                        </div>

                        <!-- Botón Guardar -->
                        <button type="submit" class="btn btn-custom w-100">Guardar cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modalElement = document.getElementById('settingsModal'); 
    const coverInput = modalElement.querySelector('#coverInput');
    const avatarInput = modalElement.querySelector('#avatarInput');
    const selectPais = modalElement.querySelector('#editPais');
    const selectEstado = modalElement.querySelector('#editProvincia');
    const selectCiudad = modalElement.querySelector('#editCiudad');
    const settingsForm = modalElement.querySelector('#profileSettingsForm'); 

    //CODIGO VALIDACIONES

    const editNombreInput = settingsForm.elements['nombre'];
    const editApellidoPInput = settingsForm.elements['apellidoPaterno'];
    const editApellidoMInput = settingsForm.elements['apellidoMaterno'];
    const editFechaNacInput = settingsForm.elements['fechaNacimiento'];
    const editGeneroSelect = settingsForm.elements['genero'];
    const editBioTextarea = settingsForm.elements['biografia']; 
    const editTelefonoInput = settingsForm.elements['telefono']; 
    const editPaisSelect = settingsForm.elements['pais']; 
    const editProvinciaSelect = settingsForm.elements['provincia']; 
    const editCiudadSelect = settingsForm.elements['ciudad']; 

    const fieldsToValidateModal = {
        'nombre': [validateName], 
        'apellidoPaterno': [validateName], 
        'apellidoMaterno': [validateName], 
        'fechaNacimiento': [validateDate, null], 
        'genero': [validateSelection], 
        'telefono': [validatePhone], 
        'pais': [validateSelection], 
        'provincia': [validateSelection],
        'ciudad': [validateSelection] 
    };

     
     Object.keys(fieldsToValidateModal).forEach(name => {
        const inputElement = settingsForm.elements[name];
        if (inputElement) {
            const [validationFn, ...args] = fieldsToValidateModal[name];
            const eventType = (inputElement.type === 'checkbox' || inputElement.type === 'radio' || inputElement.tagName === 'SELECT') ? 'change' : 'blur';

            inputElement.addEventListener(eventType, () => {
               
                validationFn(inputElement, ...args);
            });

            
             if (!inputElement.required && (inputElement.value === '' || (inputElement.tagName === 'SELECT' && inputElement.value === ''))) {
                 inputElement.classList.remove('is-invalid', 'is-valid');
                 const feedback = inputElement.closest('.mb-3, .form-group')?.querySelector('.invalid-feedback');
                 if(feedback) feedback.textContent = '';
            }
        }
    });



    //FIN CODIGO VALIDACIONES



    if (coverInput) {
        coverInput.addEventListener('change', function(e) {
            handleImageUpload(e.target.files[0], 'coverPreview');
        });
    } else { console.error("Elemento #coverInput no encontrado en modal settings"); }

    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            handleImageUpload(e.target.files[0], 'avatarPreview');
        });
    } else { console.error("Elemento #avatarInput no encontrado en modal settings"); }


    async function cargarPaises() {
        selectPais.disabled = true; 
        selectEstado.innerHTML = '<option value="">Selecciona un país primero</option>';
        selectCiudad.innerHTML = '<option value="">Selecciona un estado primero</option>';
        selectEstado.disabled = true;
        selectCiudad.disabled = true;
  
        try {
            const res = await fetch('https://countriesnow.space/api/v0.1/countries/states');
            if (!res.ok) throw new Error('Error al cargar países');
            const data = await res.json();
  
           
            if (!data || data.error || !Array.isArray(data.data)) {
                console.error("Respuesta inesperada de la API de países:", data);
                selectPais.innerHTML = '<option value="">Error al cargar</option>';
                return;
            }
            const paises = data.data;
  
            selectPais.innerHTML = '<option value="">Selecciona un país</option>'; 
            let paisEncontrado = false;
            paises.forEach(pais => {
                const option = document.createElement('option');
                option.value = pais.name;
                option.textContent = pais.name;
                
                if (pais.name === savedPais) {
                    option.selected = true;
                    paisEncontrado = true;
                }
                selectPais.appendChild(option);
            });
            selectPais.disabled = false; 
  
            
            if (paisEncontrado) {
                await cargarEstados(savedPais); 
            }
  
        } catch(error) {
             console.error("Error en cargarPaises:", error);
             selectPais.innerHTML = '<option value="">Error al cargar</option>';
        }
      }
  
      async function cargarEstados(paisSeleccionado) {
        selectEstado.innerHTML = '<option value="">Cargando estados...</option>';
        selectCiudad.innerHTML = '<option value="">Selecciona un estado primero</option>';
        selectEstado.disabled = true;
        selectCiudad.disabled = true;
  
        try {
            const res = await fetch('https://countriesnow.space/api/v0.1/countries/states', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ country: paisSeleccionado })
            });
            if (!res.ok) throw new Error('Error al cargar estados');
            const data = await res.json();
  
            
            if (!data || data.error || !data.data || !Array.isArray(data.data.states)) {
                console.error("Respuesta inesperada de la API de estados:", data);
                selectEstado.innerHTML = '<option value="">No disponible</option>';
                return; 
            }
            const estados = data.data.states;
  
            if (estados.length === 0) {
                selectEstado.innerHTML = '<option value="">No hay estados</option>';
                return; 
            }
  
            selectEstado.innerHTML = '<option value="">Selecciona un estado</option>'; 
            let estadoEncontrado = false;
            estados.forEach(estado => {
                const option = document.createElement('option');
                option.value = estado.name;
                option.textContent = estado.name;
                
                if (paisSeleccionado === savedPais && estado.name === savedProvincia) {
                    option.selected = true;
                    estadoEncontrado = true;
                }
                selectEstado.appendChild(option);
            });
            selectEstado.disabled = false; 
  
            if (estadoEncontrado) {
                await cargarCiudades(paisSeleccionado, savedProvincia);
            }
  
         } catch(error) {
             console.error("Error en cargarEstados:", error);
             selectEstado.innerHTML = '<option value="">Error al cargar</option>';
         }
      }
  
      async function cargarCiudades(paisSeleccionado, estadoSeleccionado) {
        selectCiudad.innerHTML = '<option value="">Cargando ciudades...</option>';
        selectCiudad.disabled = true;
  
        try {
            const res = await fetch('https://countriesnow.space/api/v0.1/countries/state/cities', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ country: paisSeleccionado, state: estadoSeleccionado })
            });
             if (!res.ok) throw new Error('Error al cargar ciudades');
            const data = await res.json();
  
            if (!data || data.error || !Array.isArray(data.data)) {
                console.error("Respuesta inesperada de la API de ciudades:", data);
                selectCiudad.innerHTML = '<option value="">No disponible</option>';
                return;
            }
            const ciudades = data.data;
  
            if (ciudades.length === 0) {
                 selectCiudad.innerHTML = '<option value="">No hay ciudades</option>';
                 return;
            }
  
            selectCiudad.innerHTML = '<option value="">Selecciona una ciudad</option>'; 
            ciudades.forEach(ciudad => {
                const option = document.createElement('option');
                option.value = ciudad;
                option.textContent = ciudad;
                if (paisSeleccionado === savedPais && estadoSeleccionado === savedProvincia && ciudad === savedCiudad) {
                    option.selected = true;
                }
                selectCiudad.appendChild(option);
            });
            selectCiudad.disabled = false; 
  
        } catch(error) {
            console.error("Error en cargarCiudades:", error);
            selectCiudad.innerHTML = '<option value="">Error al cargar</option>';
        }
      }
  
      
      selectPais.addEventListener('change', () => {
        const pais = selectPais.value;
        if (pais) {
          cargarEstados(pais); 
        } else {
         
          selectEstado.innerHTML = '<option value="">Selecciona un país primero</option>';
          selectCiudad.innerHTML = '<option value="">Selecciona un estado primero</option>';
          selectEstado.disabled = true;
          selectCiudad.disabled = true;
        }
      });
  
      selectEstado.addEventListener('change', () => {
        const pais = selectPais.value; 
        const estado = selectEstado.value;
        if (estado) {
          cargarCiudades(pais, estado); 
        } else {
          
          selectCiudad.innerHTML = '<option value="">Selecciona un estado primero</option>';
          selectCiudad.disabled = true;
        }
      });
  
      
      cargarPaises(); 
  
    

      if (settingsForm) {
        console.log("Formulario #profileSettingsForm encontrado en createSettingsModal. Añadiendo listener 'submit'...");
        settingsForm.addEventListener('submit', (e) => {
            console.log('Evento submit del formulario CAPTURADO.');
            e.preventDefault();
            console.log('Default Prevented EJECUTADO.');

            const isFormValid = validateForm(settingsForm, fieldsToValidateModal);

            if (!isFormValid) {
                console.log('Formulario de EDICIÓN inválido. No se enviará.');
                // El feedback visual y el focus ya lo maneja validateForm
                return; // Detiene la ejecución si hay errores
            }

            console.log('Formulario de EDICIÓN válido. Procediendo con FormData y Fetch...');

            const formData = new FormData(settingsForm);
            console.log("FormData creada:");
             for (let [key, value] of formData.entries()) {
                 if (value instanceof File) {
                     console.log(`FormData Entry: ${key}: File Name: ${value.name}, Size: ${value.size}, Type: ${value.type}`);
                 } else {
                     console.log(`FormData Entry: ${key}:`, value);
                 }
             }

            const saveButton = settingsForm.querySelector('button[type="submit"]');
            const originalButtonText = saveButton ? saveButton.innerHTML : 'Guardar cambios'; 

            if(saveButton) {
                saveButton.disabled = true;
                saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            }

            fetch(window.basePath + 'profile/update', {
                method: 'POST',
                body: formData
            })
            .then(response => { 
                 console.log("Respuesta Fetch recibida, Status:", response.status);
                 if (!response.ok) {
                      return response.text().then(text => {
                          console.error("Respuesta Fetch NO OK, Texto:", text);
                          try {
                              const errData = JSON.parse(text);
                              throw new Error(errData.message || `Error HTTP ${response.status}`);
                          } catch (jsonError) {
                              throw new Error(text || `Error HTTP ${response.status}`);
                          }
                      });
                 }
                 console.log("Respuesta Fetch OK, intentando parsear JSON...");
                 return response.json();
             })
            .then(data => { 
                 console.log("Respuesta JSON procesada:", data);
                 if (data.success) {
                     alert('Perfil actualizado con éxito!');
                     window.location.reload(); 
                 } else {
                     console.error('Error reportado por el servidor:', data.message);
                     alert('Error al actualizar: ' + (data.message || 'Error desconocido'));
                 }
             })
            .catch(error => { 
                 console.error('Error en la solicitud FETCH o procesamiento:', error);
                 alert('Error de conexión o procesamiento: ' + error.message);
             })
            .finally(() => { 
                 console.log("Ejecutando Finally del Fetch");
                 if (saveButton) { 
                     saveButton.disabled = false;
                     saveButton.innerHTML = originalButtonText;
                 }
            });
        });
    } else {
        console.error("¡CRÍTICO! No se encontró #profileSettingsForm después de insertar el HTML del modal.");
    }

      

}

//////////// VENTANA MODAL CONTRASEÑA

function createChangePasswordModal() {
    const modalHTML = `
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalTitle">Verificar Contraseña Actual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body m-3">
                    <!-- Paso 1: Verificar Contraseña Actual -->
                    <div id="verifyPasswordStep">
                        <form id="verifyPasswordForm">
                            <div class="mb-3">
                                <label for="currentPasswordInput" class="form-label">Ingresa tu contraseña actual:</label>
                                <input type="password" class="form-control" id="currentPasswordInput" required>
                                <div id="verifyPasswordError" class="invalid-feedback d-block"></div>
                            </div>
                            <button type="submit" class="btn btn-custom w-100" id="verifyPasswordBtn">Verificar</button>
                        </form>
                    </div>

                    <!-- Paso 2: Establecer Nueva Contraseña (inicialmente oculto) -->
                    <div id="setNewPasswordStep" style="display: none;">
                        <form id="setNewPasswordForm">
                            <div class="mb-3">
                                <label for="newPasswordInput" class="form-label">Nueva Contraseña:</label>
                                <input type="password" class="form-control" id="newPasswordInput" required minlength="6">
                                <!-- Puedes agregar un indicador de fortaleza aquí si quieres -->
                            </div>
                            <div class="mb-3">
                                <label for="confirmPasswordInput" class="form-label">Confirmar Nueva Contraseña:</label>
                                <input type="password" class="form-control" id="confirmPasswordInput" required minlength="6">
                            </div>
                            <div id="newPasswordError" class="alert alert-danger d-none" role="alert"></div>
                            <button type="submit" class="btn btn-success w-100" id="savePasswordBtn">Guardar Nueva Contraseña</button>
                        </form>
                    </div>

                     <!-- Mensaje de éxito (inicialmente oculto) -->
                     <div id="passwordSuccessMessage" class="alert alert-success d-none mt-3" role="alert">
                        ¡Contraseña actualizada con éxito!
                     </div>

                </div>
                 <div class="modal-footer d-none" id="changePasswordModalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                 </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modalElement = document.getElementById('changePasswordModal');
    const verifyForm = document.getElementById('verifyPasswordForm');
    const newPasswordForm = document.getElementById('setNewPasswordForm');
    const verifyStepDiv = document.getElementById('verifyPasswordStep');
    const newPasswordStepDiv = document.getElementById('setNewPasswordStep');
    const verifyPasswordBtn = document.getElementById('verifyPasswordBtn');
    const savePasswordBtn = document.getElementById('savePasswordBtn');
    const verifyErrorDiv = document.getElementById('verifyPasswordError');
    const newPasswordErrorDiv = document.getElementById('newPasswordError');
    const successMessageDiv = document.getElementById('passwordSuccessMessage');
    const modalTitle = document.getElementById('changePasswordModalTitle');
    const currentPasswordInput = document.getElementById('currentPasswordInput');
    const newPasswordInput = document.getElementById('newPasswordInput');
    const confirmPasswordInput = document.getElementById('confirmPasswordInput');
    const modalFooter = document.getElementById('changePasswordModalFooter');
    const closeButton = modalElement.querySelector('.btn-close');

    
    verifyForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        verifyErrorDiv.textContent = ''; 
        verifyPasswordBtn.disabled = true;
        verifyPasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verificando...';

        const currentPassword = currentPasswordInput.value;

        try {
            const response = await fetch(window.basePath + 'profile/verify-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' 
                },
                body: `currentPassword=${encodeURIComponent(currentPassword)}`
            });

            const data = await response.json();

            if (response.ok && data.success) {
                
                verifyStepDiv.style.display = 'none';
                newPasswordStepDiv.style.display = 'block';
                modalTitle.textContent = 'Establecer Nueva Contraseña';
                closeButton.style.display = 'none'; 
                modalElement.setAttribute('data-bs-backdrop', 'static'); 
                modalElement.setAttribute('data-bs-keyboard', 'false');
            } else {
                
                verifyErrorDiv.textContent = data.message || 'La contraseña actual es incorrecta.';
                currentPasswordInput.classList.add('is-invalid');
            }
        } catch (error) {
            console.error('Error verificando contraseña:', error);
            verifyErrorDiv.textContent = 'Error de conexión. Inténtalo de nuevo.';
        } finally {
            verifyPasswordBtn.disabled = false;
            verifyPasswordBtn.innerHTML = 'Verificar';
        }
    });

    
    currentPasswordInput.addEventListener('input', () => {
        if (currentPasswordInput.classList.contains('is-invalid')) {
            currentPasswordInput.classList.remove('is-invalid');
            verifyErrorDiv.textContent = '';
        }
    });


    
    newPasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        newPasswordErrorDiv.classList.add('d-none'); 
        newPasswordErrorDiv.textContent = '';
        newPasswordInput.classList.remove('is-invalid');
        confirmPasswordInput.classList.remove('is-invalid');

        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        
        if (newPassword.length < 6) {
             newPasswordErrorDiv.textContent = 'La nueva contraseña debe tener al menos 6 caracteres.';
             newPasswordErrorDiv.classList.remove('d-none');
             newPasswordInput.classList.add('is-invalid');
             return;
        }
        if (newPassword !== confirmPassword) {
            newPasswordErrorDiv.textContent = 'Las nuevas contraseñas no coinciden.';
            newPasswordErrorDiv.classList.remove('d-none');
            
            newPasswordInput.classList.add('is-invalid');
            confirmPasswordInput.classList.add('is-invalid');
            return;
        }

        savePasswordBtn.disabled = true;
        savePasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

        try {
            const response = await fetch(window.basePath + 'profile/update-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `newPassword=${encodeURIComponent(newPassword)}`
            });

            const data = await response.json();

            if (response.ok && data.success) {
                
                newPasswordStepDiv.style.display = 'none'; 
                successMessageDiv.classList.remove('d-none'); 
                modalTitle.textContent = 'Éxito';
                modalFooter.classList.remove('d-none'); 
                
                modalElement.removeAttribute('data-bs-backdrop');
                modalElement.removeAttribute('data-bs-keyboard');
                closeButton.style.display = 'block'; 


            } else {
              
                newPasswordErrorDiv.textContent = data.message || 'Error al guardar la nueva contraseña.';
                newPasswordErrorDiv.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Error guardando contraseña:', error);
            newPasswordErrorDiv.textContent = 'Error de conexión al guardar. Inténtalo de nuevo.';
            newPasswordErrorDiv.classList.remove('d-none');
        } finally {
            savePasswordBtn.disabled = false;
            savePasswordBtn.innerHTML = 'Guardar Nueva Contraseña';
        }
    });

     
     newPasswordInput.addEventListener('input', () => {
        if (newPasswordInput.classList.contains('is-invalid')) {
            newPasswordInput.classList.remove('is-invalid');
          
            if (confirmPasswordInput.classList.contains('is-invalid') && newPasswordInput.value === confirmPasswordInput.value) {
                 confirmPasswordInput.classList.remove('is-invalid');
            }
             newPasswordErrorDiv.classList.add('d-none');
             newPasswordErrorDiv.textContent = '';
        }
    });
    confirmPasswordInput.addEventListener('input', () => {
        if (confirmPasswordInput.classList.contains('is-invalid')) {
            confirmPasswordInput.classList.remove('is-invalid');
            
             if (newPasswordInput.classList.contains('is-invalid') && newPasswordInput.value === confirmPasswordInput.value) {
                 newPasswordInput.classList.remove('is-invalid');
            }
            newPasswordErrorDiv.classList.add('d-none');
            newPasswordErrorDiv.textContent = '';
        }
    });

    
    modalElement.addEventListener('hidden.bs.modal', () => {
        verifyStepDiv.style.display = 'block';
        newPasswordStepDiv.style.display = 'none';
        successMessageDiv.classList.add('d-none');
        modalFooter.classList.add('d-none');
        modalTitle.textContent = 'Verificar Contraseña Actual';
        verifyForm.reset();
        newPasswordForm.reset();
        verifyErrorDiv.textContent = '';
        newPasswordErrorDiv.classList.add('d-none');
        newPasswordErrorDiv.textContent = '';
        currentPasswordInput.classList.remove('is-invalid');
        newPasswordInput.classList.remove('is-invalid');
        confirmPasswordInput.classList.remove('is-invalid');
        verifyPasswordBtn.disabled = false;
        verifyPasswordBtn.innerHTML = 'Verificar';
        savePasswordBtn.disabled = false;
        savePasswordBtn.innerHTML = 'Guardar Nueva Contraseña';
        
         modalElement.removeAttribute('data-bs-backdrop');
         modalElement.removeAttribute('data-bs-keyboard');
         closeButton.style.display = 'block';
    });

}

function handleImageUpload(file, previewId) {
    if (!file.type.startsWith('image/')) {
        alert('Por favor selecciona una imagen válida');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById(previewId).src = e.target.result;
    }
    reader.readAsDataURL(file);
}

function createInsightsModal() {
    const modalHTML = `
    <div class="modal fade" id="insightsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-graph-up"></i> Insights de la Cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-5">
                    <div class="insight-filters mb-4 d-flex">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-md btn-custom active" data-period="7days">7 días</button>
                            <button type="button" class="btn btn-md btn-custom" data-period="30days">30 días</button>
                            <button type="button" class="btn btn-md btn-custom" data-period="custom">Personalizado</button>
                        </div>
                        <select class="form-select form-select-sm ms-2" style="width: 200px;">
                            <option>Todos los contenidos</option>
                            <option>Publicaciones</option>
                            <option>Videos</option>
                            <option>Historias</option>
                        </select>
                    </div>

                    <nav>
                        <div class="nav nav-tabs" id="insightsTabs">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#audience">
                                <i class="bi bi-people-fill me-2"></i>Audiencia
                            </button>
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#performance">
                                <i class="bi bi-lightning-charge-fill me-2"></i>Rendimiento
                            </button>
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#interactions">
                                <i class="bi bi-heart-fill me-2"></i>Interacciones
                            </button>
                        </div>
                    </nav>

                    <div class="tab-content mt-3">
                        <div class="tab-pane fade show active" id="audience">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="metric-card">
                                        <h6><i class="bi bi-gender-ambiguous me-2"></i>Demografía</h6>
                                        <canvas id="demographicsChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="metric-card">
                                        <h6><i class="bi bi-geo-alt me-2"></i>Ubicación</h6>
                                        <div class="map-placeholder"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="performance">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <div class="metric-card">
                                        <h6><i class="bi bi-bar-chart-line me-2"></i>Evolución de Seguidores</h6>
                                        <canvas id="followersChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="metric-card">
                                        <h6><i class="bi bi-trophy me-2"></i>Top Publicaciones</h6>
                                        <div class="top-posts">
                                            ${Array.from({length: 5}, (_,i) => `
                                            <div class="post-item">
                                                <small class="text-muted">#${i+1}</small>
                                                <div class="post-preview"></div>
                                                <div class="post-stats">
                                                    <span><i class="bi bi-heart"></i> ${(i+1)*150}</span>
                                                    <span><i class="bi bi-chat"></i> ${(i+1)*15}</span>
                                                </div>
                                            </div>`).join('')}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="interactions">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="metric-card">
                                        <h6><i class="bi bi-clock-history me-2"></i>Horario de Actividad</h6>
                                        <canvas id="activityChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="metric-card">
                                        <h6><i class="bi bi-pie-chart-fill me-2"></i>Tipo de Interacciones</h6>
                                        <canvas id="interactionsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
   
    document.getElementById('insightsModal').addEventListener('shown.bs.modal', () => {
        initCharts();
    });
}

function initCharts() {

    new Chart(document.getElementById('demographicsChart'), {
        type: 'doughnut',
        data: {
            labels: ['Hombre 18-24', 'Mujer 18-24', 'Hombre 25-34', 'Mujer 25-34'],
            datasets: [{
                data: [25, 35, 20, 20],
                backgroundColor: ['#4e79a7', '#f28e2c', '#59a14f', '#e15759']
            }]
        }
    });

    new Chart(document.getElementById('followersChart'), {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Seguidores',
                data: [12000, 13500, 14200, 15800, 16400, 18200],
                borderColor: '#4e79a7',
                tension: 0.4
            }]
        }
    });

    new Chart(document.getElementById('activityChart'), {
        type: 'bar',
        data: {
            labels: ['00-04', '04-08', '08-12', '12-16', '16-20', '20-24'],
            datasets: [{
                label: 'Interacciones',
                data: [50, 120, 450, 600, 550, 300],
                backgroundColor: '#59a14f'
            }]
        }
    });

    new Chart(document.getElementById('interactionsChart'), {
        type: 'pie',
        data: {
            labels: ['Me gusta', 'Comentarios', 'Compartidos'],
            datasets: [{
                data: [65, 25, 10],
                backgroundColor: ['#4e79a7', '#f28e2c', '#59a14f']
            }]
        }
    });
}


/////////// VENTANA MODAL PUBLICAR PRODUCTO + TAGS

async function loadProductTags() {
    const selectElement = document.getElementById('productTagSelect');
    if (!selectElement) {
        console.error("Select #productTagSelect no encontrado para cargar tags.");
        return;
    }

    selectElement.disabled = true;
    selectElement.innerHTML = '<option value="">Cargando categorías...</option>';

    try {
        // Llama al endpoint que acabamos de implementar
        const response = await fetch(window.basePath + 'tags/market');
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status} al cargar tags.`);
        }
        const tagsResponse = await response.json(); // Cambiado nombre variable

        // Verifica la estructura de la respuesta del TagController
        if (tagsResponse && tagsResponse.success && Array.isArray(tagsResponse.data)) {
            selectElement.innerHTML = '<option value="">Selecciona una categoría</option>'; // Opción por defecto
            tagsResponse.data.forEach(tag => {
                const option = document.createElement('option');
                option.value = tag.tag_id;       // Usar ID como valor
                option.textContent = tag.tag_nombre; // Mostrar nombre
                selectElement.appendChild(option);
            });
            selectElement.disabled = false; // Habilitar select
        } else {
            console.error("Respuesta inesperada al cargar tags:", tagsResponse);
            selectElement.innerHTML = '<option value="">Error al cargar</option>';
        }
    } catch (error) {
        console.error('Error al cargar las etiquetas del producto:', error);
        selectElement.innerHTML = '<option value="">Error al cargar</option>';
    }
}

function createProductModalTemplate() {
    const profilePic = window.currentUserData?.profilePicSrc || (window.basePath + 'Views/pictures/defaultpfp.jpg');
    const userFullName = window.currentUserData?.nombreCompleto || 'Usuario';

    const modalHTML = `
    <div class="modal fade" id="createProductModal" tabindex="-1" aria-labelledby="createProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="createProductForm" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createProductModalLabel">Crear Publicación en Marketplace</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <!-- Fila 1: Nombre y Precio -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="productNameInput" class="form-label">Nombre del Artículo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="productNameInput" name="product_name" required maxlength="255">
                            </div>
                            <div class="col-md-4">
                                <label for="productPriceInput" class="form-label">Precio (MXN) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="productPriceInput" name="product_price" required min="0.01" step="0.01" placeholder="Ej: 25.99">
                            </div>
                        </div>

                        <!-- Fila 2: Categoría -->
                        <div class="mb-3">
                             <label for="productTagSelect" class="form-label">Categoría <span class="text-danger">*</span></label>
                             <select class="form-select" id="productTagSelect" name="product_tag" required>
                                 <option value="" selected disabled>Cargando categorías...</option>
                                 <!-- Opciones de tags se cargarán aquí -->
                             </select>
                        </div>

                         <!-- Fila 3: Descripción -->
                        <div class="mb-3">
                            <label for="productDescriptionInput" class="form-label">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="productDescriptionInput" name="product_description" rows="4" required placeholder="Describe tu artículo detalladamente..."></textarea>
                        </div>

                        <!-- Área de previsualización de archivos -->
                        <div id="productPreviewArea" class="preview-area mb-3 mt-3 d-flex flex-wrap gap-2">
                            <!-- Las previews irán aquí -->
                        </div>

                        <!-- Botón para añadir media -->
                        <div class="mb-3">
                            <label class="btn btn-light border">
                                <i class="bi bi-camera-fill me-2"></i>Añadir Fotos/Video (Hasta 10)
                                <input type="file" id="productMediaInput" name="product_media[]" hidden accept="image/*,video/*" multiple>
                            </label>
                             <small class="d-block text-muted mt-1">La primera imagen será la principal.</small>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="submitProductBtn" class="btn btn-custom">Publicar Artículo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // --- Añadir Listeners para el modal de producto ---
    const productForm = document.getElementById('createProductForm');
    const fileInput = document.getElementById('productMediaInput');
    const previewArea = document.getElementById('productPreviewArea');
    const modalElement = document.getElementById('createProductModal');

    if (productForm) {
        // Necesitaremos una función handleProductSubmit (similar a handleEditPostSubmit)
        productForm.addEventListener('submit', handleProductSubmit); // Crear esta función
    } else { console.error("Formulario #createProductForm no encontrado."); }

    if (fileInput && previewArea) {
        // Reutilizar handleFileSelect para manejar las previews
        fileInput.addEventListener('change', (event) => {
            // Para crear, no pasamos input de IDs eliminados
            handleFileSelect(event, previewArea, fileInput, null);
        });
    } else { console.error("Error: #productMediaInput o #productPreviewArea no encontrados."); }

    // Limpiar al cerrar
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', () => {
            if (previewArea) {
                previewArea.querySelectorAll('video[data-object-url="true"]').forEach(video => {
                   if(video.src && video.src.startsWith('blob:')) {
                       URL.revokeObjectURL(video.src);
                   }
                });
                previewArea.innerHTML = '';
            }
            if (fileInput) fileInput.value = '';
            if (productForm) productForm.reset(); // Resetear todos los campos del form
            // Resetear botón submit
            const submitBtn = document.getElementById('submitProductBtn');
             if(submitBtn) {
                 submitBtn.disabled = false;
                 submitBtn.innerHTML = 'Publicar Artículo';
             }
             // Resetear validación de Bootstrap si la usas
             productForm.classList.remove('was-validated');
        });

        // Cargar las etiquetas DESPUÉS de que el modal esté en el DOM
        loadProductTags();
    }
}

async function handleProductSubmit(event) {
    event.preventDefault(); // Prevenir recarga
    event.stopPropagation(); // Detener propagación por si acaso

    const form = event.target;
    const submitButton = document.getElementById('submitProductBtn');
    if (!submitButton || !form) return;

    // --- Validación básica Frontend ---
    // Bootstrap 5 puede manejarla con 'required' y los tipos de input,
    // pero podemos añadir validaciones extra si es necesario.
    // Ejemplo: Verificar que se seleccionó una categoría
    const tagSelect = document.getElementById('productTagSelect');
    if (!tagSelect.value) {
         alert('Por favor, selecciona una categoría para tu artículo.');
         tagSelect.focus();
         return;
    }
     // Ejemplo: Verificar que se subió al menos una imagen/video
     const fileInput = document.getElementById('productMediaInput');
     if (!fileInput.files || fileInput.files.length === 0) {
         alert('Por favor, añade al menos una foto o video del artículo.');
         fileInput.click(); // Intentar abrir el selector de archivo
         return;
     }

    // Marcar como validado para estilos de Bootstrap (opcional)
    form.classList.add('was-validated');

    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Publicando...`;

    const formData = new FormData(form);

    // (Opcional) Loggear FormData para depuración
    console.log("FormData Producto a enviar:");
    for (let [key, value] of formData.entries()) { /* ... log ... */ }

    try {
        // --- Endpoint para crear producto ---
        const response = await fetch(window.basePath + 'product/create', { // Necesitarás esta ruta/método
            method: 'POST',
            body: formData
        });

        const responseData = await response.json().catch(() => ({ success: false, message: 'Respuesta inválida del servidor' }));

        if (!response.ok || !responseData.success) {
            console.error("Error creando producto:", response.status, responseData);
            alert(`Error al publicar: ${responseData.message || 'Error desconocido'}`);
        } else {
            console.log("Producto creado con éxito:", responseData);
            alert(responseData.message || 'Artículo publicado en el marketplace.');
            // Cerrar modal
            const modalElement = document.getElementById('createProductModal');
            if (modalElement) bootstrap.Modal.getInstance(modalElement)?.hide();
            // Opcional: Refrescar lista de productos en la página (más avanzado)
            // refreshMarketplaceProducts();
        }
    } catch (error) {
        console.error('Error de red o JS al publicar producto:', error);
        alert('Error de conexión al publicar el artículo.');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
}

//CARGA DE DOM
document.addEventListener('DOMContentLoaded', function() {

    if (window.currentUserData) {
        createPostModal();
        createSettingsModal();
        createChangePasswordModal();
       
    } else {
        console.warn("currentUserData no está definido. Algunos modales no se inicializarán con datos de usuario.");
    }
    
    createNotificationsModal()
    createEditPostModalTemplate();   
    createDeleteConfirmModalTemplate();
    createProductModalTemplate();
    
     if (!document.getElementById('insightsModal')) { // Solo crea si no existe
         console.log("Creating #insightsModal HTML...");
         createInsightsModal();
    }
   
    
    //const settingsButton = document.getElementById('settingsButton');
    const settingsModal = new bootstrap.Modal(document.getElementById('settingsModal'));
    const postFeedContainer = document.getElementById('postsContainer') || document.body; 
    const createProductBtn = document.getElementById('createProductListingBtn'); // <-- AÑADE ESTE ID A TU BOTÓN EN marketplace.php
    const productModalElement = document.getElementById('createProductModal');

    if (createProductBtn && productModalElement) {
        createProductBtn.addEventListener('click', () => {
            const productModal = bootstrap.Modal.getOrCreateInstance(productModalElement);
            productModal.show();
        });
    } else {
        if (!createProductBtn) console.warn("Botón #createProductListingBtn no encontrado en marketplace.php.");
        if (!productModalElement) console.error("Modal #createProductModal no se pudo crear o encontrar.");
    }
    
    /*settingsButton.addEventListener('click', () => {
        const currentName = document.querySelector('.card-title').innerText;
        const currentBio = document.querySelector('.card-text').innerText;
        
        document.getElementById('profileName').value = currentName;
        document.getElementById('profileBio').value = currentBio;
        
        settingsModal.show();
    });
    */
    const notificationTrigger = document.querySelector('[title="Notificaciones"]');
    if(notificationTrigger) {
        notificationTrigger.setAttribute('data-bs-toggle', 'modal');
        notificationTrigger.setAttribute('data-bs-target', '#notificationsModal');
    }

    document.getElementById('createPostModal').addEventListener('show.bs.modal', function() {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show post-modal-backdrop';
        document.body.appendChild(backdrop);
    });

    document.getElementById('createPostModal').addEventListener('hidden.bs.modal', function() {
        const backdrops = document.querySelectorAll('.post-modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
    });

    /*
    document.getElementById('settingsButton').addEventListener('click', () => {
        document.getElementById('coverPreview').src = document.querySelector('.profile-cover').src;
        document.getElementById('avatarPreview').src = document.querySelector('.profile-img').src;
    });
    */
    

    postFeedContainer.addEventListener('click', function(event) {
        const target = event.target;
        const editButton = target.closest('.edit-post-btn');
        const deleteButton = target.closest('.delete-post-btn');

        if (editButton) {
            event.preventDefault();
            const postId = editButton.dataset.postId;
            console.log("Click en Editar Post ID:", postId);
            populateAndShowEditModal(postId);
        } else if (deleteButton) {
            event.preventDefault();
            const postId = deleteButton.dataset.postId;
            console.log("Click en Eliminar Post ID:", postId);
            populateAndShowDeleteModal(postId);
        }
    });

});

function populateAndShowEditModal(postId) {
    const postCard = document.querySelector(`.post-card[data-post-id="${postId}"]`);
    if (!postCard) {
        console.error("No se encontró la tarjeta del post con ID:", postId);
        return;
    }

    const modalElement = document.getElementById('editPostModal'); 


    // --- OBTENER DATOS DEL POST DESDE EL DOM ---
    const textElement = postCard.querySelector('.post-text');
    const currentText = textElement ? textElement.innerText : ''; // Obtiene el texto

    const privacySpan = postCard.querySelector('.post-privacy');
    const currentPrivacy = privacySpan?.dataset.privacy || 'Publico'; // Obtiene la privacidad

    // Busca CUALQUIER imagen O video dentro del contenedor de media
    const mediaContainer = postCard.querySelector('.post-media-content');
    const mediaElement = mediaContainer ? mediaContainer.querySelector('img, video') : null;
    // *** OBTENER EL ID DE MEDIA EXISTENTE ***
    const existingMediaId = mediaContainer ? mediaContainer.dataset.mediaId : null; // Obtener el ID del atributo data-

    const postIdInput = modalElement.querySelector('#editPostIdInput'); 
    const textArea = modalElement.querySelector('#editPostTextArea');
    const privacySelect = modalElement.querySelector('#editPostPrivacy');
    const mediaPreviewArea = modalElement.querySelector('#editPostPreviewArea');
    const removedIdsInput = modalElement.querySelector('#removedMediaIdsInput'); // Input para IDs eliminados
    const fileInputElement = modalElement.querySelector('#editPostMediaInput'); // Input para archivos NUEVOS

    // --- POBLAR EL MODAL ---
    if (postIdInput) postIdInput.value = postId;
    if (textArea) textArea.value = currentText;
    if (privacySelect) privacySelect.value = currentPrivacy;
    if (removedIdsInput) removedIdsInput.value = ''; // Limpiar IDs a eliminar al abrir
    if (fileInputElement) fileInputElement.value = ''; // Limpiar input de archivos nuevos

    // Limpiar previsualización anterior y mostrar la media actual (si existe)
    if (mediaPreviewArea) {
        mediaPreviewArea.innerHTML = ''; // Limpiar contenido previo
        if (mediaElement && existingMediaId) { // Asegurarse de tener el elemento Y su ID
            const previewWrapper = document.createElement('div');
            // Clases para consistencia visual con los nuevos previews
            previewWrapper.className = 'preview-item position-relative border rounded p-1';
            // Guardar el ID existente en el wrapper para referencia si es necesario
            previewWrapper.dataset.existingMediaId = existingMediaId;

            const mediaClone = mediaElement.cloneNode(true);
            mediaClone.classList.remove('mb-3');

            mediaClone.style.maxWidth = '100px'; // Estilo consistente con previews nuevos
            mediaClone.style.maxHeight = '100px';
            mediaClone.style.height = '100px'; // Forzar tamaño cuadrado
            mediaClone.style.objectFit = 'cover';
            mediaClone.classList.remove('img-fluid'); // Quitar img-fluid si forzamos tamaño

            if (mediaClone.tagName === 'VIDEO') {
                 mediaClone.className = ''; // Limpiar clases si es necesario
                 mediaClone.style.width = '100px'; // Forzar tamaño preview
                 mediaClone.style.height = '100px';
                 mediaClone.controls = false;
                 mediaClone.autoplay = false;
                 mediaClone.muted = true;
                 mediaClone.loop = false;
            }

            previewWrapper.appendChild(mediaClone);

            // *** Crear botón de eliminar para MEDIA EXISTENTE ***
            // Pasamos el ID, null para fileInput, null para objectURL, y el input de IDs eliminados
            const removeBtn = createRemoveButton(previewWrapper, existingMediaId, null, null, removedIdsInput);
            previewWrapper.appendChild(removeBtn);

            mediaPreviewArea.appendChild(previewWrapper);
        } else if (mediaElement && !existingMediaId) {
             console.warn("Se encontró media existente pero no su ID (data-media-id). No se mostrará botón de eliminar para ella.");
             // Podrías mostrarla sin botón de eliminar o no mostrarla.
              mediaPreviewArea.innerHTML = '<p class="text-muted small"><em>Media existente encontrada, pero falta su ID para poder gestionarla.</em></p>';
        }
         else {
            // No hay media existente o no se encontró
            // mediaPreviewArea.innerHTML = '<p class="text-muted small"><em>No hay imagen/video adjunto.</em></p>'; // Opcional: mensaje
         }
    } else {
         console.warn("Área de preview #editPostPreviewArea no encontrada en el modal.");
    }

    // --- MOSTRAR EL MODAL ---
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    modalInstance.show();
}

function populateAndShowDeleteModal(postId) {
    const modalElement = document.getElementById('deleteConfirmModal');
    const postIdInput = document.getElementById('deletePostIdInput');

    // Poblar el modal (solo necesitamos el ID)
    postIdInput.value = postId;

    // Mostrar el modal
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    modalInstance.show();
}

async function handleEditPostSubmit(event) {
    event.preventDefault(); // Prevenir recarga de página
    console.log("Formulario de edición enviado.");

    const form = event.target; // El formulario que disparó el evento
    const saveButton = document.getElementById('saveEditPostBtn');
    if (!saveButton || !form) {
        console.error("No se encontró el botón de guardar o el formulario.");
        return;
    }
    const originalButtonText = saveButton.innerHTML;

    // --- 1. Deshabilitar botón y mostrar spinner ---
    saveButton.disabled = true;
    saveButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...`;

    // --- 2. Crear FormData ---
    // Recoge TODOS los campos del formulario (texto, privacidad, post_id, removed_media_ids, new_post_media[])
    const formData = new FormData(form);

    // --- 3. Obtener Post ID para la URL ---
    const postId = formData.get('post_id');
    if (!postId) {
        console.error("Error: No se encontró post_id en el formulario.");
        alert("Error interno: No se pudo identificar la publicación a editar.");
        saveButton.disabled = false; // Habilitar botón
        saveButton.innerHTML = originalButtonText;
        return;
    }

    // --- 4. Construir URL del Endpoint ---
    const updateUrl = window.basePath + 'post/update/' + postId;
    console.log("Enviando actualización a:", updateUrl);

     // (Opcional) Loggear FormData para depuración
     console.log("FormData a enviar:");
     for (let [key, value] of formData.entries()) {
         if (value instanceof File) {
             console.log(`${key}: ${value.name} (type: ${value.type}, size: ${value.size})`);
         } else {
             console.log(`${key}: ${value}`);
         }
     }

    // --- 5. Realizar la llamada Fetch ---
    try {
        const response = await fetch(updateUrl, {
            method: 'POST',
            body: formData
            // NO establecer Content-Type manualmente con FormData
        });

        // Intentar parsear JSON incluso si no es OK (puede contener mensaje de error)
        const responseData = await response.json().catch(() => {
            console.error("La respuesta del servidor no es JSON válido. Status:", response.status);
            // Devolver un objeto de error genérico si el parseo falla
            return { success: false, message: `Error del servidor (${response.status}). Respuesta no válida.` };
        });

        // Verificar si la respuesta HTTP fue OK y si el backend reportó éxito
        if (!response.ok || !responseData.success) {
            console.error("Error en la respuesta del servidor (Edición):", response.status, responseData);
            const errorMessage = responseData?.message || `Error del servidor (${response.status}). Inténtalo de nuevo.`;
            alert(`Error al actualizar: ${errorMessage}`);
            // No lanzar error aquí para que finally se ejecute, el flujo ya indica fallo
        } else {
            // --- ÉXITO ---
            console.log("Respuesta del servidor (Éxito Edición):", responseData);
            alert(responseData.message || 'Publicación actualizada con éxito.');

            // --- ACTUALIZAR UI (Opción simple: Recargar) ---
            window.location.reload();

             // --- CERRAR EL MODAL ---
            const modalElement = document.getElementById('editPostModal');
            if (modalElement) {
                 const modalInstance = bootstrap.Modal.getInstance(modalElement);
                 if (modalInstance) {
                    modalInstance.hide();
                 }
            }
        }

    } catch (error) {
        // Capturar errores de red u otros errores inesperados durante el fetch
        console.error('Error durante la actualización (Fetch/Catch):', error);
        alert('Error de conexión o al procesar la solicitud de actualización. Revisa la consola.');

    } finally {
        // --- 6. Restaurar botón (SIEMPRE) ---
        saveButton.disabled = false;
        saveButton.innerHTML = originalButtonText;
    }
}

async function handleDeletePostConfirm() {
    const modalElement = document.getElementById('deleteConfirmModal');
    const postIdInput = document.getElementById('deletePostIdInput');
    const confirmButton = document.getElementById('confirmDeleteBtn');

    if (!postIdInput || !confirmButton || !modalElement) {
        console.error("Error: Faltan elementos en el modal de confirmación de borrado.");
        return;
    }

    const postId = postIdInput.value;
    if (!postId) {
        console.error("Error: No se encontró post_id en el modal de confirmación.");
        alert("Error interno: No se pudo identificar la publicación a eliminar.");
        return;
    }
    const originalButtonText = confirmButton.innerHTML;

    // --- 1. Deshabilitar botón y mostrar spinner ---
    confirmButton.disabled = true;
    confirmButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...`;

    // --- 2. Construir URL del Endpoint ---
    // Usamos POST por simplicidad, pero podrías cambiarlo a DELETE si tu backend y servidor lo permiten fácilmente
    const deleteUrl = window.basePath + 'post/delete/' + postId;
    console.log("Enviando solicitud de eliminación a:", deleteUrl);

    // --- 3. Realizar la llamada Fetch ---
    try {
        const response = await fetch(deleteUrl, {
            method: 'POST' // O 'DELETE' si ajustaste la ruta y el servidor
            // No necesita body si solo envías el ID en la URL
        });

        const responseData = await response.json().catch(() => {
            console.error("La respuesta del servidor (delete) no es JSON válido. Status:", response.status);
            return { success: false, message: `Error del servidor (${response.status}). Respuesta no válida.` };
        });

        if (!response.ok || !responseData.success) {
            console.error("Error en la respuesta del servidor (Eliminación):", response.status, responseData);
            const errorMessage = responseData?.message || `Error del servidor (${response.status}). Inténtalo de nuevo.`;
            alert(`Error al eliminar: ${errorMessage}`);
            // No lanzar error para que finally se ejecute
        } else {
            // --- ÉXITO ---
            console.log("Respuesta del servidor (Éxito Eliminación):", responseData);
            alert(responseData.message || 'Publicación eliminada con éxito.');

            // --- ACTUALIZAR UI (Eliminar el post del DOM) ---
            const postCard = document.querySelector(`.post-card[data-post-id="${postId}"]`);
            if (postCard) {
                postCard.remove(); // Elimina el elemento de la tarjeta del DOM
                console.log("Elemento del post", postId, "eliminado del DOM.");
            } else {
                 console.warn("No se encontró el elemento del post", postId, "en el DOM para eliminarlo visualmente.");
                 // Podrías recargar la página como alternativa si esto falla a veces
                 // window.location.reload();
            }

            // --- CERRAR EL MODAL ---
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
            }
        }

    } catch (error) {
        console.error('Error durante la eliminación (Fetch/Catch):', error);
        alert('Error de conexión o al procesar la solicitud de eliminación. Revisa la consola.');
    } finally {
        // --- 4. Restaurar botón (SIEMPRE) ---
        confirmButton.disabled = false;
        confirmButton.innerHTML = originalButtonText;
    }
}

