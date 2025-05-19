/**
 * community_interactions.js
 * Manejo de interacciones específicas de la sección de Comunidades en StarNest.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar listeners y funciones para el modal de creación de comunidades.
    initializeCreateCommunityModal();

    // Inicializar listeners para botones de "Unirse" / "Abandonar" comunidad (si existen en la página).
    initializeJoinLeaveButtons();
    initializeNewCommunityPostForm();

    // Aquí puedes añadir más inicializaciones, como:
    // - Carga infinita para posts de comunidad.
    // - Manejo de filtros o búsqueda dentro de la página de comunidades.
    // - Funcionalidad para moderadores de comunidad.
});


function initializeCreateCommunityModal() {
    const modalElement = document.getElementById('createCommunityModal');
    if (!modalElement) {
        // No es un error crítico si el modal no está en todas las páginas de comunidad.
        // console.warn("Modal #createCommunityModal no encontrado en esta página.");
        return;
    }

    const form = document.getElementById('createCommunityForm');
    const submitButton = document.getElementById('submitCreateCommunityBtn');
    const errorDiv = document.getElementById('createCommunityError'); // Asegúrate que este ID existe en tu modal HTML

    // Inputs y áreas de preview para las imágenes
    const pfpInput = document.getElementById('community_pfp');
    const pfpPreviewArea = document.getElementById('pfpPreviewArea');
    const coverInput = document.getElementById('community_cover');
    const coverPreviewArea = document.getElementById('coverPreviewArea');

    if (pfpInput && pfpPreviewArea) {
        pfpInput.addEventListener('change', (event) => {
            // Reutilizar la función global de modal.js o una local si prefieres
            // Asumiendo que handleFileSelect está disponible globalmente desde modal.js
            if (typeof handleFileSelect === 'function') {
                pfpPreviewArea.innerHTML = ''; // Limpiar para previsualización única
                handleFileSelect(event, pfpPreviewArea, pfpInput, null);
            } else {
                console.warn('handleFileSelect no está definida. Asegúrate que modal.js se carga antes o implementa una función local.');
                // Como fallback, puedes usar la previewSingleImage que te proporcioné antes y definirla aquí o en modal.js
                if (typeof previewSingleImage === 'function') { // Si definiste previewSingleImage
                     previewSingleImage(event, pfpPreviewArea);
                }
            }
        });
    }

    if (coverInput && coverPreviewArea) {
        coverInput.addEventListener('change', (event) => {
            if (typeof handleFileSelect === 'function') {
                coverPreviewArea.innerHTML = ''; // Limpiar para previsualización única
                handleFileSelect(event, coverPreviewArea, coverInput, null);
            } else {
                 console.warn('handleFileSelect no está definida. Asegúrate que modal.js se carga antes o implementa una función local.');
                 if (typeof previewSingleImage === 'function') {
                     previewSingleImage(event, coverPreviewArea);
                 }
            }
        });
    }

    if (form && submitButton) {
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            event.stopPropagation();

            const communityNameInput = document.getElementById('community_name');
            if (!communityNameInput.value.trim()) {
                communityNameInput.classList.add('is-invalid');
                // (Opcional) Poner el foco en el campo inválido
                communityNameInput.focus();
                showCommunityModalError('El nombre de la comunidad es obligatorio.');
                return;
            }
            communityNameInput.classList.remove('is-invalid');
            hideCommunityModalError();

            const formData = new FormData(form);
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creando...`;

            try {
                if (typeof window.basePath === 'undefined') {
                    console.error("window.basePath no está definido.");
                    showCommunityModalError('Error de configuración: No se pudo determinar la ruta base.');
                    return;
                }
                const response = await fetch(window.basePath + 'communities/create', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Buena práctica para identificar llamadas AJAX en el backend
                    }
                });

                const result = await response.json().catch(() => {
                    return { success: false, message: 'Respuesta inválida o no JSON del servidor.' };
                });

                if (response.ok && result.success) {
                    // Usar un toast o notificación más elegante si tienes un sistema para ello
                    alert(result.message || '¡Comunidad creada con éxito!');
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if(modalInstance) modalInstance.hide();

                    if (result.community_id) {
                         window.location.href = window.basePath + 'communities/' + result.community_id;
                    } else {
                         window.location.reload(); // Recargar para ver la nueva comunidad en la lista (si aplica)
                    }
                } else {
                    showCommunityModalError(result.message || 'Error al crear la comunidad. Inténtalo de nuevo.');
                }

            } catch (error) {
                console.error('Error en fetch para crear comunidad:', error);
                showCommunityModalError('Error de red o al procesar la solicitud. Revisa la consola.');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }

    // Limpiar modal al cerrarse
    modalElement.addEventListener('hidden.bs.modal', () => {
        if(form) form.reset();
        if(form) form.classList.remove('was-validated'); // Si usas validación de Bootstrap
        document.querySelectorAll('#createCommunityForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        hideCommunityModalError();
        if(pfpPreviewArea) pfpPreviewArea.innerHTML = '';
        if(coverPreviewArea) coverPreviewArea.innerHTML = '';
        // Podrías querer limpiar los inputs de archivo explícitamente también, aunque form.reset() debería hacerlo.
        if(pfpInput) pfpInput.value = "";
        if(coverInput) coverInput.value = "";
    });
}

/**
 * Muestra un mensaje de error en el modal de creación de comunidad.
 * @param {string} message
 */
