// navbar.js (o donde tengas este código)

function toggleTheme() {
    document.body.classList.toggle('dark-theme');
    const icon = document.getElementById('theme-icon');
    const isDark = document.body.classList.contains('dark-theme');

    icon.className = isDark ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

function loadTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    // Asegurarse que el icono exista antes de intentar modificarlo
    const icon = document.getElementById('theme-icon');
    if (icon) {
         if(savedTheme === 'dark') {
            document.body.classList.add('dark-theme');
            icon.className = 'bi bi-moon-fill';
        } else {
            icon.className = 'bi bi-sun-fill';
        }
    } else {
         // Si no hay icono de tema (quizás en login/registro), aplicar tema al body
         if (savedTheme === 'dark') {
             document.body.classList.add('dark-theme');
         } else {
              document.body.classList.remove('dark-theme'); // Asegurar estado limpio
         }
    }
}


document.addEventListener("DOMContentLoaded", function() {

    loadTheme(); // Cargar tema primero

    const navbarContainer = document.getElementById("navbar-container");
    if (!navbarContainer) {
        console.error("Error: Contenedor #navbar-container no encontrado.");
        return; // Salir si no hay contenedor
    }

    // --- Usar window.basePath definido globalmente (asegúrate que exista) ---
    const basePath = window.basePath || '/ProyectoBDM/';
    // -----------------------------------------------------------------------

    navbarContainer.innerHTML = `
        <nav class="navbar navbar-expand-lg nav-custom fixed-top bg-custom">
            <div class="container-fluid">

                <a class="navbar-brand logo fw-bold ms-2" href="${basePath}feed"><span><i class="bi bi-stars"></i></span> StarNest</a>

                <div class="d-flex flex-grow-1 mx-4">
                    <!-- Envolver input y botón en un form para búsqueda GET -->
                    <form action="${basePath}search" method="GET" class="input-group w-100">
                        <input type="search" id="searchInputNavbar" name="query" class="form-control border" placeholder="Buscar...">
                        <button class="btn btn-custom border" id="btnsearchNavbar" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>

                <div class="d-flex align-items-center">
                    <div class="nav-icons">

                        <!-- Rutas dinámicas con basePath -->
                        <a href="${basePath}chat" class="text-dark mx-3 position-relative" title="Chat">
                            <i class="bi bi-chat-dots fs-5"></i>
                            <!-- El badge puede requerir lógica dinámica más adelante -->
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                3
                            </span>
                        </a>

                        <!-- Notificaciones sigue abriendo modal -->
                        <a href="#" class="text-dark mx-3 position-relative" title="Notificaciones"
                        data-bs-toggle="modal" data-bs-target="#notificationsModal">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                5
                            </span>
                        </a>

                         <!-- Rutas dinámicas con basePath -->
                        <a href="${basePath}communities" class="text-dark mx-3" title="Comunidades">
                            <i class="bi bi-people fs-5"></i>
                        </a>

                        <a href="${basePath}profile" class="text-dark mx-3" title="Perfil">
                            <i class="bi bi-person-circle fs-5"></i>
                        </a>

                        <a href="${basePath}marketplace" class="text-dark mx-3" title="Mercado">
                            <i class="bi bi-cart3 fs-5"></i>
                        </a>

                        <a href="${basePath}logout" class="text-dark mx-3" title="Cerrar sesión">
                            <i class="bi bi-box-arrow-right fs-5"></i>
                        </a>

                    </div>
                </div>
            </div>
        </nav>
        <!-- Botón para cambiar tema (si no lo tienes ya en otro lugar) -->
        <button id="theme-toggle-btn" class="theme-toggle" onclick="toggleTheme()">
            <i id="theme-icon" class="bi bi-sun-fill"></i>
        </button>
    `;

    // El listener para el botón de búsqueda ya no es necesario aquí,
    // porque ahora es un botón type="submit" dentro de un <form method="GET">.
    // El navegador se encargará de navegar a /search?query=lo_escrito

    // Asegúrate de que el botón de tema existe si tu HTML no lo incluye estáticamente
    const themeToggleButton = document.getElementById("theme-toggle-btn");
     if (!themeToggleButton) {
         console.warn("Botón #theme-toggle-btn no encontrado. La funcionalidad de cambio de tema podría no estar visible.");
     }

});