function toggleTheme() {
    document.body.classList.toggle('dark-theme');
    const icon = document.getElementById('theme-icon');
    const isDark = document.body.classList.contains('dark-theme');
    
    icon.className = isDark ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

function loadTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const icon = document.getElementById('theme-icon');
    
    if(savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        icon.className = 'bi bi-moon-fill';
    } else {
        icon.className = 'bi bi-sun-fill';
    }
}


document.addEventListener("DOMContentLoaded", function() {

    loadTheme();
    
    document.getElementById("navbar-container").innerHTML = `
        <nav class="navbar navbar-expand-lg nav-custom fixed-top bg-custom">
            <div class="container-fluid">
               
                <a class="navbar-brand logo fw-bold ms-2" href="./feed.html"><span><i class="bi bi-stars"></i></span> StarNest</a>
                
                <div class="d-flex flex-grow-1 mx-4">
                    <div class="input-group w-100">
                        <input type="search" class="form-control border" placeholder="Buscar...">
                        <button class="btn btn-custom border" id="btnsearch" type="button">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
        
                <div class="d-flex align-items-center">
                    <div class="nav-icons">
                       
                        <a href="./chat.html" class="text-dark mx-3 position-relative" title="Chat">
                            <i class="bi bi-chat-dots fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                3
                            </span>
                        </a>
                        
                        <a href="#" class="text-dark mx-3 position-relative" title="Notificaciones"
                        data-bs-toggle="modal" data-bs-target="#notificationsModal">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                5
                            </span>
                        </a>
                        
                        <a href="./communities.html" class="text-dark mx-3" title="Comunidades">
                            <i class="bi bi-people fs-5"></i>
                        </a>
                        
                        <a href="./userprofile.html" class="text-dark mx-3" title="Perfil">
                            <i class="bi bi-person-circle fs-5"></i>
                        </a>
                        
                        <a href="./marketplace.html" class="text-dark mx-3" title="Mercado">
                            <i class="bi bi-cart3 fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    `;

    const searchButton = document.getElementById("btnsearch");

    searchButton.addEventListener("click", function () {
        
            window.location.href = `search.html`;
       
    });
});