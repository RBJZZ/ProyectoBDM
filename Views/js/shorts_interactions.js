// Views/js/shorts_interactions.js
document.addEventListener('DOMContentLoaded', function () {
    const basePath = window.basePath || '/'; // Asegúrate que window.basePath esté definido
    const loggedInUserId = window.currentUserData?.userId; // Asegúrate que window.currentUserData esté definido

    const reelsWrapper = document.getElementById('reelsWrapper');
    let commentsModalInstance = null;
    if (document.getElementById('shortCommentsModal')) {
        commentsModalInstance = new bootstrap.Modal(document.getElementById('shortCommentsModal'));
    }


    // --- MANEJO DE LIKES PARA SHORTS ---
    document.body.addEventListener('click', function(event) {
        const likeButton = event.target.closest('.like-short-button');
        if (likeButton) {
            event.preventDefault();
            if (!loggedInUserId) {
                alert("Debes iniciar sesión para dar 'Me gusta'.");
                // Considerar redirigir a login: window.location.href = basePath + 'login';
                return;
            }

            const shortId = likeButton.dataset.shortId;
            const icon = likeButton.querySelector('i');
            const countSpan = likeButton.closest('.control-group').querySelector('.like-count');

            // Optimistic update (opcional, pero mejora UX)
            const واصلCurrentlyLiked = likeButton.classList.contains('liked');
            likeButton.classList.toggle('liked', !واصلCurrentlyLiked);
            likeButton.classList.toggle('text-primary', !واصلCurrentlyLiked);
            likeButton.classList.toggle('fw-bold', !واصلCurrentlyLiked);
            likeButton.classList.toggle('text-muted', واصلCurrentlyLiked);
            if (icon) icon.className = !واصلCurrentlyLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
            if (countSpan) {
                let currentCount = parseInt(countSpan.textContent.replace(/K|M|B|T/g, '')) || 0;
                // Simplificación: Asumir que K, M no están en el número base para el incremento/decremento simple.
                // Una lógica más robusta parsearía "14K" a 14000.
                // Para la UI, esto es más complejo, así que lo dejaremos con el texto por ahora
                // y el backend devolverá el número formateado.
            }


            fetch(`${basePath}short/toggle_like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', // Enviar JSON
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ short_id: shortId }) // Enviar short_id en el body
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; }); // Manejar errores JSON del backend
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Actualizar UI con datos del servidor (más fiable)
                    likeButton.classList.toggle('liked', data.liked_status);
                    likeButton.classList.toggle('text-primary', data.liked_status);
                    likeButton.classList.toggle('fw-bold', data.liked_status);
                    likeButton.classList.toggle('text-muted', !data.liked_status);
                    if (icon) icon.className = data.liked_status ? 'bi bi-heart-fill' : 'bi bi-heart';
                    if (countSpan) countSpan.textContent = numberFormatShortJS(data.new_like_count); // Usar helper JS
                } else {
                    alert(data.message || 'Error al procesar el like.');
                    // Revertir optimistic update si falló
                    likeButton.classList.toggle('liked', واصلCurrentlyLiked); // Revertir
                    // ... (revertir otras clases)
                }
            })
            .catch(error => {
                console.error('Error en like/unlike de short:', error);
                alert(error.message || 'Error de conexión al procesar el like.');
                // Revertir optimistic update
                 likeButton.classList.toggle('liked', واصلCurrentlyLiked); // Revertir
                 // ... (revertir otras clases)
            });
        }
    });

    // --- MANEJO DEL BOTÓN "COMENTAR" PARA ABRIR EL MODAL DE COMENTARIOS DE SHORTS ---
    document.body.addEventListener('click', function(event) {
        const commentButton = event.target.closest('.comment-short-button');
        if (commentButton && commentsModalInstance) {
            event.preventDefault();
            const shortId = commentButton.dataset.shortId;
            
            const postIdInput = document.getElementById('shortIdCommentModalInput');
            if (postIdInput) {
                postIdInput.value = shortId;
            } else {
                console.error("Input #shortIdCommentModalInput no encontrado en el modal de comentarios de shorts.");
            }
            
            // Opcional: Poblar información del short en el modal
            // populateShortInfoInModal(shortId); // Implementar si se desea

            loadCommentsForShortModal(shortId);
            commentsModalInstance.show();
        }
    });

    async function loadCommentsForShortModal(shortId) {
        const containerElement = document.getElementById('shortCommentsModalExistingComments');
        if (!containerElement) return;

        containerElement.innerHTML = '<div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...</div>';
        try {
            const response = await fetch(`${basePath}api/short/comments?short_id=${shortId}&limit=50&offset=0`);
            if (!response.ok) throw new Error('Error del servidor al cargar comentarios del short.');
            
            const data = await response.json();

            if (data.success && data.comments) {
                containerElement.innerHTML = ''; 
                if (data.comments.length > 0) {
                    data.comments.forEach(commentData => {
                        // Usar una función similar a la de post_interactions.js para formatear comentarios
                        const commentHtml = formatShortCommentForModal(commentData.comment, commentData.author);
                        containerElement.insertAdjacentHTML('beforeend', commentHtml);
                    });
                } else {
                    containerElement.innerHTML = '<p class="text-muted small text-center p-3 no-comments-yet">No hay comentarios aún. ¡Sé el primero!</p>';
                }
            } else {
                containerElement.innerHTML = `<p class="text-danger small text-center p-3">${data.message || 'No se pudieron cargar los comentarios.'}</p>`;
            }
        } catch (error) {
            console.error('Error al cargar comentarios para el modal de shorts:', error);
            containerElement.innerHTML = '<p class="text-danger small text-center p-3">Error al cargar comentarios.</p>';
        }
    }
    
    // --- MANEJO DE ENVÍO DE NUEVO COMENTARIO DE SHORT DESDE EL MODAL ---
    const newShortCommentForm = document.getElementById('newShortCommentFormInModal');
    if (newShortCommentForm) {
        newShortCommentForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            if (!loggedInUserId) {
                alert("Debes iniciar sesión para comentar.");
                if(commentsModalInstance) commentsModalInstance.hide();
                return;
            }

            const form = event.target;
            const shortIdInput = document.getElementById('shortIdCommentModalInput');
            const shortId = shortIdInput ? shortIdInput.value : null;
            const commentTextArea = form.querySelector('textarea[name="comment_text_modal"]');
            const commentText = commentTextArea ? commentTextArea.value.trim() : '';

            if (!shortId || !commentText) {
                alert("El comentario no puede estar vacío."); return;
            }
            
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonContent = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            const formData = new FormData();
            formData.append('short_id', shortId);
            formData.append('comment_text', commentText);

            try {
                const response = await fetch(`${basePath}short/add_comment`, {
                    method: 'POST',
                    body: formData,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                const data = await response.json();

                if (data.success && data.comment_id) { // Asumiendo que el backend devuelve el nuevo comment_id
                    // Recargar comentarios o añadir el nuevo dinámicamente
                    loadCommentsForShortModal(shortId); // La forma más simple es recargar
                    form.reset(); 
                    // Actualizar contador de comentarios en el botón original del short en el feed
                    const mainCommentButton = document.querySelector(`.video-reel[data-short-id="${shortId}"] .comment-short-button .comment-count`);
                    if (mainCommentButton) {
                         // El backend debería devolver el nuevo conteo, o incrementarlo aquí (menos fiable)
                         // mainCommentButton.textContent = parseInt(mainCommentButton.textContent) + 1;
                         // Por ahora, no actualizamos el contador principal aquí sin un nuevo conteo del backend.
                    }
                } else {
                    alert(data.message || 'Error al publicar el comentario.');
                }
            } catch (error) {
                console.error('Error al enviar comentario de short:', error);
                alert('Error de conexión al enviar el comentario.');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;
            }
        });
    }

    // Función para formatear un comentario de short (adaptar de post_interactions o crear nueva)
    function formatShortCommentForModal(comment, author) {
        const authorPic = author.usr_foto_perfil_base64_datauri || `${basePath}Views/pictures/defaultpfp.jpg`;
        const authorFullName = author.usr_nombre_completo || author.usr_username || 'Usuario';
        const authorUsername = author.usr_username || 'anónimo';
        const authorProfileLink = `${basePath}profile/${author.usr_id}`;
        const commentDate = new Date(comment.int_fecha).toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'});
        // Opciones de editar/eliminar si es el autor (necesitarías loggedInUserId)
        let commentOptions = '';
         if (loggedInUserId && loggedInUserId == author.usr_id) { // Asegúrate que loggedInUserId esté disponible
            // Lógica para botones de editar/eliminar comentario
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
    
    // Helper JS para formatear números (similar al de PHP)
    function numberFormatShortJS(n, precision = 0) { // Precision 0 para enteros
        if (n === undefined || n === null) return '0';
        if (n < 900) {
            return n.toString();
        } else if (n < 900000) {
            return (n / 1000).toFixed(n % 1000 !== 0 ? precision : 0).replace(/\.0$/, '') + 'K';
        } else if (n < 900000000) {
            return (n / 1000000).toFixed(n % 1000000 !== 0 ? precision : 0).replace(/\.0$/, '') + 'M';
        } else { // Añadir más sufijos (B, T) si es necesario
            return (n / 1000000000).toFixed(n % 1000000000 !== 0 ? precision : 0).replace(/\.0$/, '') + 'B';
        }
    }


    // Limpiar modal de comentarios de shorts al cerrar
    const shortCommentsModalElem = document.getElementById('shortCommentsModal');
    if (shortCommentsModalElem) {
        shortCommentsModalElem.addEventListener('hidden.bs.modal', () => {
            document.getElementById('shortCommentsModalExistingComments').innerHTML = '<p class="text-muted text-center">Cargando comentarios...</p>';
            document.getElementById('shortCommentsModalShortInfo').innerHTML = ''; // Limpiar info del short
            document.getElementById('shortCommentsModalShortInfo').classList.add('d-none');
            if (newShortCommentForm) newShortCommentForm.reset();
            const shortIdInput = document.getElementById('shortIdCommentModalInput');
            if(shortIdInput) shortIdInput.value = '';
        });
    }
});