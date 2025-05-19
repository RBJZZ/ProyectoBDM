/**
 * insights_handler.js
 * Manejo del modal de Insights de la Cuenta y carga de datos.
 */

// Variables globales para las instancias de las gráficas de Chart.js
let demographicsChartInstance = null; // Para pestaña Audiencia
let followersChartInstance = null;    // Para pestaña Rendimiento
let activityChartInstance = null;     // Para pestaña Interacciones (Horario)
let interactionsPieChartInstance = null; // Para pestaña Interacciones (Tipos)

// ID del usuario para el que se están mostrando los insights.
// Se establece cuando el modal se abre.
let currentInsightsTargetUserId = null;

document.addEventListener('DOMContentLoaded', function() {
    const insightsModalElement = document.getElementById('insightsModal');

    if (insightsModalElement) {
        insightsModalElement.addEventListener('shown.bs.modal', function (event) {
            const triggerButton = event.relatedTarget; // Botón que disparó el modal
            // Obtener el user_id del botón o de currentUserData si es para el propio perfil
            // En UserProfile.php, el botón "Ver Insights" no tiene data-user-id, así que usará el del perfil actual.
            currentInsightsTargetUserId = triggerButton?.dataset.userId || window.currentUserData?.userId || window.currentUserData?.loggedInUserId;

            if (!currentInsightsTargetUserId) {
                console.error("Insights Handler: No se pudo determinar el targetUserId para los insights.");
                // Podrías mostrar un mensaje de error en el modal aquí
                const insightsBody = insightsModalElement.querySelector('.modal-body');
                if (insightsBody) insightsBody.innerHTML = '<p class="text-danger p-3">Error: No se pudo identificar el usuario para mostrar insights.</p>';
                return;
            }
            console.log("Insights Handler: Mostrando insights para User ID:", currentInsightsTargetUserId);

            // Configurar listeners de filtros (solo una vez o verificar si ya existen)
            // Es mejor configurarlos una vez y que estén listos.
            // Pero si el modal se re-usa para diferentes usuarios, los datos deben recargarse.
            if (!insightsModalElement.dataset.filterListenersAttached) {
                 setupInsightsFilterListeners(insightsModalElement, currentInsightsTargetUserId);
                 insightsModalElement.dataset.filterListenersAttached = 'true';
            }
            
            // Cargar datos con el periodo por defecto al abrir el modal
            const defaultPeriodButton = insightsModalElement.querySelector('.insight-filters .btn-group .btn.active') || insightsModalElement.querySelector('.insight-filters .btn-group .btn');
            const defaultPeriod = defaultPeriodButton ? defaultPeriodButton.dataset.period : '30days';
            console.log("JS: Periodo por defecto para fetch:", defaultPeriod);
            if(defaultPeriodButton && !defaultPeriodButton.classList.contains('active')){
                defaultPeriodButton.click(); // Simula clic para activar y cargar datos
            } else {
                 fetchAllInsightsData(currentInsightsTargetUserId, defaultPeriod);
            }
        });

        insightsModalElement.addEventListener('hidden.bs.modal', function () {
            destroyAllInsightCharts();
            currentInsightsTargetUserId = null; // Resetear
            // Limpiar contenido dinámico si es necesario
            const topPostsContainer = document.querySelector('#insightsModal #performance .top-posts');
            if(topPostsContainer) topPostsContainer.innerHTML = '<small class="text-muted p-2">Cargando...</small>';
            // Podrías querer limpiar los canvas también
        });
    }
});

