<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Feed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/feed.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/main.css">
    
</head>
<body>
    
   <div id="navbar-container">

   </div>

    <div class="container-fluid mt-5 p-5">
        <div class="row g-3">
           
            <div class="col-md-3 left-column">
               
                <div class="card mb-3 rounded-4">
                  
                    <div class="profile-cover-container" style="height: 150px; position: relative;">
                        <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/header.jpg" class="profile-cover" 
                             style="width: 100%; height: 100%; object-fit: cover;">
                     
                        <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/pic.jpg" 
                             class="profile-img position-absolute top-100 start-50 translate-middle"
                             style="width: 90px; height: 90px; border: 3px solid white;">
                    </div>
            
                    <div class="card-body text-center pt-5">
                        <h5 class="mb-0 mt-3 pt-1">Anya Forger</h5>
                        <small class="text-muted">@forgeranya</small>
                        
                        <div class="stats-container mt-3">
                            <div class="stat-item">
                                <div class="fw-bold">245</div>
                                <small class="text-muted">Publicaciones</small>
                            </div>
                            <div class="stat-item">
                                <div class="fw-bold">1.2k</div>
                                <small class="text-muted">Seguidores</small>
                            </div>
                            <div class="stat-item">
                                <div class="fw-bold">856</div>
                                <small class="text-muted">Siguiendo</small>
                            </div>
                        </div>
                        
                        
                        <button class="btn btn-custom btn-md mt-3 w-75 rounded-pill">
                            <i class="bi bi-person-circle me-2"></i>Mi Perfil
                        </button>
                    </div>
                </div>

               
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Comunidades</h6>
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action shortcut-item">
                                <i class="bi bi-people-fill me-2"></i> Teresaposting: ratas de vecindad
                            </a>
                            <a href="#" class="list-group-item list-group-item-action shortcut-item">
                                <i class="bi bi-people-fill me-2"></i> Perritos que curan la depresión
                            </a>
                            <a href="#" class="list-group-item list-group-item-action shortcut-item">
                                <i class="bi bi-people-fill me-2"></i> British Shorthair Monterrey
                            </a>
                        </div>
                    </div>
                </div>
            </div>

           
            <div class="col-md-6 middle-column">
                
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/pic.jpg" class="rounded-circle" width="45" height="45" alt="Perfil">
                            <input type="text" class="form-control ms-3 rounded-pill feed-input" placeholder="¿Qué estás pensando?" readonly>
                        </div>
                        <div class="row align-items-center">
                            
                            <div class="p-4 pt-0 pb-0 col-md-12 d-flex align-items-center gap-2">
                                <button class="btn btn-custom btn-sm flex-shrink-0 rounded-pill">
                                    <i class="bi bi-image"></i> Foto/Video
                                </button>
                                
                                <button class="btn btn-custom btn-sm flex-shrink-0 rounded-pill">
                                    <i class="bi bi-bar-chart"></i> Encuesta
                                </button>
                            
                                <select class="form-select form-control form-select-sm flex-grow-1 rounded-pill">
                                    <option>Público</option>
                                    <option>Privado</option>
                                    <option>Solo amigos</option>
                                </select>
                            
                                <button class="btn btn-custom btn-sm flex-shrink-0 rounded-pill">
                                    Publicar
                                </button>
                            </div>
                            
                        </div>
                    </div>
                </div>

                
                <div class="post-feed">
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/miku.jpg" class="rounded-circle" width="45" height="45" alt="Perfil">
                                <div class="ms-3">
                                    <h6 class="mb-0">MikuMikuBeam</h6>
                                    <small class="text-muted">Hace 1 hora · <i class="bi bi-globe"></i> Público</small>
                                </div>
                            </div>
                            <p>¡Nuevo capítulo del manga está increíble! 🏴‍☠️</p>
                            <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/manga.webp" class="img-fluid rounded mb-3" alt="Publicación">
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-custom btn-sm">
                                    <i class="bi bi-hand-thumbs-up"></i> 245
                                </button>
                                <button class="btn btn-custom btn-sm">
                                    <i class="bi bi-chat"></i> 56 Comentarios
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/fuyu.jpg" class="rounded-circle" width="45" height="45" alt="Perfil">
                                <div class="ms-3">
                                    <h6 class="mb-0">Fuyusito</h6>
                                    <small class="text-muted">Hace 2 horas · <i class="bi bi-globe"></i> Público</small>
                                </div>
                            </div>
                            <p>Genshin está muriendo gente :(</p>
                            <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/meme.jpeg" class="img-fluid rounded mb-3" alt="Publicación">
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-custom btn-sm">
                                    <i class="bi bi-hand-thumbs-up"></i> 12849
                                </button>
                                <button class="btn btn-custom btn-sm">
                                    <i class="bi bi-chat"></i> 8847 Comentarios
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/icondog.jpg" class="rounded-circle" width="45" height="45" alt="Perfil">
                                <div class="ms-3">
                                    <h6 class="mb-0">Perritos que curan la depresión</h6>
                                    <small class="text-muted">Hace 23 horas · <i class="bi bi-globe"></i> Público</small>
                                </div>
                            </div>
                            <p>Raza, un perro atropelló mi carro y me regañaron, se cancela todo</p>
                            <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/dog.jpg" class="img-fluid rounded mb-3" alt="Publicación">
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-custom btn-sm">
                                    <i class="bi bi-hand-thumbs-up"></i> 342
                                </button>
                                <button class="btn btn-custom btn-sm">
                                    <i class="bi bi-chat"></i> 13 Comentarios
                                </button>
                            </div>
                        </div>
                    </div>
                   
                </div>
            </div>

           
            <div class="col-md-3 right-column">
               
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Amigos activos</h6>
                        <div class="list-group">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/cat.jpg" class="active-friend-img">
                                <div>
                                    <div class="fw-bold">Jhorson</div>
                                    <small class="text-muted">En línea</small>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/cat.jpg" class="active-friend-img">
                                <div>
                                    <div class="fw-bold">Jhorson</div>
                                    <small class="text-muted">En línea</small>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/cat.jpg" class="active-friend-img">
                                <div>
                                    <div class="fw-bold">Jhorson</div>
                                    <small class="text-muted">En línea</small>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/cat.jpg" class="active-friend-img">
                                <div>
                                    <div class="fw-bold">Jhorson</div>
                                    <small class="text-muted">En línea</small>
                                </div>
                            </div>
                           
                        </div>
                    </div>
                </div>

                
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Sugerencias</h6>
                        <div class="list-group">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/meme icon.jpg" class="active-friend-img">
                                    <div>
                                        <div class="fw-bold">Irisbane</div>
                                        <small class="text-muted">25 amigos en común</small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-custom rounded-pill">Seguir</button>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/meme icon.jpg" class="active-friend-img">
                                    <div>
                                        <div class="fw-bold">Irisbane</div>
                                        <small class="text-muted">25 amigos en común</small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-custom rounded-pill">Seguir</button>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/meme icon.jpg" class="active-friend-img">
                                    <div>
                                        <div class="fw-bold">Irisbane</div>
                                        <small class="text-muted">25 amigos en común</small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-custom rounded-pill">Seguir</button>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($base_path); ?>views/pictures/meme icon.jpg" class="active-friend-img">
                                    <div>
                                        <div class="fw-bold">Irisbane</div>
                                        <small class="text-muted">25 amigos en común</small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-custom rounded-pill">Seguir</button>
                            </div>

                            
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="theme-toggle" onclick="toggleTheme()">
        <i class="bi" id="theme-icon"></i>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/modal.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/main.js"></script>
</body>
</html>