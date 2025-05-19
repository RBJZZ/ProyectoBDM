// ./js/shorts.js
document.addEventListener('DOMContentLoaded', () => {
    const reelsWrapper = document.getElementById('reelsWrapper');
    const reels = Array.from(reelsWrapper.querySelectorAll('.video-reel'));
    const prevButton = document.querySelector('.prev-btn');
    const nextButton = document.querySelector('.next-btn');
    let currentReelIndex = 0;

    function updateButtonStates() {
        if (!prevButton || !nextButton) return;
        prevButton.disabled = currentReelIndex === 0;
        nextButton.disabled = currentReelIndex === reels.length - 1;
    }

    function showReel(index) {
        if (index < 0 || index >= reels.length) {
            console.warn('Índice de reel fuera de rango:', index);
            return;
        }

        // Pausar todos los videos y quitar la clase 'active'
        reels.forEach((reel, i) => {
            const video = reel.querySelector('.reel-video');
            reel.classList.remove('active');
            if (video && !video.paused) {
                video.pause();
            }
        });

        // Activar el reel actual
        const currentReel = reels[index];
        currentReel.classList.add('active');
        reelsWrapper.style.transform = `translateY(-${index * 100}%)`; // Usar % para ser relativo a la altura del wrapper

        const activeVideo = currentReel.querySelector('.reel-video');
        if (activeVideo) {
            activeVideo.currentTime = 0; // Reiniciar el video
            activeVideo.play().catch(error => {
                console.warn("Error al intentar reproducir el video:", error);
                // A veces los navegadores bloquean el autoplay si no hay interacción previa del usuario en la página.
                // Considerar añadir un mensaje o un botón de play/unmute si esto ocurre.
                // Por ahora, los controles del video están visibles como fallback.
            });
        }
        currentReelIndex = index;
        updateButtonStates();
    }

    // Hacer 'navigate' global para que los botones HTML onclick puedan llamarla
    window.navigate = function(direction) {
        const newIndex = currentReelIndex + direction;
        if (newIndex >= 0 && newIndex < reels.length) {
            showReel(newIndex);
        }
    }

    // Inicialización
    if (reels.length > 0) {
        // Quitar el atributo 'controls' de los videos si quieres un look más limpio
        // y manejar play/pause con clics en el video o con tus propios botones.
        reels.forEach(reel => {
            const video = reel.querySelector('.reel-video');
            if (video) {
                 // video.removeAttribute('controls'); // Descomenta si quieres quitar controles nativos
                 video.addEventListener('click', function() {
                    if (this.paused) {
                        this.play();
                    } else {
                        this.pause();
                    }
                 });
            }
        });
        showReel(0); // Mostrar el primer reel al cargar
    } else {
        console.warn('No se encontraron reels.');
        if (prevButton) prevButton.disabled = true;
        if (nextButton) nextButton.disabled = true;
    }

    // Opcional: Navegación con teclas de flecha
    document.addEventListener('keydown', (event) => {
        if (document.activeElement && ['INPUT', 'TEXTAREA', 'BUTTON'].includes(document.activeElement.tagName)) {
            return; // No interferir si el usuario está escribiendo en un input o interactuando con un botón
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            navigate(-1);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            navigate(1);
        }
    });

    // Opcional: Cargar dinámicamente la barra de navegación (si main.js lo hace así)
    // Esta parte depende de cómo tengas estructurado tu main.js
    const navbarContainer = document.getElementById('navbar-container');
    if (navbarContainer) {
        // Asumiendo que tienes una función loadNavbar en main.js o similar
        // Ejemplo: fetch('./navbar.html').then(res => res.text()).then(data => navbarContainer.innerHTML = data);
        // O si main.js se encarga de ello globalmente, no necesitas hacer nada aquí.
    }

    // Implementación básica del cambio de tema (si tu main.js no lo maneja ya)
    const themeToggle = document.querySelector('.theme-toggle');
    const themeIcon = document.getElementById('theme-icon');

    if (themeToggle && themeIcon) { // Asegúrate que existan
        // Cargar el tema guardado
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.body.classList.toggle('dark-theme', currentTheme === 'dark');
        themeIcon.classList.toggle('bi-moon-fill', currentTheme === 'light');
        themeIcon.classList.toggle('bi-sun-fill', currentTheme === 'dark');


        window.toggleTheme = function() { // Hacerla global si el onclick está en el HTML
            document.body.classList.toggle('dark-theme');
            const isDarkMode = document.body.classList.contains('dark-theme');
            themeIcon.classList.toggle('bi-moon-fill', !isDarkMode);
            themeIcon.classList.toggle('bi-sun-fill', isDarkMode);
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
        }
    } else if(themeToggle && !themeIcon) {
        console.warn("Elemento '.theme-toggle' encontrado, pero 'theme-icon' no. La funcionalidad de cambio de tema podría no funcionar como se espera.");
    }




// Script para previsualizar video y manejar envío del form (puede ir en un JS aparte o aquí si es corto)

    const videoFileIn = document.getElementById('shortVideoFile');
    const videoPreviewEl = document.getElementById('videoPreview');
    const videoPreviewContainerEl = document.getElementById('videoPreviewContainer');
    const uploadShortForm = document.getElementById('uploadShortForm');
    const submitButton = document.getElementById('submitUploadShortButton');
    const messageDiv = document.getElementById('uploadShortMessage');

    if (videoFileIn) {
        videoFileIn.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (videoPreviewEl && videoPreviewContainerEl) {
                        videoPreviewEl.src = e.target.result;
                        videoPreviewContainerEl.style.display = 'block';
                    }
                }
                reader.readAsDataURL(file);
            } else {
                 if (videoPreviewContainerEl) videoPreviewContainerEl.style.display = 'none';
                 if (videoPreviewEl) videoPreviewEl.src = '';
            }
        });
    }

    if (uploadShortForm && submitButton) {
        uploadShortForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            messageDiv.innerHTML = ''; // Limpiar mensajes previos
            submitButton.disabled = true;
            submitButton.querySelector('.spinner-border').classList.remove('d-none');
            submitButton.lastChild.textContent = ' Publicando...';


            const formData = new FormData(uploadShortForm);
            // basePath debe estar definido globalmente (lo hicimos en shorts_page.php)
            const url = `${window.basePath || '/'}short/upload`;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Para identificar AJAX en el backend
                    }
                });

                const result = await response.json();

                if (result.success) {
                    messageDiv.innerHTML = `<div class="alert alert-success">${result.message || 'Short subido con éxito.'}</div>`;
                    uploadShortForm.reset();
                    if (videoPreviewContainerEl) videoPreviewContainerEl.style.display = 'none';
                    if (videoPreviewEl) videoPreviewEl.src = '';
                    // Opcional: cerrar el modal después de un tiempo o añadir el nuevo short al feed dinámicamente
                    setTimeout(() => {
                        const modalInstance = bootstrap.Modal.getInstance(document.getElementById('uploadShortModal'));
                        if (modalInstance) modalInstance.hide();
                        // Podrías recargar la página de shorts o añadir el nuevo short al DOM
                        // window.location.reload();
                    }, 2000);
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger">${result.message || 'Error al subir el short.'}</div>`;
                }

            } catch (error) {
                console.error('Error en uploadShortForm:', error);
                messageDiv.innerHTML = '<div class="alert alert-danger">Error de conexión o respuesta inesperada del servidor.</div>';
            } finally {
                submitButton.disabled = false;
                submitButton.querySelector('.spinner-border').classList.add('d-none');
                submitButton.lastChild.textContent = ' Publicar Short';
            }
        });
    }

    // Limpiar previsualización y mensajes cuando el modal se oculte
    const uploadModalElement = document.getElementById('uploadShortModal');
    if (uploadModalElement) {
        uploadModalElement.addEventListener('hidden.bs.modal', function () {
            if (uploadShortForm) uploadShortForm.reset();
            if (videoPreviewContainerEl) videoPreviewContainerEl.style.display = 'none';
            if (videoPreviewEl) videoPreviewEl.src = '';
            if (messageDiv) messageDiv.innerHTML = '';
        });
    }


    let editShortModalInstance = null;
    if (document.getElementById('editShortModal')) {
        editShortModalInstance = new bootstrap.Modal(document.getElementById('editShortModal'));
    }

    // --- MANEJO DE CLIC EN BOTÓN "EDITAR SHORT" ---
    document.body.addEventListener('click', async function(event) {
        const editButton = event.target.closest('.edit-short-btn');
        if (editButton && editShortModalInstance) {
            event.preventDefault();
            const shortId = editButton.dataset.shortId;

            // Cargar datos del short en el modal de edición
            try {
                const response = await fetch(`${basePath}api/short/data_for_edit?short_id=${shortId}`);
                const result = await response.json();

                if (result.success && result.data) {
                    document.getElementById('editShortId').value = result.data.sht_id_short;
                    document.getElementById('editShortTitle').value = result.data.sht_titulo;
                    document.getElementById('editShortDescription').value = result.data.sht_descripcion || '';
                    document.getElementById('editShortTags').value = result.data.tags_string || '';
                    
                    // Previsualizar video (opcional, ya que no lo estamos cambiando)
                    const videoPreview = document.getElementById('editVideoPreview');
                    const videoPreviewContainer = document.getElementById('editVideoPreviewContainer');
                    if (videoPreview && result.data.video_url_for_preview) { // video_url_for_preview fue añadido en el modelo
                        videoPreview.innerHTML = `<source src="${result.data.video_url_for_preview}" type="${result.data.sht_video_mime || 'video/mp4'}">Tu navegador no soporta video.`;
                        videoPreview.load(); // Para refrescar el source
                        videoPreviewContainer.style.display = 'block';
                    } else if (videoPreviewContainer) {
                        videoPreviewContainer.style.display = 'none';
                    }
                    
                    editShortModalInstance.show();
                } else {
                    alert(result.message || 'Error al cargar datos del short para edición.');
                }
            } catch (error) {
                console.error('Error al cargar datos para editar short:', error);
                alert('Error de conexión al cargar datos para editar.');
            }
        }
    });

    // --- MANEJO DE ENVÍO DEL FORMULARIO DE EDICIÓN DE SHORT ---
    const editShortForm = document.getElementById('editShortForm');
    if (editShortForm) {
        editShortForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const submitButton = document.getElementById('submitEditShortButton');
            const messageDiv = document.getElementById('editShortMessage');
            messageDiv.innerHTML = '';
            submitButton.disabled = true;
            submitButton.querySelector('.spinner-border').classList.remove('d-none');
            // Cambiar texto del botón...

            const formData = new FormData(editShortForm);
            
            try {
                const response = await fetch(`${basePath}short/update`, {
                    method: 'POST',
                    body: formData,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                const result = await response.json();

                if (result.success) {
                    messageDiv.innerHTML = `<div class="alert alert-success">${result.message || 'Short actualizado.'}</div>`;
                    setTimeout(() => {
                        if (editShortModalInstance) editShortModalInstance.hide();
                        window.location.reload(); // Recargar para ver cambios (o actualizar DOM dinámicamente)
                    }, 1500);
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger">${result.message || 'Error al actualizar.'}</div>`;
                }
            } catch (error) {
                console.error('Error al actualizar short:', error);
                messageDiv.innerHTML = '<div class="alert alert-danger">Error de conexión.</div>';
            } finally {
                submitButton.disabled = false;
                submitButton.querySelector('.spinner-border').classList.add('d-none');
                // Restaurar texto del botón...
            }
        });
    }

    // --- MANEJO DE CLIC EN BOTÓN "ELIMINAR SHORT" ---
    document.body.addEventListener('click', async function(event) {
        const deleteButton = event.target.closest('.delete-short-btn');
        if (deleteButton) {
            event.preventDefault();
            const shortId = deleteButton.dataset.shortId;

            if (confirm('¿Estás seguro de que quieres eliminar este short? Esta acción no se puede deshacer.')) {
                try {
                    const response = await fetch(`${basePath}short/delete`, {
                        method: 'POST', // O 'DELETE' si tu servidor y JS están configurados para ello
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ short_id: shortId })
                    });
                    const result = await response.json();

                    if (result.success) {
                        alert(result.message || 'Short eliminado.');
                        // Eliminar el reel del DOM o recargar la página
                        const reelToRemove = document.querySelector(`.video-reel[data-short-id="${shortId}"]`);
                        if (reelToRemove) {
                            reelToRemove.remove();
                            // Aquí deberías recalcular los reels en tu shorts.js si es necesario o recargar.
                            // Por simplicidad, una recarga es más fácil:
                            window.location.reload();
                        } else {
                            window.location.reload(); // Si no se encuentra, recargar de todas formas
                        }
                    } else {
                        alert(result.message || 'Error al eliminar el short.');
                    }
                } catch (error) {
                    console.error('Error al eliminar short:', error);
                    alert('Error de conexión al intentar eliminar.');
                }
            }
        }
    });
    
    // Limpiar modal de edición al cerrar
    const editModalElement = document.getElementById('editShortModal');
    if (editModalElement) {
        editModalElement.addEventListener('hidden.bs.modal', function () {
            if (editShortForm) editShortForm.reset();
            document.getElementById('editShortMessage').innerHTML = '';
            const videoPreviewContainer = document.getElementById('editVideoPreviewContainer');
             if (videoPreviewContainer) videoPreviewContainer.style.display = 'none';
             document.getElementById('editVideoPreview').innerHTML = '';
        });
    }

    if (reels.length > 0) {
        showReel(0); // Mostrar el primer reel al cargar
        handleAnchorLink(); // Intentar procesar anclaje después de la carga inicial
    }

    function handleAnchorLink() {
        if (window.location.hash) {
            const hash = window.location.hash; // ej: #short-3
            if (hash.startsWith('#short-')) {
                const targetShortId = hash.substring('#short-'.length);
                const targetReelElement = reelsWrapper.querySelector(`.video-reel[data-short-id="${targetShortId}"]`);
                if (targetReelElement) {
                    const targetIndex = reels.indexOf(targetReelElement);
                    if (targetIndex > -1) {
                        console.log(`Anclaje detectado, mostrando reel en índice: ${targetIndex}`);
                        showReel(targetIndex);
                    } else {
                        console.warn(`Anclaje a short ID ${targetShortId} encontrado, pero el reel no está en el array 'reels'.`);
                    }
                } else {
                     console.warn(`Anclaje a short ID ${targetShortId} no encontrado en el DOM.`);
                }
            }
        }
    }


});