function setupInsightsFilterListeners(modalElement, targetUserId) {
    const periodButtons = modalElement.querySelectorAll('.insight-filters .btn-group .btn');
    periodButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            periodButtons.forEach(btn => btn.classList.remove('active', 'main-theme-bg-accent', 'text-white')); // Quita estilos de todos
            this.classList.add('active', 'main-theme-bg-accent', 'text-white'); // Añade al clickeado

            const selectedPeriod = this.dataset.period;
            console.log("JS: Periodo seleccionado para fetch:", selectedPeriod);
            if (selectedPeriod === 'custom') {
                console.log("Periodo personalizado - Implementación de date pickers pendiente.");
                // Aquí deberías mostrar los selectores de fecha y luego llamar a fetchAllInsightsData
                // con start_date y end_date como parámetros GET adicionales.
                return;
            }
            if (targetUserId) {
                fetchAllInsightsData(targetUserId, selectedPeriod);
            }
        });
    });

    const contentTypeSelect = modalElement.querySelector('.insight-filters select');
    if (contentTypeSelect) {
        contentTypeSelect.addEventListener('change', function(event) {
            const selectedContentType = this.value;
            const currentPeriod = modalElement.querySelector('.insight-filters .btn-group .btn.active')?.dataset.period || '30days';
            if (targetUserId) {
                // Asume que fetchAllInsightsData puede tomar contentType
                fetchAllInsightsData(targetUserId, currentPeriod, selectedContentType);
            }
        });
    }
}

function fetchAllInsightsData(targetUserId, period, contentType = 'Publicaciones') { // contentType default de tu HTML
    if (!targetUserId) {
        console.error("fetchAllInsightsData: targetUserId no definido.");
        return;
    }
    console.log(`Workspaceing all insights for User ID: ${targetUserId}, Period: ${period}, ContentType: ${contentType}`);

    // Limpiar/Mostrar 'cargando' en las áreas de gráficas
    showLoadingInCharts();

    loadInteractionTypes(targetUserId, period);
    loadHourlyActivity(targetUserId, period);
    loadTopPosts(targetUserId, period, contentType); // 'contentType' es 'Publicaciones' por defecto
    // loadFollowerEvolution(targetUserId, period); // Implementar después
    // loadDemographics(targetUserId, period);      // Implementar después
}

function showLoadingInCharts(){
    // Opcional: mostrar un spinner o mensaje en cada canvas mientras cargan los datos
    const chartCanvases = ['interactionsChart', 'activityChart' /*, 'demographicsChart', 'followersChart'*/];
    chartCanvases.forEach(canvasId => {
        const canvas = document.getElementById(canvasId);
        if (canvas && canvas.getContext) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height); // Limpiar canvas
            ctx.font = "16px Arial";
            ctx.fillStyle = "#888";
            ctx.textAlign = "center";
            ctx.fillText("Cargando datos...", canvas.width / 2, canvas.height / 2);
        }
    });
     const topPostsContainer = document.querySelector('#insightsModal #performance .top-posts');
     if(topPostsContainer) topPostsContainer.innerHTML = '<small class="text-muted p-2">Cargando top publicaciones...</small>';
}

function destroyAllInsightCharts() {
    if (demographicsChartInstance) { demographicsChartInstance.destroy(); demographicsChartInstance = null; }
    if (followersChartInstance) { followersChartInstance.destroy(); followersChartInstance = null; }
    if (activityChartInstance) { activityChartInstance.destroy(); activityChartInstance = null; }
    if (interactionsPieChartInstance) { interactionsPieChartInstance.destroy(); interactionsPieChartInstance = null; }
    console.log("Todas las gráficas de insights destruidas (o intentado).");
}

// --- Funciones específicas para cargar y renderizar cada insight ---