function showCommunityModalError(message) {
    const errorDiv = document.getElementById('createCommunityError'); // Asegúrate que el ID coincida con tu HTML
    if (errorDiv) {
        errorDiv.innerHTML = message; // Usar innerHTML si quieres incluir algún icono o formato simple
        errorDiv.classList.remove('d-none');
    }
}

/**
 * Oculta el mensaje de error en el modal de creación de comunidad.
 */
function hideCommunityModalError() {
    const errorDiv = document.getElementById('createCommunityError');
    if (errorDiv) {
        errorDiv.classList.add('d-none');
        errorDiv.innerHTML = '';
    }
}


// -----------------------------------------------------------------------------
// SECCIÓN: UNIRSE / ABANDONAR COMUNIDADES
// -----------------------------------------------------------------------------

function initializeJoinLeaveButtons() {
    // Delegación de eventos para botones que podrían cargarse dinámicamente (ej. en búsquedas)
    document.body.addEventListener('click', function(event) {
        const joinButton = event.target.closest('.join-community-button');
        const leaveButton = event.target.closest('.leave-community-button'); // Necesitarás un botón con esta clase

        if (joinButton) {
            event.preventDefault();
            const communityId = joinButton.dataset.communityId;
            if (communityId) {
                handleCommunityMembershipAction(communityId, 'join', joinButton);
            }
        } else if (leaveButton) {
            event.preventDefault();
            const communityId = leaveButton.dataset.communityId;
            if (communityId) {
                handleCommunityMembershipAction(communityId, 'leave', leaveButton);
            }
        }
    });
}

/**
 * Maneja la acción de unirse o abandonar una comunidad.
 * @param {string} communityId El ID de la comunidad.
 * @param {'join' | 'leave'} action La acción a realizar.
 * @param {HTMLElement} buttonEl El elemento botón que disparó la acción.
 */
