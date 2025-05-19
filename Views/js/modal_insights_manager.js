// modal_insights_manager.js

// --- Variables Globales para Instancias de Gráficas ---
let insightsDemographicsChart = null;
let insightsFollowersChart = null;
let insightsActivityChart = null;
let insightsInteractionsPieChart = null;

// --- ID del Usuario Target para los Insights ---
let insightsTargetUserId = null;

/**
 * Crea e inyecta el HTML del modal de insights en el body.
 * Configura los listeners básicos del modal.
 */
function createAndSetupInsightsModal() {
    if (document.getElementById('starnestInsightsModal')) {
        return; // El modal ya existe
    }

    const modalHTML = `
        <div class="modal fade" id="starnestInsightsModal" tabindex="-1" aria-labelledby="starnestInsightsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="starnestInsightsModalLabel"><i class="bi bi-graph-up me-2"></i>Insights de la Cuenta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container-fluid">
                            <div class="row mb-3">
                                <div class="col-md-auto">
                                    <label for="insightsPeriod" class="form-label">Periodo:</label>
                                    <div class="btn-group" role="group" id="insightsPeriodBtns">
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-period="7days">7 Días</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm active" data-period="30days">30 Días</button>
                                        </div>
                                </div>
                                <div class="col-md-auto">
                                    <label for="insightsContentType" class="form-label">Contenido:</label>
                                    <select class="form-select form-select-sm" id="insightsContentType" style="width: 200px;">
                                        <option value="Publicaciones" selected>Publicaciones</option>
                                        <option value="Shorts">Shorts</option>
                                        </select>
                                </div>
                            </div>

                            <nav>
                                <div class="nav nav-tabs" id="insightsMainTabs" role="tablist">
                                    <button class="nav-link active" id="tab-btn-audience" data-bs-toggle="tab" data-bs-target="#tab-pane-audience" type="button" role="tab" aria-controls="tab-pane-audience" aria-selected="true"><i class="bi bi-people-fill me-1"></i>Audiencia</button>
                                    <button class="nav-link" id="tab-btn-performance" data-bs-toggle="tab" data-bs-target="#tab-pane-performance" type="button" role="tab" aria-controls="tab-pane-performance" aria-selected="false"><i class="bi bi-lightning-charge-fill me-1"></i>Rendimiento</button>
                                    <button class="nav-link" id="tab-btn-interactions" data-bs-toggle="tab" data-bs-target="#tab-pane-interactions" type="button" role="tab" aria-controls="tab-pane-interactions" aria-selected="false"><i class="bi bi-bar-chart-steps me-1"></i>Interacciones</button>
                                </div>
                            </nav>
                            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="insightsTabContent">
                                <div class="tab-pane fade show active" id="tab-pane-audience" role="tabpanel" aria-labelledby="tab-btn-audience">
                                    <h5>Demografía de la Audiencia</h5>
                                    <p class="text-muted small">Usuarios que interactuaron con tu contenido.</p>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="chart-container" style="height:300px;"><canvas id="insightsDemographicsChart"></canvas></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <h6><i class="bi bi-geo-alt-fill me-1"></i>Top Países</h6>
                                            <div id="insightsLocationList" class="list-group"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-pane-performance" role="tabpanel" aria-labelledby="tab-btn-performance">
                                    <div class="row">
                                        <div class="col-md-7 mb-3">
                                            <h5>Evolución de Seguidores</h5>
                                            <div class="chart-container" style="height:300px;"><canvas id="insightsFollowersChart"></canvas></div>
                                        </div>
                                        <div class="col-md-5 mb-3">
                                            <h5><i class="bi bi-trophy-fill me-1"></i>Top Contenido</h5>
                                            <div id="insightsTopContentList" class="list-group"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-pane-interactions" role="tabpanel" aria-labelledby="tab-btn-interactions">
                                    <div class="row">
                                        <div class="col-md-7 mb-3">
                                            <h5>Horario de Actividad</h5>
                                            <p class="text-muted small">Hora del día con más interacciones en tu contenido (UTC).</p>
                                            <div class="chart-container" style="height:300px;"><canvas id="insightsActivityChart"></canvas></div>
                                        </div>
                                        <div class="col-md-5 mb-3">
                                            <h5>Tipos de Interacción</h5>
                                            <div class="chart-container" style="height:300px;"><canvas id="insightsInteractionsPieChart"></canvas></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modalElement = document.getElementById('starnestInsightsModal');

    // Evento cuando el modal se muestra
    modalElement.addEventListener('shown.bs.modal', function(event) {
        const triggerButton = event.relatedTarget;
        insightsTargetUserId = triggerButton?.dataset.userId || window.currentUserData?.userId;

        if (!insightsTargetUserId) {
            console.error("Insights Modal: No se pudo determinar el targetUserId.");
            modalElement.querySelector('.modal-body').innerHTML = '<p class="text-danger text-center">Error: Usuario no identificado.</p>';
            return;
        }
        console.log("Insights Modal: Mostrando para User ID:", insightsTargetUserId);
        loadAllInsightsData(); // Carga inicial con filtros por defecto
    });

    // Evento cuando el modal se oculta
    modalElement.addEventListener('hidden.bs.modal', function() {
        destroyAllCharts();
        insightsTargetUserId = null;
        // Limpiar áreas de contenido si es necesario (opcional, ya que se recargan)
        document.getElementById('insightsLocationList').innerHTML = '';
        document.getElementById('insightsTopContentList').innerHTML = '';
    });

    // Listeners para los filtros
    document.querySelectorAll('#insightsPeriodBtns .btn').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('#insightsPeriodBtns .btn').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            loadAllInsightsData();
        });
    });

    document.getElementById('insightsContentType').addEventListener('change', function() {
        loadAllInsightsData();
    });
}

/**
 * Obtiene los valores actuales de los filtros.
 */
function getCurrentInsightsFilters() {
    const periodButton = document.querySelector('#insightsPeriodBtns .btn.active');
    const period = periodButton ? periodButton.dataset.period : '30days';
    const contentType = document.getElementById('insightsContentType').value;
    return { period, contentType, userId: insightsTargetUserId };
}

/**
 * Carga todos los datos de los insights basados en los filtros actuales.
 */
function loadAllInsightsData() {
    if (!insightsTargetUserId) return;

    const filters = getCurrentInsightsFilters();
    console.log("Insights Modal: Cargando datos con filtros:", filters);

    // Mostrar placeholders de carga
    showChartLoadingPlaceholder('insightsDemographicsChart', 'Cargando demografía...');
    showListLoadingPlaceholder('insightsLocationList', 'Cargando ubicaciones...');
    showChartLoadingPlaceholder('insightsFollowersChart', 'Cargando evolución de seguidores...');
    showListLoadingPlaceholder('insightsTopContentList', 'Cargando top contenido...');
    showChartLoadingPlaceholder('insightsActivityChart', 'Cargando actividad horaria...');
    showChartLoadingPlaceholder('insightsInteractionsPieChart', 'Cargando tipos de interacción...');


    // Llamadas a las funciones de carga específicas
    fetchDemographicsData(filters);
    fetchFollowerEvolutionData(filters); // Necesitarás este SP y método en el backend
    fetchTopContentData(filters);
    fetchHourlyActivityData(filters);
    fetchInteractionTypesData(filters);
}

/**
 * Funciones para mostrar placeholders de carga
 */
function showChartLoadingPlaceholder(canvasId, text = "Cargando datos...") {
    const canvas = document.getElementById(canvasId);
    if (canvas && canvas.getContext) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = "16px Arial";
        ctx.fillStyle = "#888";
        ctx.textAlign = "center";
        ctx.fillText(text, canvas.width / 2, canvas.height / 2);
    }
}
function showListLoadingPlaceholder(elementId, text = "Cargando...") {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `<div class="text-center text-muted p-3"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${text}</div>`;
    }
}

/**
 * Destruye todas las instancias de Chart.js.
 */
function destroyAllCharts() {
    if (insightsDemographicsChart) { insightsDemographicsChart.destroy(); insightsDemographicsChart = null; }
    if (insightsFollowersChart) { insightsFollowersChart.destroy(); insightsFollowersChart = null; }
    if (insightsActivityChart) { insightsActivityChart.destroy(); insightsActivityChart = null; }
    if (insightsInteractionsPieChart) { insightsInteractionsPieChart.destroy(); insightsInteractionsPieChart = null; }
    console.log("Insights Modal: Gráficas destruidas.");
}

// --- Funciones de Fetch y Renderizado Específicas ---

async function fetchDemographicsData({ userId, period, contentType }) {
    // El SP sp_get_demographics_insights devuelve dos result sets: gender_age y country
    const basePath = window.basePath || '/ProyectoBDM/';
    const endpoint = `${basePath}insights/demographics?user_id=${userId}&period=${period}&contentType=${contentType}`;
    console.log("JS: Fetching demographics from:", endpoint);

    try {
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();

        if (result.success && result.data) {
            // Renderizar gráfica de demografía (género y edad)
            if (insightsDemographicsChart) { insightsDemographicsChart.destroy(); insightsDemographicsChart = null; }
            const demoCanvas = document.getElementById('insightsDemographicsChart');
            const demoCard = demoCanvas ? demoCanvas.closest('.metric-card') : null; // Contenedor para el texto
            let existingSummary = demoCard ? demoCard.querySelector('.gender-summary-text') : null;
            if (existingSummary) existingSummary.remove(); // Limpiar texto anterior

            if (demoCanvas && result.data.gender_age && result.data.gender_age.length > 0) {
                // No necesitamos agrupar solo por género, usamos la combinación directa
                const demographicData = result.data.gender_age;

                const totalInteractingUsers = demographicData.reduce((sum, item) => sum + parseInt(item.count_unique_users), 0);

                const labels = [];
                const dataValues = [];
                const percentages = [];

                demographicData.forEach(item => {
                    // Crear etiqueta combinada
                    labels.push(`${item.genero || 'Desconocido'} (${item.rango_edad || 'N/A'})`);
                    dataValues.push(parseInt(item.count_unique_users));
                    percentages.push(totalInteractingUsers > 0 ? ((parseInt(item.count_unique_users) / totalInteractingUsers) * 100).toFixed(1) + '%' : '0.0%');
                });

                const backgroundColors = ['#0d6efd', '#ffc107', '#6f42c1', '#198754', '#dc3545', '#adb5bd', '#0dcaf0', '#fd7e14']; // Más colores

                insightsDemographicsChart = new Chart(demoCanvas, {
                    type: 'doughnut', // Doughnut puede ser mejor si hay muchas rebanadas
                    data: {
                        labels: labels.map((label, index) => `${label}: ${dataValues[index]} (${percentages[index]})`),
                        datasets: [{
                            label: 'Demografía de Audiencia',
                            data: dataValues,
                            backgroundColor: backgroundColors.slice(0, labels.length), // Asegura suficientes colores
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right', // 'bottom' o 'right' pueden ser mejores si hay muchas etiquetas
                                labels: {
                                    font: { size: 10 } // Letra más pequeña para la leyenda
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        // context.label ya incluye el desglose, conteo y porcentaje
                                        return context.label;
                                    }
                                }
                            }
                        }
                    }
                });

                // Mostrar el resumen de texto (opcional, la leyenda ya es detallada)
                if (demoCard) {
                    let summaryTextHTML = '<ul class="list-unstyled small mt-3 text-center gender-summary-text">';
                    labels.forEach((label, index) => {
                        summaryTextHTML += `<li>${label}: ${dataValues[index]} (${percentages[index]})</li>`;
                    });
                    summaryTextHTML += '</ul>';
                    // Limpiar y añadir (código existente)
                    const existingSummary = demoCard.querySelector('.gender-summary-text');
                    if (existingSummary) existingSummary.remove();
                    if (labels.length > 0) { // Solo añadir si hay datos
                        demoCard.insertAdjacentHTML('beforeend', summaryTextHTML);
                    }
                }

            } else {
                showChartLoadingPlaceholder('insightsDemographicsChart', 'No hay datos demográficos detallados.');
                // Limpiar texto si existía
                if (demoCard) {
                    const existingSummary = demoCard.querySelector('.gender-summary-text');
                    if (existingSummary) existingSummary.remove();
                }
            }

            // Renderizar lista de ubicaciones
            const locationList = document.getElementById('insightsLocationList');
            locationList.innerHTML = ''; // Limpiar
            if (result.data.country && result.data.country.length > 0) {
                result.data.country.slice(0, 5).forEach(item => { // Mostrar top 5
                    const listItem = document.createElement('div');
                    listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                    listItem.innerHTML = `<span>${item.pais}</span><span class="badge bg-primary rounded-pill">${item.count_unique_users}</span>`;
                    locationList.appendChild(listItem);
                });
            } else {
                locationList.innerHTML = '<p class="text-muted text-center small p-2">No hay datos de ubicación.</p>';
            }
        } else {
            console.error("Error en datos de demografía:", result.message);
            showChartLoadingPlaceholder('insightsDemographicsChart', result.message || 'Error al cargar.');
            showListLoadingPlaceholder('insightsLocationList', result.message || 'Error al cargar.');
        }
    } catch (error) {
        console.error("Fetch error en Demographics:", error);
        showChartLoadingPlaceholder('insightsDemographicsChart', 'Error de conexión.');
        showListLoadingPlaceholder('insightsLocationList', 'Error de conexión.');
    }
}


async function fetchFollowerEvolutionData({ userId, period }) {
    const basePath = window.basePath || '/ProyectoBDM/';
    const endpoint = `${basePath}insights/follower-evolution?user_id=${userId}&period=${period}`;
    console.log("JS: Fetching follower evolution from:", endpoint);

    try {
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();

        if (insightsFollowersChart) { insightsFollowersChart.destroy(); insightsFollowersChart = null; }
        const followersCanvas = document.getElementById('insightsFollowersChart');

        if (result.success && result.data && result.data.labels && result.data.data) {
            insightsFollowersChart = new Chart(followersCanvas, {
                type: 'line',
                data: {
                    labels: result.data.labels, // Fechas
                    datasets: [{
                        label: 'Total Seguidores',
                        data: result.data.data, // Conteos
                        borderColor: '#007bff',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: false } } }
            });
        } else {
            console.error("Error en datos de evolución de seguidores:", result.message);
            showChartLoadingPlaceholder('insightsFollowersChart', result.message || 'Error al cargar.');
        }
    } catch (error) {
        console.error("Fetch error en Follower Evolution:", error);
        showChartLoadingPlaceholder('insightsFollowersChart', 'Error de conexión.');
    }
}

async function fetchTopContentData({ userId, period, contentType }) {
    const limit = 5;
    const basePath = window.basePath || '/ProyectoBDM/';
    const endpoint = `${basePath}insights/top-content?user_id=${userId}&period=${period}&contentType=${contentType}&limit=${limit}`;
    console.log("JS: Fetching top content from:", endpoint);

    const topContentList = document.getElementById('insightsTopContentList');

    try {
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();

        topContentList.innerHTML = ''; // Limpiar
        if (result.success && Array.isArray(result.data)) {
            if (result.data.length === 0) {
                topContentList.innerHTML = '<p class="text-muted text-center small p-2">No hay contenido destacado.</p>';
                return;
            }
            result.data.forEach((item, index) => {
                let previewHtml = `<div class="post-preview-placeholder bg-light rounded d-flex align-items-center justify-content-center me-2" style="width: 50px; height: 50px;"><i class="bi bi-file-earmark-text text-muted fs-4"></i></div>`;
                if (item.media_data_uri) {
                    if (item.primera_media_mime && item.primera_media_mime.startsWith('image/')) {
                        previewHtml = `<img src="${item.media_data_uri}" alt="Preview" class="rounded me-2" style="width: 50px; height: 50px; object-fit: cover;">`;
                    } else if (item.primera_media_mime && item.primera_media_mime.startsWith('video/')) {
                        previewHtml = `<div class="post-preview-placeholder bg-dark rounded d-flex align-items-center justify-content-center me-2" style="width: 50px; height: 50px;"><i class="bi bi-camera-reels-fill fs-4 text-light"></i></div>`;
                    }
                }
                const contentText = item.pub_texto || item.sht_titulo || 'Ver Contenido'; // Asume pub_texto o sht_titulo
                const contentId = item.pub_id_publicacion || item.sht_id_short;
                const linkType = item.pub_id_publicacion ? 'post' : (item.sht_id_short ? 'short' : '#');

                const listItem = document.createElement('div');
                listItem.className = 'list-group-item list-group-item-action d-flex align-items-center small';
                listItem.innerHTML = `
                    <span class="fw-bold me-2" style="min-width: 20px; text-align: right;">#${index + 1}</span>
                    ${previewHtml}
                    <div class="flex-grow-1 text-truncate">
                        <a href="${basePath}${linkType}/${contentId}" target="_blank" class="text-decoration-none text-dark stretched-link">${contentText.substring(0,60)}${contentText.length > 60 ? '...' : ''}</a>
                        <div class="text-muted small mt-1">
                            <i class="bi bi-hand-thumbs-up-fill"></i> ${item.like_count || 0}
                            <i class="bi bi-chat-fill ms-2"></i> ${item.comment_count || 0}
                        </div>
                    </div>
                `;
                topContentList.appendChild(listItem);
            });
        } else {
            console.error("Error en datos de top contenido:", result.message);
            showListLoadingPlaceholder('insightsTopContentList', result.message || 'Error al cargar.');
        }
    } catch (error) {
        console.error("Fetch error en Top Content:", error);
        showListLoadingPlaceholder('insightsTopContentList', 'Error de conexión.');
    }
}

async function fetchHourlyActivityData({ userId, period, contentType }) {
    const basePath = window.basePath || '/ProyectoBDM/';
    // El SP sp_get_hourly_activity_insights diseñado no toma contentType,
    // si se necesita, el SP y el backend deben adaptarse.
    const endpoint = `${basePath}insights/hourly-activity?user_id=${userId}&period=${period}&contentType=${contentType}`;
    console.log("JS: Fetching hourly activity from:", endpoint);

    try {
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();

        if (insightsActivityChart) { insightsActivityChart.destroy(); insightsActivityChart = null; }
        const activityCanvas = document.getElementById('insightsActivityChart');

        if (result.success && Array.isArray(result.data)) {
            // result.data ya es el array de 24 conteos
            insightsActivityChart = new Chart(activityCanvas, {
                type: 'bar',
                data: {
                    labels: Array.from({ length: 24 }, (_, i) => `${String(i).padStart(2, '0')}:00`),
                    datasets: [{
                        label: 'Interacciones',
                        data: result.data,
                        backgroundColor: '#28a745',
                        borderColor: '#208335',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } // Asegura que el eje Y muestre enteros
                }
            });
        } else {
            console.error("Error en datos de actividad horaria:", result.message);
            showChartLoadingPlaceholder('insightsActivityChart', result.message || 'Error al cargar.');
        }
    } catch (error) {
        console.error("Fetch error en Hourly Activity:", error);
        showChartLoadingPlaceholder('insightsActivityChart', 'Error de conexión.');
    }
}

async function fetchInteractionTypesData({ userId, period, contentType }) {
    const basePath = window.basePath || '/ProyectoBDM/';
    const endpoint = `${basePath}insights/interaction-types?user_id=${userId}&period=${period}&contentType=${contentType}`;
    console.log("JS: Fetching interaction types from:", endpoint); // Ya tienes esto

    try {
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const result = await response.json();

        // DEBUGGING: VER QUÉ DEVUELVE EL BACKEND
        console.log("Interaction Types - Raw Backend Result:", JSON.stringify(result));

        if (insightsInteractionsPieChart) { insightsInteractionsPieChart.destroy(); insightsInteractionsPieChart = null; }
        const interactionsCanvas = document.getElementById('insightsInteractionsPieChart');

        if (result.success && result.data) {
            // Asegúrate de que las claves coincidan con lo que devuelve el SP y el Modelo
            // El SP devuelve 'total_likes' y 'total_comments'
            const likes = parseInt(result.data.total_likes) || 0;
            const comments = parseInt(result.data.total_comments) || 0;
            const dataValues = [likes, comments];
            const dataLabels = ['Me Gusta', 'Comentarios'];
            const backgroundColors = ['#007bff', '#ffc107'];

            // DEBUGGING: VER QUÉ VALORES SE USARÁN PARA LA GRÁFICA
            console.log("Interaction Types - Processed Data for Chart:", { labels: dataLabels, values: dataValues });

            if (dataValues.every(v => v === 0)) {
                console.log("Interaction Types: Todos los valores son cero, mostrando 'No hay datos'.");
                showChartLoadingPlaceholder('insightsInteractionsPieChart', 'No hay datos de interacción.');
                return;
            }

            // Si llegamos aquí, al menos un valor no es cero, se debe dibujar la gráfica.
            insightsInteractionsPieChart = new Chart(interactionsCanvas, {
                type: 'pie',
                data: {
                    labels: dataLabels,
                    datasets: [{
                        label: 'Tipos de Interacción',
                        data: dataValues,
                        backgroundColor: backgroundColors,
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        } else {
            console.error("Error en datos de tipos de interacción desde backend:", result.message || "result.data no es válido o success es false");
            showChartLoadingPlaceholder('insightsInteractionsPieChart', result.message || 'Error al cargar datos.');
        }
    } catch (error) { // Error en fetch o response.json()
        console.error("Fetch error en Interaction Types:", error);
        showChartLoadingPlaceholder('insightsInteractionsPieChart', 'Error de conexión o respuesta no JSON.');
    }
}


// --- Inicialización ---
// Llama a esta función una vez que el DOM esté listo si quieres que el modal
// se cree inmediatamente, o llámala cuando sea necesario.
document.addEventListener('DOMContentLoaded', function() {
    createAndSetupInsightsModal();

    // Añadir listener al botón que abre el modal (ejemplo)
    // Necesitas un botón en tu HTML con id="openInsightsModalBtn" y data-user-id="EL_ID_DEL_USUARIO"
    // O si el botón ya existe y tiene data-bs-toggle="modal" data-bs-target="#starnestInsightsModal"
    // entonces el listener 'shown.bs.modal' dentro de createAndSetupInsightsModal se encargará.
    /*
    const openModalButton = document.getElementById('openInsightsModalBtn');
    if (openModalButton) {
        openModalButton.addEventListener('click', function() {
            const userIdForInsights = this.dataset.userId; // Obtener el userId del botón
            const modalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('starnestInsightsModal'));
            
            // Pasar el userId al modal antes de mostrarlo, si es necesario (o usar el listener 'shown.bs.modal')
            // document.getElementById('starnestInsightsModal').dataset.userIdTarget = userIdForInsights; // Ejemplo
            
            modalInstance.show();
        });
    }
    */
});