async function loadInteractionTypes(targetUserId, period) {
    const chartCanvas = document.getElementById('interactionsChart');
    if (!chartCanvas) {
        console.error("Canvas 'interactionsChart' no encontrado.");
        return;
    }
    // Limpiar canvas antes de la llamada fetch si quieres mostrar "cargando"
    const ctxPre = chartCanvas.getContext('2d');
    ctxPre.clearRect(0,0,chartCanvas.width, chartCanvas.height);
    ctxPre.fillText("Cargando...", chartCanvas.width/2, chartCanvas.height/2);


    const basePath = window.basePath || '/ProyectoBDM/'; 
    const endpoint = `${basePath}insights/interaction-types?user_id=${targetUserId}&period=${period}`;
    try {
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error(`HTTP ${response.status} - ${response.statusText}`);
        const result = await response.json();

        if (interactionsPieChartInstance) { // Destruye la instancia ANTERIOR
            interactionsPieChartInstance.destroy();
            interactionsPieChartInstance = null; // ¡IMPORTANTE: Poner a null!
        }

        if (result.success && result.data) {
            const { total_likes, total_comments } = result.data;
            const chartDataValues = [total_likes || 0, total_comments || 0];
            const chartLabels = ['Me gusta', 'Comentarios'];
            const chartBackgroundColors = ['#4e79a7', '#f28e2c'];
            // ... (lógica para shares si la tienes) ...

            interactionsPieChartInstance = new Chart(chartCanvas, { // Crea la NUEVA instancia
                type: 'pie',
                data: { labels: chartLabels, datasets: [{ data: chartDataValues, backgroundColor: chartBackgroundColors }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
            });
        } else {
            console.error("Error al cargar datos de tipos de interacción:", result.message);
            if(chartCanvas.getContext) { const ctx = chartCanvas.getContext('2d'); ctx.clearRect(0,0,chartCanvas.width, chartCanvas.height); ctx.fillText(result.message || "No hay datos.", chartCanvas.width/2, chartCanvas.height/2); }
        }
    } catch (error) {
        console.error("Fetch error en loadInteractionTypes:", error);
        if(chartCanvas.getContext) { const ctx = chartCanvas.getContext('2d'); ctx.clearRect(0,0,chartCanvas.width, chartCanvas.height); ctx.fillText("Error al cargar.", chartCanvas.width/2, chartCanvas.height/2); }
    }
}

async function loadHourlyActivity(targetUserId, period) {
    const chartCanvas = document.getElementById('activityChart');
    if (!chartCanvas) {
        console.error("Canvas 'activityChart' no encontrado.");
        return;
    }
    // Limpiar canvas
    const ctxPre = chartCanvas.getContext('2d');
    ctxPre.clearRect(0,0,chartCanvas.width, chartCanvas.height);
    ctxPre.fillText("Cargando...", chartCanvas.width/2, chartCanvas.height/2);

    const basePath = window.basePath || '/ProyectoBDM/';
    const endpoint = `${basePath}insights/hourly-activity?user_id=${targetUserId}&period=${period}`;
    try {
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error(`HTTP ${response.status} - ${response.statusText}`);
        const result = await response.json();

        if (activityChartInstance) { // Destruye la instancia ANTERIOR
            activityChartInstance.destroy();
            activityChartInstance = null; // ¡IMPORTANTE: Poner a null!
        }

        if (result.success && Array.isArray(result.data)) {
            const hourlyCounts = result.data;
            activityChartInstance = new Chart(chartCanvas, { // Crea la NUEVA instancia
                type: 'bar',
                data: {
                    labels: Array.from({length: 24}, (_, i) => `${i}`), // Etiquetas más cortas para horas
                    datasets: [{ label: 'Interacciones', data: hourlyCounts, backgroundColor: '#59a14f' }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, suggestedMax: Math.max(0, ...hourlyCounts) + 5 }, // Evitar error si hourlyCounts está vacío
                        x: { ticks: { callback: function(val, index) { return index % 2 === 0 ? this.getLabelForValue(val) : ''; } } } // Mostrar solo algunas etiquetas x
                    }
                }
            });
        } else {
            console.error("Error al cargar datos de actividad horaria:", result.message);
             if(chartCanvas.getContext) { const ctx = chartCanvas.getContext('2d'); ctx.clearRect(0,0,chartCanvas.width, chartCanvas.height); ctx.fillText(result.message || "No hay datos.", chartCanvas.width/2, chartCanvas.height/2); }
        }
    } catch (error) {
        console.error("Fetch error en loadHourlyActivity:", error);
        if(chartCanvas.getContext) { const ctx = chartCanvas.getContext('2d'); ctx.clearRect(0,0,chartCanvas.width, chartCanvas.height); ctx.fillText("Error al cargar.", chartCanvas.width/2, chartCanvas.height/2); }
    }
}

async function loadTopPosts(targetUserId, period, contentType = 'Publicaciones', limit = 5) {
    const topPostsContainer = document.querySelector('#insightsModal #performance .top-posts');
    if (!topPostsContainer) return;
    
    const basePath = window.basePath || '/ProyectoBDM/';
    const endpoint = `${basePath}insights/top-posts?user_id=${targetUserId}&period=${period}&limit=${limit}&contentType=${contentType}`;
    topPostsContainer.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-secondary" role="status"><span class="visually-hidden">Cargando...</span></div> <small class="text-muted">Cargando...</small></div>';

    try {
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error(`HTTP ${response.status} - ${response.statusText}`);
        const result = await response.json();

        topPostsContainer.innerHTML = ''; // Limpiar
        if (result.success && Array.isArray(result.data)) {
            if (result.data.length === 0) {
                topPostsContainer.innerHTML = '<p class="text-muted small p-2 text-center">No hay publicaciones destacadas en este periodo.</p>';
                return;
            }
            result.data.forEach((post, index) => {
                let postPreviewHtml = `<div class="post-preview-placeholder bg-light rounded d-flex align-items-center justify-content-center"><i class="bi bi-image text-muted fs-4"></i></div>`; // Default
                if (post.media_data_uri) { // Usamos la data URI procesada por el controlador
                    if (post.first_media_mime && post.first_media_mime.startsWith('image/')) {
                        postPreviewHtml = `<img src="${post.media_data_uri}" alt="Preview" class="post-preview-img rounded">`;
                    } else if (post.first_media_mime && post.first_media_mime.startsWith('video/')) {
                        postPreviewHtml = `<div class="post-preview-placeholder bg-dark rounded d-flex align-items-center justify-content-center"><i class="bi bi-camera-reels-fill fs-3 text-light"></i></div>`;
                    }
                }

                const postElement = document.createElement('div');
                postElement.className = 'post-item d-flex align-items-center mb-2 p-2 border-bottom'; // Estilo de item
                postElement.innerHTML = `
                    <small class="text-muted fw-bold me-2" style="min-width:25px; text-align:right;">#${index + 1}</small>
                    <div class="post-preview me-2">${postPreviewHtml}</div>
                    <div class="post-info flex-grow-1 text-truncate small">
                        <a href="${window.basePath || ''}post/${post.pub_id_publicacion}" target="_blank" class="text-decoration-none text-body d-block text-truncate" title="${post.pub_texto || 'Ver publicación'}">
                            ${post.pub_texto ? (post.pub_texto.substring(0, 40) + (post.pub_texto.length > 40 ? '...' : '')) : 'Ver Publicación'}
                        </a>
                        <div class="post-stats text-muted mt-1">
                            <small><i class="bi bi-hand-thumbs-up"></i> ${post.like_count || 0}</small>
                            <small class="ms-2"><i class="bi bi-chat-dots"></i> ${post.comment_count || 0}</small>
                        </div>
                    </div>
                `;
                topPostsContainer.appendChild(postElement);
            });
        } else {
            console.error("Error al cargar datos de top posts:", result.message);
            topPostsContainer.innerHTML = '<p class="text-danger small p-2 text-center">No se pudieron cargar las publicaciones.</p>';
        }
    } catch (error) {
        console.error("Fetch error en loadTopPosts:", error);
        topPostsContainer.innerHTML = '<p class="text-danger small p-2 text-center">Error de red al cargar.</p>';
    }
}