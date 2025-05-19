document.addEventListener('DOMContentLoaded', function() {
    const basePath = window.basePath || '/ProyectoBDM/'; // Asegúrate que basePath esté definido


        // En tu archivo JS:
    document.querySelectorAll('.edit-product-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Previene la acción por defecto del enlace '#'
            const productId = this.dataset.productId;
            if (!productId) {
                alert('No se pudo obtener el ID del producto para editar.');
                return;
            }
            // Llama a la función que carga datos y muestra el modal
            loadProductDataIntoModal(productId); 
        });
    });

    document.querySelectorAll('.favorite-toggle-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevenir cualquier acción por defecto del botón si es un enlace
            
            if (!window.currentUserData || !window.currentUserData.userId) {
                alert('Debes iniciar sesión para añadir a favoritos.');
                // Opcional: redirigir a login
                // window.location.href = basePath + 'login';
                return;
            }

            const productId = this.dataset.productId;
            const icon = this.querySelector('i');
            // const countSpan = this.querySelector('.favorite-count'); // Si tienes contador

            // Deshabilitar botón temporalmente para evitar clics múltiples
            this.disabled = true;

            fetch(basePath + 'product/toggle_favorite', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // Importante para algunos frameworks o verificaciones
                },
                body: JSON.stringify({ productId: parseInt(productId) }) // Enviar como JSON
            })
            .then(response => {
                if (!response.ok) { // Si la respuesta HTTP no es 2xx
                    return response.json().then(errData => {
                        throw new Error(errData.message || `Error del servidor: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.dataset.isFavorite = data.isFavorite ? 'true' : 'false';
                    this.title = data.isFavorite ? "Quitar de favoritos" : "Añadir a favoritos";
                    
                    if (icon) {
                        if (data.isFavorite) {
                            icon.classList.remove('bi-heart');
                            icon.classList.add('bi-heart-fill', 'text-danger');
                        } else {
                            icon.classList.remove('bi-heart-fill', 'text-danger');
                            icon.classList.add('bi-heart');
                        }
                    }
                    
                    // (Opcional) Actualizar contador de favoritos si lo muestras
                    // if (countSpan && data.favoriteCount !== undefined) {
                    //     countSpan.textContent = data.favoriteCount;
                    // }

                    // (Opcional) Mostrar un mensaje de feedback (toast, etc.)
                    // showToast(data.message); 

                } else {
                    alert(data.message || 'Hubo un error al procesar tu solicitud.');
                }
            })
            .catch(error => {
                console.error('Error en toggleFavorite AJAX:', error);
                alert('Error de conexión al procesar la acción de favorito: ' + error.message);
            })
            .finally(() => {
                this.disabled = false; // Habilitar el botón nuevamente
            });
        });
    });

    /////////// MANEJO DE EDICION DE PRODUCTO - ETC
    const editProductModalElement = document.getElementById('editProductModal');
    let editProductModalInstance = null;
    if (editProductModalElement) {
        editProductModalInstance = new bootstrap.Modal(editProductModalElement);
    }

    // Contenedores del formulario del modal
    const form = document.getElementById('editProductModalForm');
    const productIdInputForAction = document.getElementById('edit_product_id'); // Para construir el action del form
    const productNameInput = document.getElementById('edit_product_name');
    const productDescInput = document.getElementById('edit_product_description');
    const productPriceInput = document.getElementById('edit_product_price');
    const productTagSelect = document.getElementById('edit_product_tag');
    const currentMediaContainer = document.getElementById('edit_currentMediaPreviewContainer');
    const mediaToDeleteContainer = document.getElementById('edit_mediaToDeleteContainer');
    const newMediaInput = document.getElementById('edit_product_media_new');
    const newMediaPreviewContainer = document.getElementById('edit_newMediaPreviewContainer');
    const errorMessagesDiv = document.getElementById('editProductErrorMessages');

    // 1. Event Listener para el botón "Editar" en la página product.php
    // Este botón ya existe en tu product.php con la clase .edit-product-btn
    document.querySelectorAll('.edit-product-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const productId = this.dataset.productId;
            if (!productId) {
                alert('No se pudo obtener el ID del producto.');
                return;
            }
            loadProductDataIntoModal(productId);
        });
    });


    const deleteProductButton = document.querySelector('.delete-product-btn'); 
    if (deleteProductButton) {
        deleteProductButton.addEventListener('click', function(event) {
            event.preventDefault();
            const productId = this.dataset.productId;

            if (!productId) {
                alert('No se pudo obtener el ID del producto para eliminar.');
                return;
            }

            // Usar SweetAlert2 para una mejor confirmación
            if (typeof Swal === 'undefined') { // Fallback a confirm simple si Swal no está
                if (!confirm('¿Estás seguro de que quieres eliminar esta publicación? Esta acción no se puede deshacer.')) {
                    return;
                }
            } else {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡No podrás revertir esto!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, ¡eliminarlo!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        proceedWithDeletion(productId);
                    }
                });
                return; // Detener aquí, proceedWithDeletion se llama desde el then de Swal
            }
            // Si no se usó Swal, continuar aquí
            proceedWithDeletion(productId);
        });
    }

    async function proceedWithDeletion(productId) {
        const deleteButton = document.querySelector(`.delete-product-btn[data-product-id="${productId}"]`);
        if(deleteButton) deleteButton.disabled = true; // Deshabilitar botón

        try {
            const response = await fetch(`${window.basePath || '/ProyectoBDM/'}product/delete/${productId}`, {
                method: 'POST', // O 'DELETE', asegúrate que el backend lo espere
                headers: {
                    'Content-Type': 'application/json', // Aunque no envíes cuerpo, es buena práctica
                    'X-Requested-With': 'XMLHttpRequest'
                }
                // No necesitas body para una solicitud de eliminación simple si el ID está en la URL
            });

            const data = await response.json();

            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire(
                        '¡Eliminado!',
                        data.message || 'Tu publicación ha sido eliminada.',
                        'success'
                    );
                } else {
                    alert(data.message || 'Publicación eliminada.');
                }
                // Redirigir al marketplace o al perfil del usuario
                window.location.href = `${window.basePath || '/ProyectoBDM/'}marketplace`;
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire(
                        'Error',
                        data.message || 'No se pudo eliminar la publicación.',
                        'error'
                    );
                } else {
                    alert(data.message || 'No se pudo eliminar la publicación.');
                }
                if(deleteButton) deleteButton.disabled = false; // Rehabilitar si falló
            }
        } catch (error) {
            console.error('Error al eliminar producto:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Error de conexión al intentar eliminar.', 'error');
            } else {
                alert('Error de conexión al intentar eliminar.');
            }
            if(deleteButton) deleteButton.disabled = false; // Rehabilitar en error
        }
    }

    // 2. Función para cargar datos del producto en el modal
    function loadProductDataIntoModal(productId) { // Ya no necesita ser async
        console.log("loadProductDataIntoModal: Intentando cargar datos para productId:", productId);
        if (!editProductModalInstance || !form) {
            console.error("Modal o formulario no encontrados.");
            return;
        }
        
        // Resetear formulario y previsualizaciones
        form.reset();
        currentMediaContainer.innerHTML = ''; // Limpiar específicamente
        mediaToDeleteContainer.innerHTML = ''; 
        newMediaPreviewContainer.innerHTML = '';
        if(newMediaInput) newMediaInput.value = ''; // Limpiar input de archivos si existe
        if(errorMessagesDiv) {
            errorMessagesDiv.classList.add('d-none');
            errorMessagesDiv.textContent = '';
        }
        if(productIdInputForAction) productIdInputForAction.value = productId; 
        if(form) form.setAttribute('action', `${basePath}product/update/${productId}`);

        // Usar los datos ya cargados desde window.initialProductDataForModal
        if (window.initialProductDataForModal && 
            window.initialProductDataForModal.product && 
            parseInt(window.initialProductDataForModal.product.prd_id_producto) === parseInt(productId)) {
            
            console.log("Datos iniciales encontrados para el producto:", window.initialProductDataForModal);
            const product = window.initialProductDataForModal.product;
            const categories = window.initialProductDataForModal.categories; // Estas son TODAS las categorías del mercado

            if(productNameInput) productNameInput.value = product.prd_nombre_producto || '';
            
            // Usar la descripción cruda para el textarea
            if(productDescInput) productDescInput.value = product.prd_descripcion_raw || product.prd_descripcion || '';
            
            if(productPriceInput) productPriceInput.value = product.prd_precio || '';

            // Poblar select de categorías
            if (productTagSelect) {
                productTagSelect.innerHTML = '<option value="">Selecciona una categoría...</option>';
                if (categories && categories.length > 0) {
                    categories.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.tag_id;
                        option.textContent = cat.tag_nombre;
                        // product.prd_id_tag debe estar disponible en product (viene de $productDetails)
                        if (product.prd_id_tag && cat.tag_id == product.prd_id_tag) { 
                            option.selected = true;
                        }
                        productTagSelect.appendChild(option);
                    });
                } else {
                    productTagSelect.innerHTML = '<option value="">No hay categorías disponibles</option>';
                }
            }
            
            // Poblar media actual
            if (currentMediaContainer) {
                currentMediaContainer.innerHTML = ''; // Asegurarse que esté vacío
                if (product.media && product.media.length > 0) {
                    product.media.forEach(item => {
                        const mediaDiv = document.createElement('div');
                        mediaDiv.className = 'media-preview-item position-relative'; // Añadido position-relative
                        mediaDiv.dataset.mediaId = item.prdmed_id;

                        const mediaUrl = `${basePath}get_product_media.php?id=${item.prdmed_id}`;
                        // El botón de eliminar ahora es más estilizado y posicionado
                        const removeBtnHTML = `<button type="button" class="btn btn-danger btn-sm remove-media-btn position-absolute top-0 end-0 m-1 p-0 lh-1" title="Marcar para eliminar" style="width: 20px; height: 20px; font-size: 0.8rem;">&times;</button>`;

                        if (item.prdmed_tipo === 'Imagen') {
                            mediaDiv.innerHTML = `<img src="${mediaUrl}" alt="Media producto ${item.prdmed_id}" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">${removeBtnHTML}`;
                        } else if (item.prdmed_tipo === 'Video') {
                            // Para videos, la miniatura es más compleja si quieres un frame. Por ahora, un placeholder o icono.
                            mediaDiv.innerHTML = `<div class="bg-dark d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;"><i class="bi bi-film text-white fs-3"></i></div><span class="d-block small text-truncate w-100" style="max-width: 80px;">Video ID: ${item.prdmed_id}</span>${removeBtnHTML}`;
                            // Considera mostrar un thumbnail del video si lo generas o si el navegador puede hacerlo.
                        }
                        currentMediaContainer.appendChild(mediaDiv);
                    });
                } else {
                    currentMediaContainer.innerHTML = '<p class="text-muted small">Este producto no tiene imágenes o videos.</p>';
                }
            }

            if(editProductModalInstance) editProductModalInstance.show();
        } else {
            alert('Error: No se pudieron encontrar los datos del producto precargados para el modal.');
            console.error('window.initialProductDataForModal no está disponible, no contiene el producto, o el ID no coincide. ProductId:', productId, 'Datos precargados:', window.initialProductDataForModal);
        }
    }

    // 3. Event listener para marcar media existente para eliminar (dentro del modal)
    // Usar delegación de eventos porque los items de media se cargan dinámicamente
    if (currentMediaContainer) {
        currentMediaContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-media-btn')) {
                const mediaItemDiv = event.target.closest('.media-preview-item');
                const mediaId = mediaItemDiv.dataset.mediaId;

                // Añadir input hidden para enviar este ID
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'media_to_delete[]'; // Array para el backend
                hiddenInput.value = mediaId;
                mediaToDeleteContainer.appendChild(hiddenInput);

                mediaItemDiv.style.opacity = '0.5'; // Indicar visualmente
                event.target.remove(); // Remover el botón de eliminar para no marcarlo de nuevo
                // O podrías deshabilitarlo: event.target.disabled = true;
            }
        });
    }

    // 4. Previsualización de nueva media (similar al de la página de edición)
    if (newMediaInput) {
        newMediaInput.addEventListener('change', function(event) {
            newMediaPreviewContainer.innerHTML = ''; // Limpiar previas
            const files = event.target.files;
            // Lógica de previsualización (copiar de edit_product.php JS)
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                const div = document.createElement('div');
                div.style.display = 'inline-block';
                div.style.margin = '5px';
                div.style.border = '1px solid #eee';
                div.style.padding = '5px';
                div.style.position = 'relative';

                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.maxWidth = '80px'; img.style.maxHeight = '80px'; img.style.display = 'block';
                        div.appendChild(img);
                    } else if (file.type.startsWith('video/')) {
                        const video = document.createElement('video');
                        video.src = e.target.result;
                        video.controls = true; video.style.maxWidth = '80px'; video.style.maxHeight = '80px';
                        div.appendChild(video);
                    }
                    newMediaPreviewContainer.appendChild(div);
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // 5. Manejo del envío del formulario del modal (AJAX)
    if (form) {
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const actionUrl = this.getAttribute('action'); // Ya debería estar actualizado
            
            const submitButton = editProductModalElement.querySelector('.modal-footer button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...`;
            errorMessagesDiv.classList.add('d-none');
            errorMessagesDiv.textContent = '';

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.success) {
                    editProductModalInstance.hide();
                    alert(data.message || 'Producto actualizado con éxito.');
                    // Forzar recarga de la página para ver los cambios, o actualizar dinámicamente la UI
                    window.location.reload(); 
                } else {
                    let errorText = data.message || 'Error al actualizar el producto.';
                    if (data.errors && Array.isArray(data.errors)) {
                        errorText += '\n\nDetalles:\n- ' + data.errors.join('\n- ');
                    }
                    errorMessagesDiv.textContent = errorText.replace(/\n/g, '<br>');
                    errorMessagesDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Error en fetch al actualizar producto desde modal:', error);
                errorMessagesDiv.textContent = 'Error de conexión o del servidor.';
                errorMessagesDiv.classList.remove('d-none');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }


});