async function handleCommunityMembershipAction(communityId, action, buttonEl) {
    if (!communityId || !action || !buttonEl) return;

    const originalButtonText = buttonEl.innerHTML;
    buttonEl.disabled = true;
    buttonEl.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

    // Determinar el endpoint basado en la acción
    const endpoint = window.basePath + `communities/${communityId}/${action}`; // Ej: /communities/123/join

    try {
        const response = await fetch(endpoint, {
            method: 'POST', // O GET si tu backend espera eso para estas acciones y no hay CSRF
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                // Podrías necesitar un token CSRF aquí si tu backend lo requiere para POSTs
                // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });

        const result = await response.json().catch(() => ({
            success: false, message: 'Respuesta inválida del servidor.'
        }));

        if (response.ok && result.success) {
            alert(result.message || `Acción de ${action} completada.`);
            // Actualizar la UI:
            // - Cambiar el texto del botón (ej. "Unirse" a "Miembro" o "Abandonar")
            // - Actualizar el contador de miembros si se muestra
            // - Potencialmente, redirigir o recargar partes de la página
            window.location.reload(); // La opción más simple para reflejar el cambio

        } else {
            alert(result.message || `Error al intentar ${action} la comunidad.`);
            buttonEl.innerHTML = originalButtonText; // Restaurar en caso de error
        }

    } catch (error) {
        console.error(`Error en fetch para ${action} comunidad:`, error);
        alert(`Error de red al intentar ${action} la comunidad.`);
        buttonEl.innerHTML = originalButtonText;
    } finally {
        // Aunque recarguemos, es buena práctica habilitar si algo falla antes de la recarga
        if (buttonEl.disabled) { // Solo si no se recargó la página aún
             buttonEl.disabled = false;
             // Si no se recargó, el texto debe ser el original si hubo error.
             // Si hubo éxito y no se recarga, aquí se actualizaría el texto del botón dinámicamente.
        }
    }
}



function previewSingleImage(event, previewAreaElement) { // Esta es la función que te pasé antes
    if (!previewAreaElement || !event || !event.target.files) return;
    previewAreaElement.innerHTML = ''; // Limpiar preview anterior
    const file = event.target.files[0];

    if (file && file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.maxWidth = '200px';
        img.style.maxHeight = '200px';
        img.style.objectFit = 'cover';
        img.classList.add('img-thumbnail', 'mt-2'); // Añadir clases de Bootstrap
        img.onload = () => URL.revokeObjectURL(img.src);
        previewAreaElement.appendChild(img);
    } else if (file) {
        previewAreaElement.innerHTML = '<small class="text-danger d-block mt-1">El archivo seleccionado no es una imagen válida.</small>';
    }
}


// --- SECCIÓN: PUBLICACIONES DENTRO DE LA COMUNIDAD ---

/**
 * Previsualiza la media para el nuevo post en la comunidad.
 * Se llama desde el onchange del input file.
 */
function previewCommunityPostMedia(event) {
    const previewArea = document.getElementById('newCommunityPostPreviewArea');
    const fileInput = document.getElementById('newCommunityPostMedia'); // El input mismo
    if (!previewArea || !fileInput) return;

    // Reutilizar la función global de modal.js si existe y es adecuada,
    // o una versión simplificada para múltiples archivos aquí.
    if (typeof handleFileSelect === 'function') { // Asumiendo que handleFileSelect de modal.js está disponible
        handleFileSelect(event, previewArea, fileInput, null); // El último null es para 'removedMediaIdsInput'
    } else {
        // Fallback simple si handleFileSelect no está (o crea una versión local aquí)
        previewArea.innerHTML = ''; // Limpiar
        const files = event.target.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.style.maxWidth = '100px';
                img.style.maxHeight = '100px';
                img.classList.add('img-thumbnail', 'm-1');
                img.onload = () => URL.revokeObjectURL(img.src);
                previewArea.appendChild(img);
            }
        }
        console.warn("handleFileSelect no encontrada, usando preview básica para posts de comunidad.");
    }
}

let postButtonListenerAttached = false; 

