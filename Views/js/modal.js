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
                                <img src="./pictures/fuyu.jpg" class="rounded-circle me-3" width="40" height="40">
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

    const profilePic = window.currentUserData?.profilePicSrc || (window.basePath + 'Views/pictures/defaultpfp.jpg'); 
    const userFullName = window.currentUserData?.nombreCompleto || 'Usuario';
    const userFirstName = window.currentUserData?.nombre || 'Usuario';
    const defaultPrivacy = window.currentUserData?.privacidad || 'Publico';

    const modalHTML = `
    <div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel">
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
            handleFileSelect(event, previewArea); 
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

function handleFileSelect(event, previewAreaElement) {
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
            filePromises.push(createImagePreview(file, previewAreaElement, event.target));
        } else if (file.type.startsWith('video/')) {
            filePromises.push(createVideoPreview(file, previewAreaElement, event.target));
        } else {
            console.warn(`Archivo omitido por tipo no soportado: ${file.name} (${file.type})`);
             const errorPreview = createErrorPreview(`"${file.name}" (Tipo no soportado).`);
             previewAreaElement.appendChild(errorPreview);
        }
    }

}


function createImagePreview(file, previewAreaElement, fileInputElement) {
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

            const removeBtn = createRemoveButton(previewWrapper, file, fileInputElement);

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


function createVideoPreview(file, previewAreaElement, fileInputElement) {
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

        const removeBtn = createRemoveButton(previewWrapper, file, fileInputElement, objectURL);
        
        removeBtn.style.top = '5px';
        removeBtn.style.right = '5px'; 

        previewWrapper.appendChild(video);
        previewWrapper.appendChild(removeBtn);
        previewAreaElement.appendChild(previewWrapper);
        resolve();
    });
}

function createErrorPreview(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'preview-item preview-error text-danger border rounded p-2 d-flex align-items-center';
    errorDiv.style.width = '100px';
    errorDiv.style.height = '100px';
    errorDiv.style.fontSize = '0.8em';
    errorDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i> ${message}`;
    return errorDiv;
}

function createRemoveButton(wrapper, fileToRemove, fileInputElement, objectUrlToRemove = null) {
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button'; 
    removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0 d-flex justify-content-center align-items-center';
    removeBtn.innerHTML = '×'; 
    removeBtn.style.width = '20px';
    removeBtn.style.height = '20px';
    removeBtn.style.borderRadius = '50%';
    removeBtn.style.lineHeight = '1';
    removeBtn.title = `Quitar ${fileToRemove.name}`;

    removeBtn.onclick = () => {
        wrapper.remove(); 

        
        if (objectUrlToRemove) {
            URL.revokeObjectURL(objectUrlToRemove);
        }

        
        const dataTransfer = new DataTransfer();
        const currentFiles = Array.from(fileInputElement.files);

        
        currentFiles.forEach(file => {
            if (file !== fileToRemove) {
                dataTransfer.items.add(file);
            }
        });

        fileInputElement.files = dataTransfer.files;

        console.log(`Archivo "${fileToRemove.name}" marcado para no ser subido.`);
    };
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
                            <button type="button" class="btn btn-md btn-custom active" data-period="7">7 días</button>
                            <button type="button" class="btn btn-md btn-custom" data-period="30">30 días</button>
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

function showInsightsModal() {
    if (!document.getElementById('insightsModal')) {
        createInsightsModal();
    }
    const insightsModal = new bootstrap.Modal('#insightsModal');
    insightsModal.show();
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
    
    const settingsButton = document.getElementById('settingsButton');
    const settingsModal = new bootstrap.Modal(document.getElementById('settingsModal'));
    
    settingsButton.addEventListener('click', () => {
        const currentName = document.querySelector('.card-title').innerText;
        const currentBio = document.querySelector('.card-text').innerText;
        
        document.getElementById('profileName').value = currentName;
        document.getElementById('profileBio').value = currentBio;
        
        settingsModal.show();
    });

    const notificationTrigger = document.querySelector('[title="Notificaciones"]');
    if(notificationTrigger) {
        notificationTrigger.setAttribute('data-bs-toggle', 'modal');
        notificationTrigger.setAttribute('data-bs-target', '#notificationsModal');
    }

    document.getElementById('postModal').addEventListener('show.bs.modal', function() {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show post-modal-backdrop';
        document.body.appendChild(backdrop);
    });

    document.getElementById('postModal').addEventListener('hidden.bs.modal', function() {
        const backdrops = document.querySelectorAll('.post-modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
    });

    document.getElementById('settingsButton').addEventListener('click', () => {
        document.getElementById('coverPreview').src = document.querySelector('.profile-cover').src;
        document.getElementById('avatarPreview').src = document.querySelector('.profile-img').src;
    });

});


