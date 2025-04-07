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
    const modalHTML = `
    <div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center w-100">
                        <img src="./pictures/pic.jpg" class="rounded-circle me-2" width="45" height="45">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-bold">Anya Forger</h6>
                            <select class="form-select form-select-sm border-0 p-0" style="width: auto;">
                                <option>Público</option>
                                <option>Privado</option>
                                <option>Solo amigos</option>
                            </select>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                
                <div class="modal-body p-5 pb-2">
                    <textarea class="form-control border-0 fs-5" 
                              placeholder="¿Qué estás pensando, Anya?" 
                              rows="5"
                              style="resize: none;"></textarea>
                    
                    <div class="preview-area mb-3 mt-3"></div>
                    
                    <div class="d-flex align-items-center gap-2 border-top pt-3">
                        <label class="btn btn-custom rounded-pill px-3 py-2">
                            <i class="bi bi-image me-2"></i>Foto/Video
                            <input type="file" hidden accept="image/*,video/*">
                        </label>
                        
                        <button class="btn btn-custom rounded-pill px-3 py-2">
                            <i class="bi bi-filetype-gif me-2"></i>GIF
                        </button>
                    </div>
                </div>
                
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-custom rounded-pill px-4 py-2 w-100">
                        Publicar
                    </button>
                </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    document.querySelectorAll('.feed-input').forEach(input => {
        input.addEventListener('click', () => {
            new bootstrap.Modal(document.getElementById('postModal')).show();
        });
    });
}

///////////// VENTANA MODAL "AJUSTES"

function createSettingsModal() {
    const modalHTML = `
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Configuración de perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="profileSettingsForm">
                        <!-- Sección Header -->
                        <div class="mb-4">
                            <label class="form-label">Foto de portada</label>
                            <div class="cover-preview-container mb-3">
                                <img id="coverPreview" class="cover-preview">
                                <input type="file" id="coverInput" accept="image/*" hidden>
                                <button type="button" class="btn btn-upload-cover btn-custom border" onclick="document.getElementById('coverInput').click()">
                                    <i class="bi bi-camera"></i> Cambiar portada
                                </button>
                            </div>
                        </div>

                        <!-- Sección Avatar -->
                        <div class="mb-4">
                            <label class="form-label">Foto de perfil</label>
                            <div class="avatar-preview-container mb-3">
                                <img id="avatarPreview" class="avatar-preview rounded-circle">
                                <input type="file" id="avatarInput" accept="image/*" hidden>
                                <button type="button" class="btn btn-upload-avatar btn-custom" onclick="document.getElementById('avatarInput').click()">
                                    <i class="bi bi-camera"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Campos de nombre divididos -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombres</label>
                                <input type="text" class="form-control" id="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellidos</label>
                                <input type="text" class="form-control" id="lastName" required>
                            </div>
                        </div>

                        <!-- Campo de biografía -->
                        <div class="mb-3">
                            <label class="form-label">Biografía</label>
                            <textarea class="form-control" id="profileBio" rows="3" 
                                placeholder="Cuéntanos algo sobre ti..."></textarea>
                        </div>

                        <!-- Selector de privacidad -->
                        <div class="mb-4">
                            <label class="form-label">Privacidad del perfil</label>
                            <select class="form-select" id="profilePrivacy">
                                <option value="public">Público</option>
                                <option value="private">Privado</option>
                                <option value="friends">Solo amigos</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-custom w-100">Guardar cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Manejar cambio de imágenes
    document.getElementById('coverInput').addEventListener('change', function(e) {
        handleImageUpload(e.target.files[0], 'coverPreview');
    });

    document.getElementById('avatarInput').addEventListener('change', function(e) {
        handleImageUpload(e.target.files[0], 'avatarPreview');
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
    createNotificationsModal();
    createPostModal();
    createSettingsModal();
    
    const settingsButton = document.getElementById('settingsButton');
    const settingsModal = new bootstrap.Modal(document.getElementById('settingsModal'));
    
    settingsButton.addEventListener('click', () => {
        const currentName = document.querySelector('.card-title').innerText;
        const currentBio = document.querySelector('.card-text').innerText;
        
        document.getElementById('profileName').value = currentName;
        document.getElementById('profileBio').value = currentBio;
        
        settingsModal.show();
    });

    document.getElementById('profileSettingsForm').addEventListener('submit', (e) => {
        e.preventDefault();
        
        const newName = document.getElementById('profileName').value;
        const newBio = document.getElementById('profileBio').value;
        
        document.querySelector('.card-title').innerText = newName;
        document.querySelector('.card-text').innerText = newBio;
        
        settingsModal.hide();
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


