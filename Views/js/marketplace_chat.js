// En un archivo JS cargado en la página de Marketplace
document.addEventListener('DOMContentLoaded', function() {
    const baseUri = document.documentElement.getAttribute('data-base-uri') || '/ProyectoBDM/';

    document.querySelectorAll('.contact-seller-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const sellerId = this.dataset.sellerId;
            // const productName = this.dataset.productName; // Ya no lo pasaremos al backend directamente
            const currentUserId = window.currentUserData?.userId;

            if (!currentUserId) {
                alert("Debes iniciar sesión para contactar al vendedor.");
                return;
            }

            if (parseInt(sellerId) === parseInt(currentUserId)) {
                alert("No puedes iniciar un chat contigo mismo por tu propio producto.");
                return;
            }

            console.log(`Marketplace: Intentando iniciar/obtener chat con vendedor ID: ${sellerId}`);
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Abriendo chat...';

            try {
                const response = await fetch(`${baseUri}chat/individual/create_or_get`, { // <--- USA TU RUTA EXISTENTE
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        target_user_id: parseInt(sellerId) // El backend espera 'target_user_id'
                    })
                });

                const result = await response.json();
                this.disabled = false;
                this.textContent = 'Contactar al Vendedor'; // Restaurar botón

                if (result.success && result.chat_id) {
                    console.log(`Chat ${result.is_new ? 'nuevo' : 'existente'} obtenido/creado. ID: ${result.chat_id}`);
                    // Redirigir a la página de chat, activando el chat específico
                    window.location.href = `${baseUri}chat?activate_chat_id=${result.chat_id}`;
                    // Opcional: si es un chat nuevo y quieres enviar un mensaje inicial sobre el producto:
                    // if (result.is_new && productName) {
                    //     // Podrías almacenar el productName en localStorage/sessionStorage
                    //     // y que la página de chat lo lea y envíe un mensaje inicial.
                    //     // O hacer otra petición AJAX para enviar ese primer mensaje al chat_id obtenido.
                    //     // Por ahora, solo redirigimos.
                    // }
                } else {
                    alert(`Error al iniciar el chat: ${result.message || 'Error desconocido del servidor.'}`);
                }

            } catch (error) {
                console.error('Error en fetch para iniciar chat desde marketplace:', error);
                alert('Error de conexión al intentar iniciar el chat.');
                this.disabled = false;
                this.textContent = 'Contactar al Vendedor';
            }
        });
    });
});