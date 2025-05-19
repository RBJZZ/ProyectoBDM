// Views/js/post_interactions.js
document.addEventListener('DOMContentLoaded', function () {
    const basePath = window.basePath || '/';
    const loggedInUserId = window.currentUserData?.userId;

    // --- MANEJO DE LIKES ---
    document.body.addEventListener('click', function(event) {
        const likeButton = event.target.closest('.like-button');
        if (likeButton) {
            event.preventDefault();
            if (!loggedInUserId) {
                alert("Debes iniciar sesión para dar 'Me gusta'.");
                // Opcional: redirigir a login window.location.href = basePath + 'login';
                return;
            }

            const postId = likeButton.dataset.postId;
            const isCurrentlyLiked = likeButton.classList.contains('liked');
            const action = isCurrentlyLiked ? 'unlike' : 'like';
            const icon = likeButton.querySelector('i');
            const countSpan = likeButton.querySelector('.like-count');

            fetch(`${basePath}post/${postId}/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    likeButton.classList.toggle('liked', data.liked_by_user);
                    likeButton.classList.toggle('text-primary', data.liked_by_user);
                    likeButton.classList.toggle('fw-bold', data.liked_by_user);
                    likeButton.classList.toggle('text-muted', !data.liked_by_user);
                    
                    if (icon) {
                        icon.className = data.liked_by_user ? 'bi bi-hand-thumbs-up-fill' : 'bi bi-hand-thumbs-up';
                    }
                    if (countSpan) {
                        countSpan.textContent = data.new_like_count;
                    }
                } else {
                    alert(data.message || 'Error al procesar el like.');
                }
            })
            .catch(error => {
                console.error('Error en like/unlike:', error);
                alert('Error de conexión al procesar el like.');
            });
        }
    });

     // --- MANEJO DEL BOTÓN "COMENTAR" PARA ABRIR EL MODAL ---
    document.body.addEventListener('click', function(event) {
        const commentButton = event.target.closest('.comment-button');
        if (commentButton) {
            event.preventDefault();
            const postId = commentButton.dataset.postId;
            
            const modalElement = document.getElementById('commentsModal');
            if (!modalElement) {
                console.error("Modal de comentarios #commentsModal no encontrado en el DOM.");
                // Intentar crearlo si no existe (esto es una salvaguarda, debería crearse en modal.js)
                if (typeof createCommentsModalTemplate === "function") {
                    createCommentsModalTemplate(); // Asegúrate que esta función esté disponible globalmente o importada
                } else {
                    alert("Error: Funcionalidad de comentarios no disponible.");
                    return;
                }
            }
            
            commentsModalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('commentsModal'));

            // Poblar el input oculto con el postId
            const postIdInput = document.getElementById('postIdCommentModalInput');
            if (postIdInput) {
                postIdInput.value = postId;
            } else {
                console.error("Input #postIdCommentModalInput no encontrado en el modal de comentarios.");
            }
            
            // (Opcional) Poblar información del post en el modal
            populatePostInfoInModal(postId); // Implementar esta función

            loadCommentsForModal(postId); // Cargar comentarios en el modal
            commentsModalInstance.show();
        }
    });

    // --- FUNCIÓN PARA POBLAR INFORMACIÓN DEL POST EN EL MODAL (OPCIONAL) ---
    function populatePostInfoInModal(postId) {
        const postCard = document.querySelector(`.post-card[data-post-id="${postId}"]`);
        const modalPostInfoDiv = document.getElementById('commentsModalPostInfo');

        if (postCard && modalPostInfoDiv) {
            const authorNameElement = postCard.querySelector('.card-title-name'); // Asume que esta clase existe en el nombre del autor
            const postTextElement = postCard.querySelector('.post-text');
            
            let postInfoHTML = '';
            if (authorNameElement) {
                postInfoHTML += `<small class="text-muted">Comentarios para la publicación de <strong>${authorNameElement.textContent.trim()}</strong></small>`;
            }
            // Podrías añadir un snippet del texto del post si es corto
            // if (postTextElement) {
            //     postInfoHTML += `<p class="small mt-1 text-muted"><em>"${postTextElement.textContent.substring(0, 50)}..."</em></p>`;
            // }
            modalPostInfoDiv.innerHTML = postInfoHTML;
            modalPostInfoDiv.classList.remove('d-none');
        }
    }


    // --- FUNCIÓN PARA CARGAR COMENTARIOS EN EL MODAL ---
    async function loadCommentsForModal(postId) {
        const containerElement = document.getElementById('commentsModalExistingComments');
        if (!containerElement) return;

        containerElement.innerHTML = '<div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...</div>';
        
        try {
            const response = await fetch(`${basePath}post/${postId}/comments?limit=50&offset=0`); // Cargar más comentarios por defecto
            if (!response.ok) throw new Error('Error del servidor al cargar comentarios.');
            const data = await response.json();

            if (data.success && data.comments) {
                containerElement.innerHTML = ''; // Limpiar "cargando"
                if (data.comments.length > 0) {
                    data.comments.forEach(commentData => {
                        const commentHtml = formatCommentForModal(commentData.comment, commentData.author); // Usar una función de formato específica o la misma
                        containerElement.insertAdjacentHTML('beforeend', commentHtml);
                    });
                } else {
                    containerElement.innerHTML = '<p class="text-muted small text-center p-3 no-comments-yet">No hay comentarios aún. ¡Sé el primero!</p>';
                }
            } else {
                containerElement.innerHTML = `<p class="text-danger small text-center p-3">${data.message || 'No se pudieron cargar los comentarios.'}</p>`;
            }
        } catch (error) {
            console.error('Error al cargar comentarios para el modal:', error);
            containerElement.innerHTML = '<p class="text-danger small text-center p-3">Error al cargar comentarios.</p>';
        }
    }
    
    // --- MANEJO DE ENVÍO DE NUEVO COMENTARIO DESDE EL MODAL ---
    async function handleNewCommentSubmitInModal(event) {
        event.preventDefault();
        if (!loggedInUserId) {
            alert("Debes iniciar sesión para comentar.");
            if(commentsModalInstance) commentsModalInstance.hide(); // Cerrar modal si no está logueado
            return;
        }

        const form = event.target;
        const postIdInput = document.getElementById('postIdCommentModalInput');
        const postId = postIdInput ? postIdInput.value : null;
        const commentTextArea = form.querySelector('textarea[name="comment_text_modal"]');
        const commentText = commentTextArea ? commentTextArea.value.trim() : '';

        if (!postId || !commentText) {
            alert("El comentario no puede estar vacío y debe estar asociado a un post.");
            return;
        }
        
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonIcon = submitButton.innerHTML; // Guardar el icono
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        const formData = new FormData();
        formData.append('comment_text', commentText);
        // formData.append('reply_to_id', idDeRespuestaSiExiste); // Para futuras respuestas

        try {
            const response = await fetch(`${basePath}post/${postId}/comment`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success && data.comment_data && data.comment_data.comment && data.comment_data.author) {
                const existingCommentsDiv = document.getElementById('commentsModalExistingComments');
                const noCommentsMsg = existingCommentsDiv.querySelector('.no-comments-yet');
                if (noCommentsMsg) noCommentsMsg.remove();

                const newCommentHtml = formatCommentForModal(data.comment_data.comment, data.comment_data.author);
                existingCommentsDiv.insertAdjacentHTML('beforeend', newCommentHtml);
                form.reset(); // Limpiar el textarea
                
                // Actualizar contador de comentarios en el botón original del post en el feed/perfil
                const mainCommentButtonSpan = document.querySelector(`.post-card[data-post-id="${postId}"] .comment-button .comment-count`);
                if (mainCommentButtonSpan && data.new_comment_count !== undefined) {
                    mainCommentButtonSpan.textContent = data.new_comment_count;
                }
                // Hacer scroll al nuevo comentario
                 existingCommentsDiv.scrollTop = existingCommentsDiv.scrollHeight;

            } else {
                alert(data.message || 'Error al publicar el comentario.');
            }
        } catch (error) {
            console.error('Error al enviar comentario desde modal:', error);
            alert('Error de conexión al enviar el comentario.');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonIcon; // Restaurar el icono
        }
    }

    // Función para formatear un comentario en HTML para el modal
    // (Puedes reutilizar tu `formatComment` o adaptarla si es necesario)
    function formatCommentForModal(comment, author) {
        const authorPic = (author.usr_foto_perfil_base64 && author.usr_foto_perfil_mime) ? 
            `data:${author.usr_foto_perfil_mime};base64,${author.usr_foto_perfil_base64}` : 
            `${basePath}Views/pictures/defaultpfp.jpg`;
        const authorFullName = `${author.usr_nombre || ''} ${author.usr_apellido_paterno || ''}`.trim();
        const authorUsername = author.usr_username || 'usuario';
        const authorProfileLink = `${basePath}profile/${author.usr_id}`;
        const commentDate = new Date(comment.int_fecha).toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'});

        // Podrías añadir opciones de eliminar/editar comentario aquí si el loggedInUserId es el autor del comentario
        let commentOptions = '';
        if (loggedInUserId && loggedInUserId == author.usr_id) {
            commentOptions = `
                <div class="dropdown ms-auto">
                    <button class="btn btn-sm btn-link text-muted py-0 px-1 no-arrow" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item edit-comment-btn small" href="#" data-comment-id="${comment.int_id_interaccion}">Editar</a></li>
                        <li><a class="dropdown-item delete-comment-btn small" href="#" data-comment-id="${comment.int_id_interaccion}">Eliminar</a></li>
                    </ul>
                </div>`;
        }

        return `
            <div class="comment d-flex mb-2" data-comment-id="${comment.int_id_interaccion}">
                <a href="${authorProfileLink}" class="flex-shrink-0">
                    <img src="${authorPic}" class="rounded-circle me-2" width="35" height="35" alt="${authorUsername}" style="object-fit: cover;">
                </a>
                <div class="comment-body bg-light p-2 rounded flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="${authorProfileLink}" class="text-dark text-decoration-none fw-bold small">${authorFullName}</a>
                            <span class="text-muted small ms-1">@${authorUsername}</span>
                        </div>
                        ${commentOptions}
                    </div>
                    <p class="comment-text mb-1 small mt-1">${comment.int_texto_comentario.replace(/\n/g, '<br>')}</p>
                    <small class="text-muted comment-date" style="font-size: 0.7rem;">${commentDate}</small>
                </div>
            </div>
        `;
    }

    async function handleNewCommentSubmitInModal(event) {
        const loggedInUserId = window.currentUserData?.userId;
        event.preventDefault();
        if (!loggedInUserId) {
            alert("Debes iniciar sesión para comentar.");
            if(commentsModalInstance) commentsModalInstance.hide(); // Cerrar modal si no está logueado
            return;
        }

        const form = event.target;
        const postIdInput = document.getElementById('postIdCommentModalInput');
        const postId = postIdInput ? postIdInput.value : null;
        const commentTextArea = form.querySelector('textarea[name="comment_text_modal"]');
        const commentText = commentTextArea ? commentTextArea.value.trim() : '';

        if (!postId || !commentText) {
            alert("El comentario no puede estar vacío y debe estar asociado a un post.");
            return;
        }
        
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonIcon = submitButton.innerHTML; // Guardar el icono
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        const formData = new FormData();
        formData.append('comment_text', commentText);
        // formData.append('reply_to_id', idDeRespuestaSiExiste); // Para futuras respuestas

        try {
            const response = await fetch(`${basePath}post/${postId}/comment`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success && data.comment_data && data.comment_data.comment && data.comment_data.author) {
                const existingCommentsDiv = document.getElementById('commentsModalExistingComments');
                const noCommentsMsg = existingCommentsDiv.querySelector('.no-comments-yet');
                if (noCommentsMsg) noCommentsMsg.remove();

                const newCommentHtml = formatCommentForModal(data.comment_data.comment, data.comment_data.author);
                existingCommentsDiv.insertAdjacentHTML('beforeend', newCommentHtml);
                form.reset(); // Limpiar el textarea
                
                // Actualizar contador de comentarios en el botón original del post en el feed/perfil
                const mainCommentButtonSpan = document.querySelector(`.post-card[data-post-id="${postId}"] .comment-button .comment-count`);
                if (mainCommentButtonSpan && data.new_comment_count !== undefined) {
                    mainCommentButtonSpan.textContent = data.new_comment_count;
                }
                // Hacer scroll al nuevo comentario
                 existingCommentsDiv.scrollTop = existingCommentsDiv.scrollHeight;

            } else {
                alert(data.message || 'Error al publicar el comentario.');
            }
        } catch (error) {
            console.error('Error al enviar comentario desde modal:', error);
            alert('Error de conexión al enviar el comentario.');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonIcon; // Restaurar el icono
        }
    }


    //////////// MODAL DE COMENTARIOS

    function createCommentsModalTemplate() {
        const basePath = window.basePath || '/';
        // Foto del usuario logueado para el input de "nuevo comentario"
        const currentUserProfilePic = window.currentUserData?.profilePicSrc || (basePath + 'Views/pictures/defaultpfp.jpg');

        const modalHTML = `
        <div class="modal fade" id="commentsModal" tabindex="-1" aria-labelledby="commentsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"> 
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="commentsModalLabel">Comentarios</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="commentsModalPostInfo" class="mb-3 border-bottom pb-2 d-none">
                        </div>

                        <div id="commentsModalExistingComments" class="mb-3" style="max-height: 40vh; overflow-y: auto;">
                            <p class="text-muted text-center">Cargando comentarios...</p>
                        </div>
                        
                        <div id="commentsModalFormContainer">
                            <form id="newCommentFormInModal">
                                <input type="hidden" name="post_id_comment_modal" id="postIdCommentModalInput">
                                <div class="d-flex align-items-start">
                                    <img src="${currentUserProfilePic}" class="rounded-circle me-2" width="40" height="40" alt="Mi Perfil" style="object-fit: cover;">
                                    <textarea name="comment_text_modal" class="form-control me-2" rows="2" placeholder="Escribe un comentario..." required></textarea>
                                    <button type="submit" class="btn btn-custom mt-1 p-3">
                                        <i class="bi bi-send-fill"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Añadir listener al formulario del modal
        const commentFormInModal = document.getElementById('newCommentFormInModal');
        if (commentFormInModal) {
            commentFormInModal.addEventListener('submit', handleNewCommentSubmitInModal);
        }

        // Limpiar al cerrar
        const commentsModalElement = document.getElementById('commentsModal');
        if (commentsModalElement) {
            commentsModalElement.addEventListener('hidden.bs.modal', () => {
                document.getElementById('commentsModalExistingComments').innerHTML = '<p class="text-muted text-center">Cargando comentarios...</p>';
                document.getElementById('commentsModalPostInfo').innerHTML = '';
                document.getElementById('commentsModalPostInfo').classList.add('d-none');
                if (commentFormInModal) commentFormInModal.reset();
                const postIdInput = document.getElementById('postIdCommentModalInput');
                if(postIdInput) postIdInput.value = '';
            });
        }
    }



});