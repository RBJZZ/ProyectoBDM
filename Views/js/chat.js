// js/chat.js
document.addEventListener('DOMContentLoaded', function () {
    // --- CONSTANTES Y VARIABLES GLOBALES DEL CHAT ---
    const baseUri = document.documentElement.getAttribute('data-base-uri') || '/ProyectoBDM/';
    const currentUserId = window.currentUserData?.userId || null;
    const defaultProfilePic = `${baseUri}Views/pictures/defaultpfp.jpg`;

    let activeChatId = null;
    let pollingInterval = null;
    let lastDisplayedMessageId = 0;
    let isLoadingMessages = false;
    let userHasScrolledUp = false; // Para el control inteligente del scroll

    // --- SELECTORES DE ELEMENTOS DEL DOM ---
    const chatListContainer = document.getElementById('chat-list-container');
    const chatMessagesContainer = document.getElementById('chat-messages-container');
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-text-input');
    // sendMessageButton no es estrictamente necesario si el form lo maneja, pero lo dejaremos por si acaso.
    // const sendMessageButton = document.getElementById('send-message-button');

    const chatHeaderImageEl = document.getElementById('chat-header-image');
    const chatHeaderNameEl = document.getElementById('chat-header-name');
    const chatHeaderStatusEl = document.getElementById('chat-header-status'); // Asegúrate que este ID exista en tu HTML

    const attachImageButton = document.getElementById('attach-image-btn');
    const attachFilesButton = document.getElementById('attach-file-btn');
    const fileInput = document.getElementById('chat-file-input');

    const activeChatAreaContainer = document.getElementById('active-chat-area');
    const noActiveChatPlaceholderContainer = document.getElementById('no-active-chat-placeholder');
    const chatInfoSidebarContainer = document.getElementById('chat-info-sidebar');

    const chatInfoImageEl = document.getElementById('chat-info-image');
    const chatInfoNameEl = document.getElementById('chat-info-name');
    const chatInfoDetailsEl = document.getElementById('chat-info-details');
    const chatInfoExtraLine1El = document.getElementById('chat-info-extra-line1');
    const chatInfoExtraLine2El = document.getElementById('chat-info-extra-line2');
    const sharedFilesContainerEl = document.getElementById('chat-shared-files-container');

    const createGroupModal = document.getElementById('createGroupModal');
    const userSearchInputForGroupModal = document.getElementById('userSearchInputForGroupModal');
    const userSearchResultsForGroupModal = document.getElementById('userSearchResultsForGroupModal');
    const selectedGroupMembersDisplayModal = document.getElementById('selectedGroupMembersDisplayModal');
    const createGroupForm = document.getElementById('createGroupForm'); 

    let selectedParticipantsData = [];
    let searchTimeoutId = null;

    const userSearchInputForGroup = document.getElementById('userSearchInputForGroup'); 
    const userSearchResultsForGroup = document.getElementById('userSearchResultsForGroup'); 
    const selectedUsersDisplay = document.getElementById('groupMembersSelectorPlaceholder'); 

    const adminControlsContainer = document.getElementById('chat-info-admin-controls');


    // --- INICIALIZACIÓN ---
    if (!currentUserId) {
        console.warn("Chat.js: No se encontró el ID del usuario actual.");
    }
    // La función showActiveChatUI (definida en el HTML) se llama al final del HTML con 'false'.
    loadUserChats();

    // --- MANEJADORES DE EVENTOS ---
    if (messageForm) {
        messageForm.addEventListener('submit', function(event) {
            event.preventDefault();
            handleSendMessage();
        });
    }

    if (chatListContainer) {
        chatListContainer.addEventListener('click', function (event) {
            const chatItem = event.target.closest('.list-group-item[data-chat-id]');
            if (chatItem) {
                event.preventDefault();
                const chatId = parseInt(chatItem.dataset.chatId);
                // Evitar reactivar el mismo chat si ya está visible, a menos que se fuerce.
                // if (activeChatId === chatId && document.getElementById('active-chat-area').style.display === 'flex') return;
                
                const chatName = chatItem.querySelector('h6')?.textContent || 'Chat';
                const chatImage = chatItem.querySelector('img')?.src || defaultProfilePic;
                activateChat(chatId, chatName, chatImage);
            }
        });
    }

    if (attachImageButton && fileInput) {
        attachImageButton.addEventListener('click', () => {
            fileInput.accept = "image/*";
            fileInput.click();
        });
    }
    if (attachFilesButton && fileInput) {
        attachFilesButton.addEventListener('click', () => {
            fileInput.removeAttribute('accept');
            fileInput.click();
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                const file = event.target.files[0];
                handleSendMessage(null, file);
                event.target.value = ''; // Correcto para resetear el input de archivo
            }
        });
    }

    // Detectar si el usuario hace scroll hacia arriba en el contenedor de mensajes
    if (chatMessagesContainer) {
        chatMessagesContainer.addEventListener('scroll', function() {
            // Si el final del contenido está más de ~100px por encima del borde inferior visible del contenedor
            if (this.scrollHeight - this.scrollTop > this.clientHeight + 100) {
                userHasScrolledUp = true;
            } else {
                userHasScrolledUp = false;
            }
        });
    }

    if (userSearchInputForGroupModal) {
        userSearchInputForGroupModal.addEventListener('input', function() {
            clearTimeout(searchTimeoutId); // Cancelar timeout anterior
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) { // No buscar si es muy corto
                if (userSearchResultsForGroupModal) userSearchResultsForGroupModal.innerHTML = '';
                return;
            }

            searchTimeoutId = setTimeout(async () => { // Esperar un poco antes de buscar
                if (userSearchResultsForGroupModal) userSearchResultsForGroupModal.innerHTML = '<p class="text-muted small p-2">Buscando...</p>';
                
                const excludeIdsArray = selectedParticipantsData.map(p => p.id);
            const excludeIdsString = excludeIdsArray.join(',');
                try {
                    const response = await fetch(`${baseUri}users/search_for_group?term=${encodeURIComponent(searchTerm)}&exclude_ids=${encodeURIComponent(excludeIdsString)}`);
                    if (!response.ok) {
                        throw new Error(`Error del servidor: ${response.status}`);
                    }
                    const result = await response.json();

                    if (result.success && userSearchResultsForGroupModal) {
                        userSearchResultsForGroupModal.innerHTML = ''; // Limpiar resultados anteriores
                        if (result.users && result.users.length > 0) {
                            result.users.forEach(user => {
                                const userDiv = document.createElement('div');
                                userDiv.classList.add('list-group-item', 'list-group-item-action', 'd-flex', 'align-items-center', 'py-2', 'px-2', 'search-result-item');
                                userDiv.style.cursor = 'pointer';
                                userDiv.dataset.userId = user.user_id;
                                userDiv.dataset.username = user.username;
                                userDiv.dataset.fullName = user.full_name; // Guardar nombre completo
                                userDiv.dataset.profilePicUrl = user.profile_pic_url;

                                userDiv.innerHTML = `
                                    <img src="${user.profile_pic_url || defaultProfilePic}" class="rounded-circle me-2" width="30" height="30" alt="${user.username}" style="object-fit:cover;">
                                    <div class="flex-grow-1">
                                        <small class="fw-bold d-block text-truncate">${user.full_name || user.username}</small>
                                        <small class="text-muted d-block text-truncate">@${user.username}</small>
                                    </div>
                                `;
                                userDiv.addEventListener('click', function() {
                                    addParticipantToGroup(
                                        user.user_id, 
                                        user.full_name || user.username, // Usa el nombre completo si está disponible
                                        user.profile_pic_url          // La URL de la foto de perfil
                                    );
                                    userSearchInputForGroupModal.value = ''; 
                                    if(userSearchResultsForGroupModal) userSearchResultsForGroupModal.innerHTML = ''; 
                                });
                                userSearchResultsForGroupModal.appendChild(userDiv);
                            });
                        } else {
                            userSearchResultsForGroupModal.innerHTML = '<p class="text-muted small p-2">No se encontraron usuarios.</p>';
                        }
                    } else {
                        if (userSearchResultsForGroupModal) userSearchResultsForGroupModal.innerHTML = `<p class="text-danger small p-2">${result.message || 'Error al buscar.'}</p>`;
                    }
                } catch (error) {
                    console.error("Error al buscar usuarios para grupo:", error);
                    if (userSearchResultsForGroupModal) userSearchResultsForGroupModal.innerHTML = '<p class="text-danger small p-2">Error de conexión al buscar.</p>';
                }
            }, 500); // Debounce de 500ms
        });
    }

    function addParticipantToGroup(userId, userName, userPicUrl) {
        userId = parseInt(userId);
        if (userId === currentUserId) { // currentUserId debe estar definido globalmente en este script
            // alert("Ya eres parte de este grupo como creador.");
            return;
        }
        // Verificar si el usuario ya está en selectedParticipantsData por su ID
        if (!selectedParticipantsData.find(p => p.id === userId)) {
            selectedParticipantsData.push({
                id: userId,
                name: userName, // El nombre que se mostró en los resultados de búsqueda
                picUrl: userPicUrl // La URL de la foto de perfil que se mostró
            });
            renderSelectedParticipants();
        } else {
            // alert("Este usuario ya está seleccionado.");
        }
    }

    function removeParticipantFromGroup(userIdToRemove) {
        userIdToRemove = parseInt(userIdToRemove);
        selectedParticipantsData = selectedParticipantsData.filter(participant => participant.id !== userIdToRemove);
        renderSelectedParticipants();
    }


    function renderSelectedParticipants() {

        if (!selectedGroupMembersDisplayModal) return;
        selectedGroupMembersDisplayModal.innerHTML = ''; // Limpiar

        if (selectedParticipantsData.length === 0) {
            selectedGroupMembersDisplayModal.innerHTML = '<small class="text-muted placeholder-selected-members">Ningún miembro añadido (aparte de ti).</small>';
            return;
        }

            selectedParticipantsData.forEach(participant => {
                const userBadge = document.createElement('span');
                userBadge.classList.add('badge', 'rounded-pill', 'bg-secondary', 'd-flex', 'align-items-center', 'p-1', 'pe-2', 'me-1', 'mb-1');
                // Estilo para la imagen dentro del badge
                const imgStyle = "width: 20px; height: 20px; border-radius: 50%; margin-right: 5px; object-fit: cover;";

                userBadge.innerHTML = `
                    <img src="${participant.picUrl || defaultProfilePic}" style="${imgStyle}" alt="${participant.name || 'Usuario'}">
                    <span class="participant-name small text-white">${participant.name || 'Usuario'}</span>
                    <button type="button" class="btn-close btn-close-white ms-1 remove-participant-btn" aria-label="Quitar"
                            style="font-size: 0.55em; padding: 0.15rem 0.3rem;" 
                            data-user-id-remove="${participant.id}"></button>
                `;
                    // Añadir listener al botón de quitar específico de este badge
                    const removeBtn = userBadge.querySelector('.btn-close');
                    if(removeBtn){
                        removeBtn.addEventListener('click', function(event) {
                            event.stopPropagation(); // Evitar que se disparen otros listeners
                            removeParticipantFromGroup(this.dataset.userId);
                        });
                    }
                    selectedGroupMembersDisplayModal.appendChild(userBadge);
            });

            updateHiddenParticipantIdsInput()
    }

    renderSelectedParticipants();



    if (createGroupForm) {
        createGroupForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const groupNameInput = document.getElementById('groupNameInputModal'); // Usar ID del modal
            const groupPhotoInput = document.getElementById('groupPhotoInputModal'); // Usar ID del modal

            if (!groupNameInput || !groupNameInput.value.trim()) {
                alert("El nombre del grupo es requerido.");
                groupNameInput.focus();
                return;
            }
            
            const participantIdsArray = selectedParticipantsData.map(p => p.id);

            
            if (participantIdsArray.length < 1 && document.getElementById('groupNameInputModal').value.trim() !== "Chat personal") { // Ejemplo: Mínimo 1 para grupos no personales
                // alert("Debes añadir al menos un miembro al grupo (aparte de ti).");
                // return;
            }

            const formData = new FormData();
            formData.append('group_name', document.getElementById('groupNameInputModal').value.trim());
            formData.append('participant_ids', participantIdsArray.join(',')); // Enviar como string separado por comas

            if (groupPhotoInput.files && groupPhotoInput.files[0]) {
                formData.append('group_photo', groupPhotoInput.files[0]);
            }

            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creando...';

            try {
                const response = await fetch(`${baseUri}chat/group/create`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (response.ok && result.success && result.chat_id) {
                    alert('Grupo creado exitosamente!');
                    const modalElement = document.getElementById('createGroupModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) modalInstance.hide();
                    
                    this.reset(); // Limpiar formulario
                    selectedParticipantData = [];
                    renderSelectedParticipants(); // Limpiar UI de seleccionados

                    loadUserChats(); // Recargar la lista de chats en la columna izquierda
                    // Activar el nuevo grupo (necesitarás el nombre y la foto del grupo de la respuesta o SP)
                    // Por ahora, activamos con datos genéricos, o mejor, que loadUserChats lo active.
                    // activateChat(result.chat_id, groupNameInput.value.trim(), defaultProfilePic); 
                } else {
                    alert(`Error al crear grupo: ${result.message || 'Respuesta no exitosa del servidor.'}`);
                }
            } catch (error) {
                console.error("Error al crear grupo:", error);
                alert("Error de conexión al crear el grupo.");
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
    
    // Limpiar el modal cuando se cierra
    if(createGroupModal) {
        createGroupModal.addEventListener('hidden.bs.modal', function () {
            if(createGroupForm) createGroupForm.reset();
            selectedParticipantData = [];
            if(userSearchInputForGroupModal) userSearchInputForGroupModal.value = '';
            if(userSearchResultsForGroupModal) userSearchResultsForGroupModal.innerHTML = '';
            renderSelectedParticipants();
        });
    }


    function updateChatUIVisibility(showChatInterface) {
        console.log("JS: updateChatUIVisibility, showChatInterface:", showChatInterface);
        console.log("JS: Placeholder:", noActiveChatPlaceholderContainer, "ActiveArea:", activeChatAreaContainer);

        if (showChatInterface) {
            if (activeChatAreaContainer) activeChatAreaContainer.style.display = 'flex'; else console.error("JS: activeChatAreaContainer no encontrado");
            if (chatInfoSidebarContainer) chatInfoSidebarContainer.style.display = 'block'; else console.error("JS: chatInfoSidebarContainer no encontrado");
            if (noActiveChatPlaceholderContainer) noActiveChatPlaceholderContainer.style.display = 'none'; else console.error("JS: noActiveChatPlaceholderContainer no encontrado");
        } else {
            if (activeChatAreaContainer) activeChatAreaContainer.style.display = 'none'; else console.error("JS: activeChatAreaContainer no encontrado");
            if (chatInfoSidebarContainer) chatInfoSidebarContainer.style.display = 'none'; else console.error("JS: chatInfoSidebarContainer no encontrado");
            if (noActiveChatPlaceholderContainer) noActiveChatPlaceholderContainer.style.display = 'flex'; else console.error("JS: noActiveChatPlaceholderContainer no encontrado");
        }
    }

    // --- FUNCIONES ---

    function activateChat(chatId, chatName, chatImage) {

        updateChatUIVisibility(true);
        

        const activeChatArea = document.getElementById('active-chat-area');
        // Si es el mismo chat y ya está visible, no recargar la cabecera. Podrías solo recargar mensajes.
        if (activeChatId === chatId && activeChatArea && activeChatArea.style.display === 'flex') {
            console.log(`Chat ID: ${chatId} ya está activo. Forzando recarga de mensajes.`);
            lastDisplayedMessageId = 0; // Para que cargue desde el inicio
            userHasScrolledUp = false; // Asumir que quiere ver lo último
            loadChatMessages(true); // Forzar recarga completa
            return;
        }
        
        console.log("Activando chat:", chatId, chatName);
        activeChatId = chatId;
        lastDisplayedMessageId = 0;
        userHasScrolledUp = false; 
        loadChatDetails(chatId);

         if (typeof window.showActiveChatUI === 'function') {
            window.showActiveChatUI(true);
        } else {
            console.error("activateChat: La función showActiveChatUI NO está definida globalmente.");
            // Fallback por si acaso (intenta hacerlo directamente)
            const area = document.getElementById('active-chat-area');
            const placeholder = document.getElementById('no-active-chat-placeholder');
            const sidebar = document.getElementById('chat-info-sidebar');
            if(area) area.style.display = 'flex';
            if(sidebar) sidebar.style.display = 'block'; // O 'flex' si lo cambiaste
            if(placeholder) placeholder.style.display = 'none';
        }
        if (chatHeaderNameEl) chatHeaderNameEl.textContent = chatName;
        if (chatHeaderImageEl) chatHeaderImageEl.src = chatImage;
        if (chatHeaderStatusEl) chatHeaderStatusEl.textContent = ""; // Limpiar estado anterior

        if(chatMessagesContainer) chatMessagesContainer.innerHTML = '<p class="text-center text-muted p-3">Cargando mensajes...</p>';
        
        loadChatMessages(true); // true indica carga inicial

        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(pollNewMessages, 7000); // Intervalo de polling

        document.querySelectorAll('#chat-list-container .list-group-item.active-chat').forEach(item => item.classList.remove('active-chat'));
        const activeListItem = document.querySelector(`#chat-list-container .list-group-item[data-chat-id="${chatId}"]`);
        if (activeListItem) activeListItem.classList.add('active-chat');
    }

    async function handleSendMessage(textArg = null, file = null) {
        if (!activeChatId) { alert("Por favor, selecciona un chat."); return; }
        if (!currentUserId) { alert("Error: Usuario no identificado."); return; }

        let messageText;
        if (textArg !== null && typeof textArg === 'string') {
            messageText = textArg.trim();
        } else if (messageInput) {
            messageText = messageInput.value.trim();
        } else {
            messageText = '';
        }

        if (messageText === '' && !file) {
            console.log("Mensaje vacío y sin archivo, no se envía.");
            return;
        }

        const formData = new FormData();
        formData.append('chat_id', activeChatId);
        if (messageText !== '') { formData.append('texto', messageText); }
        if (file) { formData.append('media_file', file, file.name); }

        // Guardar referencia al botón de enviar del formulario actual si es necesario
        const currentSendMessageButton = messageForm ? messageForm.querySelector('button[type="submit"]') : null;

        if (messageInput) messageInput.disabled = true;
        if (currentSendMessageButton) currentSendMessageButton.disabled = true;

        try {
            const response = await fetch(`${baseUri}chat/send_message`, { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success && result.data) {
                userHasScrolledUp = false; // Después de enviar, queremos ir al fondo
                const appended = appendMessageToChat(result.data, true);
                if (appended && parseInt(result.data.msg_id_mensaje) > lastDisplayedMessageId) {
                    lastDisplayedMessageId = parseInt(result.data.msg_id_mensaje);
                }
                if (messageInput && !file) messageInput.value = '';
            } else {
                alert(`Error al enviar mensaje: ${result.message || 'Error desconocido.'}`);
                console.error("Error al enviar mensaje:", result);
            }
        } catch (error) {
            console.error('Error en la petición de envío de mensaje:', error);
            alert('Error de conexión al enviar el mensaje.');
        } finally {
            if (messageInput) messageInput.disabled = false;
            if (currentSendMessageButton) currentSendMessageButton.disabled = false;
            if (messageInput) messageInput.focus();
        }
    }

    async function loadChatMessages(isInitialLoad = false) {
        if (!activeChatId || isLoadingMessages) return;
        isLoadingMessages = true;
        if (isInitialLoad) {
            lastDisplayedMessageId = 0; // Cargar todos los mensajes para este chat
            userHasScrolledUp = false; // Para la carga inicial, siempre ir al fondo
        }

        const limit = 50; // Cargar hasta 50 mensajes
        const offset = 0; 

        let fetchUrl = `${baseUri}chat/messages?chat_id=${activeChatId}&limit=${limit}&offset=${offset}`;
        if (!isInitialLoad && lastDisplayedMessageId > 0) {
            fetchUrl += `&last_message_id=${lastDisplayedMessageId}`; // Backend debe soportar esto
        }
        
        try {
            // console.log("Fetching messages from:", fetchUrl);
            const response = await fetch(fetchUrl);

            if (!response.ok) {
                console.error("Error en respuesta del servidor (loadChatMessages):", response.status, response.statusText);
                const errorBody = await response.text(); console.error("Cuerpo del error:", errorBody);
                if (isInitialLoad && chatMessagesContainer) chatMessagesContainer.innerHTML = `<p class="text-center text-danger p-3">Error ${response.status} al cargar mensajes.</p>`;
                isLoadingMessages = false;
                return;
            }
            const result = await response.json();
            // console.log("Messages result:", result);

            if (isInitialLoad && chatMessagesContainer) {
                chatMessagesContainer.innerHTML = ''; // Limpiar solo en carga inicial
            }

            if (result.success && result.messages && result.messages.length > 0) {
                let newMessagesWereActuallyAdded = false;
                let highestNewIdInBatch = lastDisplayedMessageId;

                result.messages.forEach(message => {
                    const wasAppended = appendMessageToChat(message, message.msg_id_emisor === currentUserId);
                    if (wasAppended) {
                        newMessagesWereActuallyAdded = true;
                        if (parseInt(message.msg_id_mensaje) > highestNewIdInBatch) {
                            highestNewIdInBatch = parseInt(message.msg_id_mensaje);
                        }
                    }
                });
                lastDisplayedMessageId = highestNewIdInBatch;

                if (isInitialLoad && chatMessagesContainer && chatMessagesContainer.innerHTML === '' && !newMessagesWereActuallyAdded) {
                     chatMessagesContainer.innerHTML = '<p class="text-center text-muted p-3">No hay mensajes. ¡Envía el primero!</p>';
                } else if (newMessagesWereActuallyAdded) {
                    if (!userHasScrolledUp || isInitialLoad) { // Si el usuario no ha scrolleado arriba o es carga inicial
                        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
                    }
                }
            } else if (isInitialLoad && result.success && result.messages.length === 0) {
                if(chatMessagesContainer) chatMessagesContainer.innerHTML = '<p class="text-center text-muted p-3">No hay mensajes. ¡Envía el primero!</p>';
            } else if (!isInitialLoad && result.success && result.messages.length === 0) {
                // console.log("Polling: No new messages.");
            } else if (!result.success && isInitialLoad) {
                 if(chatMessagesContainer) chatMessagesContainer.innerHTML = `<p class="text-center text-danger p-3">Error: ${result.message || 'Desconocido'}</p>`;
            }
        } catch (error) {
            console.error('Excepción en loadChatMessages:', error);
            if(isInitialLoad && chatMessagesContainer) chatMessagesContainer.innerHTML = '<p class="text-center text-danger p-3">Error de conexión.</p>';
        } finally {
            isLoadingMessages = false;
        }
    }
    
    function appendMessageToChat(messageData, isOwnMessage) {
        if (!chatMessagesContainer) return false;
        if (document.querySelector(`.message-bubble-wrapper[data-message-id="${messageData.msg_id_mensaje}"]`)) {
            return false; 
        }
        const noMessagesP = chatMessagesContainer.querySelector('p.text-center.text-muted');
        if (noMessagesP) noMessagesP.remove();

        const messageWrapperDiv = document.createElement('div');
        messageWrapperDiv.classList.add('d-flex', 'mb-2', 'message-bubble-wrapper');
        messageWrapperDiv.dataset.messageId = messageData.msg_id_mensaje;

        if (isOwnMessage) {
            messageWrapperDiv.classList.add('justify-content-end');
        } else {
            messageWrapperDiv.classList.add('justify-content-start');
            if (messageData.usr_emisor_foto_perfil_url) {
                const imgAvatar = document.createElement('img');
                imgAvatar.src = messageData.usr_emisor_foto_perfil_url || defaultProfilePic;
                imgAvatar.classList.add('message-emitter-avatar'); // Tu CSS debe estilizar esto
                messageWrapperDiv.appendChild(imgAvatar);
            }
        }
        const messageBubbleDiv = document.createElement('div');
        messageBubbleDiv.classList.add('message-bubble', isOwnMessage ? 'sent' : 'received'); // Clases de CSS
        const bubbleContentDiv = document.createElement('div');
        bubbleContentDiv.classList.add('bubble-content'); // Clases de CSS

        if (messageData.msg_texto && messageData.msg_texto.trim() !== '') {
            const pText = document.createElement('p');
            pText.textContent = messageData.msg_texto;
            bubbleContentDiv.appendChild(pText);
        }
        if (messageData.msg_media_mime && messageData.msg_media_url) {
            const mediaContainer = document.createElement('div');
            mediaContainer.classList.add('message-media');
            if (messageData.msg_media_mime.startsWith('image/')) {
                const imgMedia = document.createElement('img');
                imgMedia.src = messageData.msg_media_url;
                imgMedia.alt = messageData.msg_media_filename || 'Imagen adjunta';
                imgMedia.onerror = function() { console.error("Error al cargar imagen:", messageData.msg_media_url); this.alt="Error al cargar";};
                mediaContainer.appendChild(imgMedia);
            } else if (messageData.msg_media_mime.startsWith('video/')) { 
                const videoMedia = document.createElement('video');
                videoMedia.src = messageData.msg_media_url;
                videoMedia.controls = true; // Añadir controles para video
                videoMedia.classList.add('mw-100'); // Bootstrap para max-width
                mediaContainer.appendChild(videoMedia);
            } else { 
                const fileLink = document.createElement('a');
                fileLink.href = messageData.msg_media_url;
                fileLink.target = '_blank'; // Abrir en nueva pestaña
                fileLink.classList.add('message-file-link'); // Tu clase CSS
                let iconClass = 'bi-file-earmark-arrow-down'; 
                if (messageData.msg_media_mime.includes('pdf')) iconClass = 'bi-file-earmark-pdf';
                else if (messageData.msg_media_mime.includes('word')) iconClass = 'bi-file-earmark-word';
                fileLink.innerHTML = `<i class="bi ${iconClass} me-1"></i> ${messageData.msg_media_filename || 'Archivo Adjunto'}`;
                mediaContainer.appendChild(fileLink);
            }
            bubbleContentDiv.appendChild(mediaContainer);
        }
        const smallDate = document.createElement('small');
        smallDate.classList.add('message-time'); // Tu clase CSS
        try { smallDate.textContent = new Date(messageData.msg_fecha).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }); }
        catch(e) { smallDate.textContent = " "; } 
        bubbleContentDiv.appendChild(smallDate);
        messageBubbleDiv.appendChild(bubbleContentDiv);
        messageWrapperDiv.appendChild(messageBubbleDiv);
        chatMessagesContainer.appendChild(messageWrapperDiv);
        return true;
    }

    async function pollNewMessages() {
        if (!activeChatId || isLoadingMessages) return;
        loadChatMessages(false);
    }

    async function loadUserChats() {
        updateChatUIVisibility(false);
        if (!chatListContainer || !currentUserId) {
            if (typeof window.showActiveChatUI === 'function') window.showActiveChatUI(false);
            return;
        }
        if(chatListContainer) chatListContainer.innerHTML = '<p class="text-muted p-2 text-center">Cargando tus chats...</p>';
        // Llamar a showActiveChatUI(false) aquí asegura que se vea el placeholder mientras se cargan los chats.
        if (typeof window.showActiveChatUI === 'function') {
            window.showActiveChatUI(false); 
        }


        try {
            const response = await fetch(`${baseUri}chat/conversations`);
            if (!response.ok) {
                console.error("Error en respuesta del servidor (loadUserChats):", response.status, response.statusText);
                if(chatListContainer) chatListContainer.innerHTML = `<p class="text-danger p-2 text-center">Error ${response.status} al cargar chats.</p>`;
                if (typeof window.showActiveChatUI === 'function') window.showActiveChatUI(false);
                return;
            }
            const result = await response.json();
            // console.log("Chats loaded:", result);

            if(chatListContainer) chatListContainer.innerHTML = '';

            if (result.success && result.chats && result.chats.length > 0) {
                result.chats.forEach(chat => {
                    const chatItem = document.createElement('a');
                    chatItem.href = '#';
                    chatItem.classList.add('list-group-item', 'list-group-item-action', 'd-flex', 'align-items-center', 'py-3');
                    chatItem.dataset.chatId = chat.cht_id_chat;
                    const img = document.createElement('img');
                    img.src = chat.chat_image_url || defaultProfilePic;
                    img.classList.add('rounded-circle', 'me-3');
                    img.width = 45; img.height = 45; img.alt = chat.chat_name || 'Chat'; img.style.objectFit = 'cover';
                    const textDiv = document.createElement('div');
                    textDiv.classList.add('flex-grow-1', 'overflow-hidden');
                    const nameDiv = document.createElement('div');
                    nameDiv.classList.add('d-flex', 'justify-content-between', 'align-items-center');
                    const h6Name = document.createElement('h6');
                    h6Name.classList.add('mb-0', 'text-truncate');
                    h6Name.textContent = chat.chat_name || 'Nombre del Chat';
                    h6Name.title = chat.chat_name || 'Nombre del Chat';
                    const smallLastMsgDate = document.createElement('small');
                    smallLastMsgDate.classList.add('text-muted', 'ms-2', 'flex-shrink-0');
                    if (chat.last_message_date) {
                        const date = new Date(chat.last_message_date); const today = new Date();
                        if (date.toDateString() === today.toDateString()) { smallLastMsgDate.textContent = date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });}
                        else { smallLastMsgDate.textContent = date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });}
                    }
                    nameDiv.appendChild(h6Name); nameDiv.appendChild(smallLastMsgDate);
                    const smallLastMsgText = document.createElement('small');
                    smallLastMsgText.classList.add('text-muted', 'd-block', 'text-truncate');
                    smallLastMsgText.textContent = chat.last_message_text || 'No hay mensajes aún.';
                    textDiv.appendChild(nameDiv); textDiv.appendChild(smallLastMsgText);
                    chatItem.appendChild(img); chatItem.appendChild(textDiv);
                    if(chatListContainer) chatListContainer.appendChild(chatItem);
                });

                const firstChatElement = chatListContainer.querySelector('.list-group-item[data-chat-id]');
                if (firstChatElement) {
                    // No activar el chat aquí directamente, solo si no hay uno ya activo O si es el mismo
                    // La selección del usuario lo activará, o si activeChatId es null.
                    // Si no hay activeChatId, activar el primero.
                    if (!activeChatId) {
                        const firstChatId = parseInt(firstChatElement.dataset.chatId);
                        const firstChatName = firstChatElement.querySelector('h6')?.textContent || 'Chat';
                        const firstChatImage = firstChatElement.querySelector('img')?.src;
                        activateChat(firstChatId, firstChatName, firstChatImage);
                    } else {
                        // Ya hay un chat activo, asegurarse que la UI esté correcta
                         if (typeof window.showActiveChatUI === 'function') window.showActiveChatUI(true);
                    }
                } else {
                    if (typeof window.showActiveChatUI === 'function') window.showActiveChatUI(false);
                }
            } else { 
                if(chatListContainer) chatListContainer.innerHTML = '<p class="text-muted p-2 text-center">No tienes chats activos.</p>';
                if (typeof window.showActiveChatUI === 'function') window.showActiveChatUI(false);
            }
        } catch (error) {
            console.error("Excepción en loadUserChats:", error);
            if(chatListContainer) chatListContainer.innerHTML = '<p class="text-danger p-2 text-center">Error de conexión.</p>';
            if (typeof window.showActiveChatUI === 'function') window.showActiveChatUI(false);
        }
    }

    async function loadChatDetails(chatId) {
        if (!chatId) return;

        if(chatInfoNameEl) chatInfoNameEl.textContent = 'Cargando...';
        if(chatInfoDetailsEl) chatInfoDetailsEl.textContent = '...';
        if(chatInfoImageEl) chatInfoImageEl.src = defaultProfilePic; 
        if(sharedFilesContainerEl) sharedFilesContainerEl.innerHTML = '<p class="text-muted small">Cargando archivos...</p>';

        try {
            const response = await fetch(`${baseUri}chat/details/${chatId}`); 
            if (!response.ok) {
                console.error("Error al cargar detalles del chat:", response.status, response.statusText);
                if(chatInfoNameEl) chatInfoNameEl.textContent = 'Error al cargar';
                const adminControlsContainer = document.getElementById('chat-info-admin-controls');
                if (adminControlsContainer) adminControlsContainer.innerHTML = ''; 
                return;
            }
            const result = await response.json();
            console.log("Detalles del chat (desde loadChatDetails, raw result):", JSON.stringify(result, null, 2)); 

            if (result.success && result.details) {
                console.log("loadChatDetails: Llamando a populateChatInfoSidebar con:", JSON.stringify(result.details, null, 2));
                populateChatInfoSidebar(result.details); 

                if (result.shared_files) { 
                    populateSharedFiles(result.shared_files);
                } else {
                    if(sharedFilesContainerEl) sharedFilesContainerEl.innerHTML = '<p class="text-muted small">No hay archivos compartidos.</p>';
                }
            } else {
                console.error("Error en datos de detalles del chat (result.success false o result.details no existe):", result.message || "Mensaje de error no disponible en result.");
                if(chatInfoNameEl) chatInfoNameEl.textContent = 'Error';
                const adminControlsContainer = document.getElementById('chat-info-admin-controls');
                if (adminControlsContainer) adminControlsContainer.innerHTML = '';
            }
        } catch (error) {
            console.error("Excepción al cargar detalles del chat (catch):", error);
            if(chatInfoNameEl) chatInfoNameEl.textContent = 'Error de conexión';
            const adminControlsContainer = document.getElementById('chat-info-admin-controls');
            if (adminControlsContainer) adminControlsContainer.innerHTML = '';
        }
    }

    function populateChatInfoSidebar(details) {
        console.log("populateChatInfoSidebar - INICIO - Recibiendo detalles:", JSON.stringify(details, null, 2));
        
        
        if (!details) {
            console.error("populateChatInfoSidebar: ¡Los detalles son null o undefined!");
            if(chatInfoNameEl) chatInfoNameEl.textContent = 'Error al cargar detalles';
            // Limpiar otros elementos de la sidebar si es necesario
            const adminControlsContainer = document.getElementById('chat-info-admin-controls');
            if (adminControlsContainer) adminControlsContainer.innerHTML = '';
            return;
        }

        // --------------------------------------------------------------------
        // 1. POBLAR INFORMACIÓN BÁSICA (adaptado a los nombres de tu SP)
        // --------------------------------------------------------------------
        // El SP sp_get_chat_details_info devuelve: is_group, entity_name, entity_username, entity_description, 
        // entity_image_url, member_count, creation_date, user_member_since, current_user_is_admin.

        if (details.is_group !== undefined) { // Asegurarse que is_group exista
            details.is_group = Boolean(details.is_group); // Convertir 0/1 a false/true
        } else {
            console.error("populateChatInfoSidebar: details.is_group no está definido.");
            // Manejar error o salir
            return;
        }

        if (details.current_user_is_admin !== undefined) { // Asegurarse que current_user_is_admin exista
            details.current_user_is_admin = Boolean(details.current_user_is_admin); // Convertir 0/1 a false/true
        } else {
            console.warn("populateChatInfoSidebar: details.current_user_is_admin no está definido. Asumiendo false.");
            details.current_user_is_admin = false;
        }

        console.log("Valor de details.is_group después de convertir:", details.is_group, typeof details.is_group);
        console.log("Valor de details.current_user_is_admin después de convertir:", details.current_user_is_admin, typeof details.current_user_is_admin);

        if(chatInfoImageEl) chatInfoImageEl.src = details.image_url || defaultProfilePic;
        if(chatInfoNameEl) chatInfoNameEl.textContent = details.name || (details.is_group ? 'Grupo Desconocido' : 'Usuario Desconocido');
        if(chatInfoDetailsEl) chatInfoDetailsEl.textContent = details.username_or_details || '---'; // Esto ya tiene "X miembros" o "@username"

        if (details.is_group) {
            // 'username_or_details' ya tiene el contador de miembros formateado por el controlador.
            // 'description_or_status' tiene la descripción del grupo.
            // 'created_or_member_since' tiene la fecha de creación.
            if(chatInfoExtraLine1El) chatInfoExtraLine1El.textContent = `Descripción: ${details.description_or_status || 'Sin descripción.'}`;
            if(chatInfoExtraLine2El && details.created_or_member_since) {
                chatInfoExtraLine2El.textContent = `Creado: ${new Date(details.created_or_member_since).toLocaleDateString()}`;
            } else if (chatInfoExtraLine2El) {
                chatInfoExtraLine2El.textContent = "---"; // Placeholder si no hay fecha
            }
        } else { // Chat individual
            // 'description_or_status' tiene la biografía del usuario.
            // 'created_or_member_since' tiene la fecha desde que es miembro (o fecha de creación del chat individual).
            if(chatInfoExtraLine1El && details.created_or_member_since) {
                chatInfoExtraLine1El.textContent = `Miembro desde: ${new Date(details.created_or_member_since).toLocaleDateString()}`;
            } else if (chatInfoExtraLine1El) {
                chatInfoExtraLine1El.textContent = "---";
            }
            if(chatInfoExtraLine2El) {
                chatInfoExtraLine2El.textContent = details.description_or_status || 'Sin biografía.';
            }
        }


        // --------------------------------------------------------------------
        // 2. MANEJO DE CONTROLES DE ADMIN
        // --------------------------------------------------------------------
        
        if (!adminControlsContainer) {
            console.warn("Contenedor #chat-info-admin-controls no encontrado en el DOM. No se pueden mostrar controles de admin.");
            // No retornamos aquí necesariamente, porque la info básica ya se pobló.
        } else {
            adminControlsContainer.innerHTML = ''; // Limpiar siempre los controles de un chat anterior

            // Ahora la condición crucial
            if (details.is_group && details.current_user_is_admin) {
                 console.log("POPULATE: CONDICIÓN ADMIN (menos estricta) CUMPLIDA.");

                // 1. Botón para Editar Grupo
                const editGroupBtn = document.createElement('button');
                editGroupBtn.classList.add('btn', 'btn-outline-primary', 'btn-sm', 'mt-2', 'mb-2', 'w-100');
                editGroupBtn.innerHTML = '<i class="bi bi-pencil-square me-2"></i> Editar Información del Grupo';
                editGroupBtn.type = 'button';
                editGroupBtn.addEventListener('click', () => {
                    console.log("Botón Editar clickeado. Datos completos del chat (details):", details);
                    const modalGroupDetails = { 
                        name: details.name,
                        description_or_status: details.entity_description, 
                        image_url: details.image_url 
                    };
                    openEditGroupModal(modalGroupDetails); 
                });
                adminControlsContainer.appendChild(editGroupBtn);
                console.log("Botón de editar añadido.");

                // 2. Sección para Gestionar Miembros
                const manageMembersSection = document.createElement('div');
                manageMembersSection.classList.add('mt-3');
                manageMembersSection.innerHTML = `
                    <h6 class="mb-2">Gestionar Miembros</h6>
                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm" id="userSearchInputForEditGroup" placeholder="Buscar para añadir miembro...">
                        <div id="userSearchResultsForEditGroup" class="list-group mt-1 user-search-results-admin" style="max-height: 100px; overflow-y: auto; border: 1px solid #ced4da; border-radius: 0.25rem;"></div>
                    </div>
                    <label class="form-label small mb-1">Miembros Actuales:</label>
                    <div id="currentGroupMembersList" class="list-group list-group-flush border rounded admin-members-list" style="max-height: 150px; overflow-y: auto;">
                        <div class="list-group-item text-muted small">Cargando miembros...</div>
                    </div>
                `;
                adminControlsContainer.appendChild(manageMembersSection);
                console.log("Sección de gestionar miembros añadida.");

                console.log("Llamando a loadCurrentGroupMembers y setupUserSearchForEditGroup...");
                if (activeChatId) { // Asegurarse que activeChatId esté definido
                    loadCurrentGroupMembers(activeChatId);
                    setupUserSearchForEditGroup(activeChatId);
                } else {
                    console.error("populateChatInfoSidebar: activeChatId no está definido al intentar cargar miembros/setup búsqueda.");
                }
                console.log("Llamadas a funciones de miembros completadas (o intentadas).");

            } else if (details.is_group) {
                console.log("Es grupo, PERO la condición de admin NO se cumplió. current_user_is_admin fue:", details.current_user_is_admin);
            } else {
                console.log("No es un grupo, no se muestran controles de admin.");
            }
        }
    }

    function populateSharedFiles(files) {
    if (!sharedFilesContainerEl) {
        console.error("populateSharedFiles: sharedFilesContainerEl no encontrado.");
        return;
    }
    sharedFilesContainerEl.innerHTML = ''; // Limpiar

    if (!files || files.length === 0) {
        sharedFilesContainerEl.innerHTML = '<p class="text-muted small p-2">No hay archivos compartidos.</p>'; // Añadir padding para que se vea mejor
        return;
    }

    files.forEach(file => {
        // console.log("Procesando archivo para la lista:", file); // Útil para depurar
        const fileItemLink = document.createElement('a');
        fileItemLink.href = `${baseUri}chat/media/${file.msg_id_mensaje}`;
        fileItemLink.target = '_blank';
        fileItemLink.classList.add('list-group-item', 'list-group-item-action', 'small', 'd-flex', 'align-items-center', 'text-truncate');

        let iconClass = 'bi-file-earmark-arrow-down'; // Icono por defecto
        if (file.msg_media_mime) {
            if (file.msg_media_mime.startsWith('image/')) iconClass = 'bi-file-image';
            else if (file.msg_media_mime.startsWith('video/')) iconClass = 'bi-film'; // Icono para video
            else if (file.msg_media_mime.startsWith('audio/')) iconClass = 'bi-file-earmark-music'; // Icono para audio
            else if (file.msg_media_mime.includes('pdf')) iconClass = 'bi-file-earmark-pdf';
            else if (file.msg_media_mime.includes('word')) iconClass = 'bi-file-earmark-word';
            else if (file.msg_media_mime.includes('excel') || file.msg_media_mime.includes('spreadsheet')) iconClass = 'bi-file-earmark-excel';
            else if (file.msg_media_mime.includes('presentation') || file.msg_media_mime.includes('powerpoint')) iconClass = 'bi-file-earmark-slides';
            else if (file.msg_media_mime.includes('zip') || file.msg_media_mime.includes('archive')) iconClass = 'bi-file-earmark-zip';
            else if (file.msg_media_mime.includes('text')) iconClass = 'bi-file-earmark-text';
        }

        // Usar el nombre de archivo del SP (que ya tiene el IFNULL) o un genérico
        const fileName = file.msg_media_filename || `archivo_${file.msg_id_mensaje}`;

        // CONSTRUCCIÓN CORRECTA DEL INNERHTML USANDO TEMPLATE LITERALS (BACKTICKS)
        fileItemLink.innerHTML = `
            <i class="bi ${iconClass} me-2 fs-5"></i>
            <span class="flex-grow-1" title="${fileName}">${fileName}</span>
        `;
        // El span con flex-grow-1 ayuda al text-truncate si el nombre es muy largo y hay otros elementos (como una fecha o tamaño)

        sharedFilesContainerEl.appendChild(fileItemLink);
    });
    }

    function updateHiddenParticipantIdsInput() {
        const groupParticipantIdsInput = document.getElementById('groupParticipantIdsInputModal'); // Ya lo tienes como groupParticipantIdsInputModal
        if (groupParticipantIdsInput) {
            // Extraer solo los IDs
            const idsArray = selectedParticipantsData.map(p => p.id);
            groupParticipantIdsInput.value = idsArray.join(','); // Enviar como string separado por comas
        }
    }

    function openEditGroupModal(modalData) { 
                                           
        const modalElement = document.getElementById('editGroupModal');
        if (!modalElement) {
            console.error("Modal de edición de grupo #editGroupModal no encontrado.");
            alert("Error: Funcionalidad de edición no disponible.");
            return;
        }

        console.log("openEditGroupModal - Recibiendo datos para poblar modal:", modalData);
        console.log("openEditGroupModal - Usando activeChatId:", activeChatId);


        // --- Poblar los campos del formulario ---
        const groupNameInput = document.getElementById('editGroupNameInputModal');
        const groupIdInput = document.getElementById('editGroupIdInputModal'); // Para el ID del grupo
        const currentPhotoPreview = document.getElementById('currentGroupPhotoPreview');
        const noPhotoText = document.getElementById('noCurrentGroupPhotoText');
        const newPhotoInput = document.getElementById('editGroupPhotoInputModal');


        if (groupNameInput) {
            groupNameInput.value = modalData.name || '';
        } else {
            console.error("Input #editGroupNameInputModal no encontrado en modal de edición.");
        }

        if (groupIdInput && activeChatId) { // activeChatId debe estar seteado
            groupIdInput.value = activeChatId;
        } else {
            console.error("Input #editGroupIdInputModal no encontrado o activeChatId no está definido.");
        }


        if (currentPhotoPreview && noPhotoText) {
            if (modalData.image_url && modalData.image_url !== defaultProfilePic && !modalData.image_url.endsWith('default_group_icon.png')) { // Evitar mostrar el placeholder como "foto actual"
                currentPhotoPreview.src = modalData.image_url;
                currentPhotoPreview.style.display = 'block';
                noPhotoText.style.display = 'none';
            } else {
                currentPhotoPreview.src = ''; // Limpiar por si acaso
                currentPhotoPreview.style.display = 'none';
                noPhotoText.style.display = 'block';
            }
        }

        // Limpiar el input de nueva foto
        if (newPhotoInput) {
            newPhotoInput.value = ''; // Resetear el campo de archivo
        }


        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
        modalInstance.show();
    }

    const editGroupForm = document.getElementById('editGroupForm'); // Asegúrate que el form en el modal tenga este ID
        if (editGroupForm) {
            editGroupForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                const groupId = document.getElementById('editGroupIdInputModal').value;
                const groupName = document.getElementById('editGroupNameInputModal').value.trim();
                const groupPhotoInput = document.getElementById('editGroupPhotoInputModal');

                if (!groupName) {
                    alert("El nombre del grupo es requerido.");
                    return;
                }

                const formData = new FormData();
                formData.append('group_id', groupId);
                formData.append('group_name', groupName);
                if (groupPhotoInput.files && groupPhotoInput.files[0]) {
                    formData.append('group_photo', groupPhotoInput.files[0]);
                }

                // Lógica de Fetch a POST /chat/group/update_info
                try {
                    const response = await fetch(`${baseUri}chat/group/update_info`, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        alert("Información del grupo actualizada.");
                        bootstrap.Modal.getInstance(document.getElementById('editGroupModal')).hide();
                        loadChatDetails(groupId); // Recargar detalles en la sidebar
                        loadUserChats(); // Recargar lista de chats por si cambió el nombre/foto
                    } else {
                        alert(`Error: ${result.message || 'No se pudo actualizar el grupo.'}`);
                    }
                } catch (error) {
                    console.error("Error al actualizar grupo:", error);
                    alert("Error de conexión al actualizar el grupo.");
                }
            });
        }



    async function loadCurrentGroupMembers(groupId) {
        const membersListContainer = document.getElementById('currentGroupMembersList');
        if (!membersListContainer) return;
        membersListContainer.innerHTML = '<div class="list-group-item text-muted small">Cargando miembros...</div>';

        try {
            const response = await fetch(`${baseUri}chat/group/${groupId}/members`);
            const result = await response.json();

            if (result.success && result.members) {
                membersListContainer.innerHTML = ''; // Limpiar
                if (result.members.length > 0) {
                    result.members.forEach(member => {
                        const memberItem = document.createElement('div');
                        memberItem.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center', 'py-2', 'px-2');
                        memberItem.innerHTML = `
                            <div class="d-flex align-items-center">
                                <img src="${member.profile_pic_url || defaultProfilePic}" class="rounded-circle me-2" width="30" height="30" alt="${member.username}" style="object-fit:cover;">
                                <div class="small">
                                    <strong class="d-block text-truncate">${member.full_name || member.username}</strong>
                                    <span class="text-muted d-block text-truncate">@${member.username}</span>
                                </div>
                            </div>
                            ${ (member.user_id !== currentUserId && result.current_user_is_admin) // No permitir eliminar al admin actual si es el único o es el mismo
                                ? `<button class="btn btn-outline-danger btn-sm py-0 px-1 remove-member-from-group-btn" data-user-id="${member.user_id}" data-group-id="${groupId}" title="Eliminar miembro"><i class="bi bi-trash"></i></button>`
                                : ''
                            }
                        `;
                        membersListContainer.appendChild(memberItem);
                    });
                } else {
                    membersListContainer.innerHTML = '<div class="list-group-item text-muted small">No hay miembros en este grupo (aparte de ti, si eres el creador).</div>';
                }
            } else {
                membersListContainer.innerHTML = `<div class="list-group-item text-danger small">${result.message || 'Error al cargar miembros.'}</div>`;
            }
        } catch (error) {
            console.error("Error cargando miembros del grupo:", error);
            membersListContainer.innerHTML = '<div class="list-group-item text-danger small">Error de conexión al cargar miembros.</div>';
        }
    }

    // Delegación de eventos para eliminar miembro
    document.body.addEventListener('click', async function(event) {
        const removeBtn = event.target.closest('.remove-member-from-group-btn');
        if (removeBtn) {
            const userIdToRemove = removeBtn.dataset.userId;
            const groupId = removeBtn.dataset.groupId;
            if (!confirm(`¿Seguro que quieres eliminar a este miembro del grupo?`)) return;

            try {
                const response = await fetch(`${baseUri}chat/group/remove_member`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ group_id: groupId, user_id_to_remove: userIdToRemove })
                });
                const result = await response.json();
                if (result.success) {
                    alert("Miembro eliminado.");
                    loadCurrentGroupMembers(groupId); // Recargar lista de miembros
                    loadChatDetails(groupId); // Recargar detalles del chat (para el contador de miembros)
                } else {
                    alert(`Error: ${result.message || 'No se pudo eliminar al miembro.'}`);
                }
            } catch (error) {
                console.error("Error al eliminar miembro:", error);
                alert("Error de conexión al eliminar miembro.");
            }
        }
    });

    function setupUserSearchForEditGroup(groupId) {
        const searchInput = document.getElementById('userSearchInputForEditGroup');
        const searchResultsContainer = document.getElementById('userSearchResultsForEditGroup');
        let searchTimeoutIdEdit;

        if (!searchInput || !searchResultsContainer) return;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeoutIdEdit);
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) {
                searchResultsContainer.innerHTML = '';
                return;
            }

            searchTimeoutIdEdit = setTimeout(async () => {
                searchResultsContainer.innerHTML = '<p class="text-muted small p-2 text-center">Buscando...</p>';
                try {
                    // Necesitas una ruta que busque usuarios EXCLUYENDO los que ya están en el grupo.
                    const response = await fetch(`${baseUri}chat/users/search_for_group?term=${encodeURIComponent(searchTerm)}&exclude_from_chat_id=${groupId}&limit=5`);
                    const result = await response.json();

                    searchResultsContainer.innerHTML = '';
                    if (result.success && result.users && result.users.length > 0) {
                        result.users.forEach(user => {
                            const userDiv = document.createElement('div');
                            userDiv.classList.add('list-group-item', 'list-group-item-action', 'd-flex', 'align-items-center', 'py-2', 'px-2', 'search-result-item');
                            userDiv.style.cursor = 'pointer';
                            userDiv.innerHTML = `
                                <img src="${user.profile_pic_url || defaultProfilePic}" class="rounded-circle me-2" width="30" height="30" alt="${user.username}" style="object-fit:cover;">
                                <div class="flex-grow-1">
                                    <small class="fw-bold d-block text-truncate">${user.full_name || user.username}</small>
                                    <small class="text-muted d-block text-truncate">@${user.username}</small>
                                </div>
                            `;
                            userDiv.addEventListener('click', async () => {
                                // Acción para añadir miembro
                                try {
                                    const addResponse = await fetch(`${baseUri}chat/group/add_member`, {
                                        method: 'POST',
                                        headers: {'Content-Type': 'application/json'},
                                        body: JSON.stringify({ group_id: groupId, user_id_to_add: user.user_id })
                                    });
                                    const addResult = await addResponse.json();
                                    if (addResult.success) {
                                        alert(`${user.full_name || user.username} añadido al grupo.`);
                                        searchInput.value = '';
                                        searchResultsContainer.innerHTML = '';
                                        loadCurrentGroupMembers(groupId); // Recargar lista
                                        loadChatDetails(groupId); // Recargar detalles del chat
                                    } else {
                                        alert(`Error: ${addResult.message || 'No se pudo añadir al miembro.'}`);
                                    }
                                } catch (addError) {
                                    alert("Error de conexión al añadir miembro.");
                                }
                            });
                            searchResultsContainer.appendChild(userDiv);
                        });
                    } else {
                        searchResultsContainer.innerHTML = '<p class="text-muted small p-2 text-center">No se encontraron usuarios o ya son miembros.</p>';
                    }
                } catch (error) {
                    searchResultsContainer.innerHTML = '<p class="text-danger small p-2 text-center">Error de conexión al buscar.</p>';
                }
            }, 500);
        });
    }


});