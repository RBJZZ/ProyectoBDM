// Views/js/follow_handler.js
document.addEventListener('DOMContentLoaded', function () {
    const currentUserId = window.currentUserData?.userId; // Asume que window.currentUserData.userId está disponible

    document.body.addEventListener('click', function(event) {
        if (event.target.matches('.follow-button')) {
            handleFollowButtonClick(event.target);
        } else if (event.target.closest('.follow-button')) { // Si el clic fue en el icono o texto dentro del botón
            handleFollowButtonClick(event.target.closest('.follow-button'));
        }
    });

    function handleFollowButtonClick(button) {
        if (!currentUserId) {
            console.warn("Usuario no logueado. Acción de seguir cancelada.");
            // Opcional: redirigir a login o mostrar mensaje
            // window.location.href = window.basePath + 'login';
            alert("Debes iniciar sesión para seguir a otros usuarios.");
            return;
        }

        const targetUserId = button.dataset.userIdTarget;
        let action = button.dataset.action; // 'follow' o 'unfollow'

        if (!targetUserId || !action) {
            console.error("Faltan atributos data-user-id-target o data-action en el botón.");
            return;
        }

        const apiUrl = `${window.basePath}user/${targetUserId}/${action}`;
        const originalButtonText = button.querySelector('.button-text')?.textContent || (action === 'follow' ? 'Seguir' : 'Siguiendo');
        const iconElement = button.querySelector('i');
        const originalIconClasses = iconElement ? iconElement.className : '';
        
        // Deshabilitar botón y mostrar carga (opcional)
        button.disabled = true;
        if (button.querySelector('.button-text')) {
            button.querySelector('.button-text').textContent = 'Procesando...';
        } else {
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
        }


        fetch(apiUrl, {
            method: 'POST', // Asumiendo que tus endpoints de follow/unfollow son POST
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' // Para identificar peticiones AJAX en el backend si es necesario
            },
            // body: JSON.stringify({ actor_id: currentUserId }) // El actorId se toma de la sesión en el backend
        })
        .then(response => {
            if (!response.ok) {
                // Intenta leer el mensaje de error del JSON si está disponible
                return response.json().then(errData => {
                    throw new Error(errData.message || `Error de red o servidor: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            button.disabled = false; // Rehabilitar botón
            if (data.success) {
                // Cambiar estado del botón
                if (action === 'follow') {
                    button.dataset.action = 'unfollow';
                    if (button.querySelector('.button-text')) button.querySelector('.button-text').textContent = 'Siguiendo';
                    if (iconElement) iconElement.className = 'bi bi-person-check-fill';
                    button.classList.remove('btn-custom');
                    button.classList.add('btn-outline-custom', 'follow-active');
                } else { // action === 'unfollow'
                    button.dataset.action = 'follow';
                     if (button.querySelector('.button-text')) button.querySelector('.button-text').textContent = 'Seguir';
                    if (iconElement) iconElement.className = 'bi bi-person-plus-fill';
                    button.classList.remove('btn-outline-custom', 'follow-active');
                    button.classList.add('btn-custom');
                }
                console.log(data.message);
                // Aquí podrías actualizar contadores de seguidores/seguidos si se muestran en la página
                // y si el backend los devuelve en la respuesta JSON (ej. data.new_follower_count_for_target)
            } else {
                alert(data.message || 'Ocurrió un error.');
                 if (button.querySelector('.button-text')) button.querySelector('.button-text').textContent = originalButtonText;
                if (iconElement) iconElement.className = originalIconClasses;
            }
        })
        .catch(error => {
            button.disabled = false; // Rehabilitar botón
            if (button.querySelector('.button-text')) button.querySelector('.button-text').textContent = originalButtonText;
            if (iconElement) iconElement.className = originalIconClasses;
            console.error('Error en la acción de seguir:', error);
            alert('Error: ' + error.message);
        });
    }
});