function initializeNewCommunityPostForm() {
    const submitButton = document.getElementById('submitNewCommunityPostBtn');
    if (!submitButton) {
        // console.log("Botón submitNewCommunityPostBtn no encontrado.");
        return;
    }

    // Prevenir múltiples listeners en el MISMO botón si esta función se llama varias veces
    if (submitButton.dataset.listenerAttached === 'true') {
        // console.log("Listener para submitNewCommunityPostBtn ya adjunto.");
        return;
    }

    console.log("Adjuntando listener de clic a submitNewCommunityPostBtn"); // <-- VER ESTE LOG

    submitButton.addEventListener('click', async function(event) {
        event.preventDefault(); // ¡Importante!
        event.stopPropagation(); // Puede ser útil

        console.log("CLIC en submitNewCommunityPostBtn. Community ID:", this.dataset.communityId); // <-- VER ESTE LOG

        if (this.disabled) {
            console.warn("Doble clic rápido detectado o el botón ya está procesando.");
            return; 
        }
        this.disabled = true;
        const originalButtonText = this.innerHTML;
        this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> Publicando...`;

        const communityId = this.dataset.communityId;
        const postTextElement = document.getElementById('newCommunityPostText');
        const postText = postTextElement ? postTextElement.value.trim() : '';
        const mediaInputElement = document.getElementById('newCommunityPostMedia');
        const mediaFiles = mediaInputElement ? mediaInputElement.files : null;
        const previewArea = document.getElementById('newCommunityPostPreviewArea'); // Para limpiar después

        // Validaciones básicas en el frontend (ya las tienes en el backend también)
        if (!postText && (!mediaFiles || mediaFiles.length === 0)) {
            alert("Escribe algo o añade multimedia para publicar.");
            this.disabled = false; // Rehabilitar
            this.innerHTML = originalButtonText;
            return;
        }

        const formData = new FormData();
        formData.append('post_text', postText);
        formData.append('post_privacy', 'Publico'); // O la privacidad que corresponda
        formData.append('community_id', communityId);

        if (mediaFiles) {
            for (let i = 0; i < mediaFiles.length; i++) {
                if (mediaFiles[i]) { // Asegurarse de que el archivo existe en el índice
                   formData.append('post_media[]', mediaFiles[i]);
                }
            }
        }

        try {
            const response = await fetch(window.basePath + 'post/create', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json().catch(() => {
                return { success: false, message: 'Respuesta inválida o no JSON del servidor. Revisa la pestaña Network.' };
            });

            if (result.success) {
                alert(result.message || "¡Publicado en la comunidad!");
                if(postTextElement) postTextElement.value = '';
                if(mediaInputElement) mediaInputElement.value = ''; 
                if(previewArea) previewArea.innerHTML = ''; 
                window.location.reload(); 
            } else {
                alert("Error al publicar: " + (result.message || "No se pudo publicar."));
                // Solo rehabilitar si no hay recarga/redirección
                this.disabled = false;
                this.innerHTML = originalButtonText;
            }
        } catch (error) {
            console.error("Error en fetch al publicar en comunidad:", error);
            alert("Error de conexión al publicar en la comunidad.");
            this.disabled = false;
            this.innerHTML = originalButtonText;
        } 
        // El finally se complica con window.location.reload(). 
        // Si la recarga es segura tras éxito, el estado del botón no importa tanto.
        // Si el fetch falla o result.success es false, SÍ necesitamos rehabilitar.
    });
    submitButton.dataset.listenerAttached = 'true'; // Marcar que el listener fue adjuntado
}

function initializeLoadMoreCommunityPostsButton() {
    const loadMoreBtn = document.querySelector('.load-more-community-posts');
    if (!loadMoreBtn) return;

    loadMoreBtn.addEventListener('click', async function() {
        const communityId = this.dataset.communityId;
        let offset = parseInt(this.dataset.offset || 0, 10);
        const limit = 10; // O el límite que prefieras

        this.disabled = true;
        this.textContent = 'Cargando...';

        try {
            // Necesitarás un endpoint para esto, ej: /communities/{id}/posts?offset=X&limit=Y
            // Asumiré que la ruta que definimos antes '/communities/{id}/posts' se usará para esto
            const response = await fetch(`${window.basePath}communities/${communityId}/posts?offset=${offset}&limit=${limit}`);
            if (!response.ok) {
                throw new Error(`Error HTTP ${response.status} al cargar más posts.`);
            }
            const result = await response.json(); // Asume que el endpoint devuelve HTML o JSON de posts

            if (result.success && result.html_posts) { // Si el backend devuelve HTML pre-renderizado
                const postsContainer = document.getElementById('communityPostsContainer');
                if (postsContainer) {
                    postsContainer.insertAdjacentHTML('beforeend', result.html_posts);
                }
                offset += result.loaded_count || 0; // El backend debe decir cuántos cargó
                this.dataset.offset = offset;

                if (!result.has_more) { // El backend debe indicar si hay más posts
                    this.remove(); // Ocultar o quitar el botón si no hay más
                }
            } else if (result.success && Array.isArray(result.data)) { // Si devuelve JSON de posts
                 const postsContainer = document.getElementById('communityPostsContainer');
                 if(postsContainer) {
                    result.data.forEach(postData => {
                        // Aquí necesitarías una función para renderizar un post desde JSON a HTML
                        // y añadirlo a postsContainer. Esta función sería similar a la lógica PHP.
                        // const postElement = createPostElementFromData(postData);
                        // postsContainer.appendChild(postElement);
                        console.warn("Renderizado de posts desde JSON para 'cargar más' no implementado completamente en JS.");
                    });
                 }
                 offset += result.data.length;
                 this.dataset.offset = offset;
                 if (result.data.length < limit) { // Una forma simple de ver si hay más
                    this.remove();
                 }

            } else {
                // No hay más posts o hubo un error
                this.remove(); // Ocultar o quitar el botón
                if(result.message) alert(result.message);
            }
        } catch (error) {
            console.error("Error al cargar más posts de comunidad:", error);
            this.textContent = 'Error al cargar';
        } finally {
            if (document.contains(this)) { // Si el botón no fue removido
                 this.disabled = false;
                 this.textContent = 'Cargar más publicaciones';
            }
        }
    });
}