<?php

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    global $base_path;
    if(!isset($base_path)) $base_path = '/ProyectoBDM/';
    header('Location: ' . $base_path . 'login');
    exit();
}



$username = $userData['usr_username'] ?? 'Usuario';
$nombreCompleto = ($userData['usr_nombre'] ?? '') . ' ' . ($userData['usr_apellido_paterno'] ?? '');
$nombre = $userData['usr_nombre'] ?? 'Nombre'; 
$apellidoPaterno = $userData['usr_apellido_paterno'] ?? 'Apellido1';
$apellidoMaterno = $userData['usr_apellido_materno'] ?? 'Apellido2';
$privacidad = $userData['usr_privacidad'] ?? 'Undefined';
$telefono = $userData['usr_telefono'] ?? '00-00-00-00-00';
$genero = $userData['usr_genero'] ?? 'Undefined';
$provincia = $userData['usr_provincia'] ?? 'Undefined';
$fechaNacimiento = $userData['usr_fecha_nacimiento'] ?? null;
$profilePicData = $userData['usr_foto_perfil'] ?? null;
$profilePicMime = $userData['usr_foto_perfil_mime'] ?? null; 
$coverPicData = $userData['usr_foto_portada'] ?? null;
$coverPicMime = $userData['usr_foto_portada_mime'] ?? null;
$userId = $userData['usr_id'] ?? null;
$biografia = $userData['usr_biografia'] ?? 'Aún no has añadido una biografía.';
$fechaAlta = $userData['usr_fecha_alta'] ?? null;
$ciudad = $userData['usr_ciudad'] ?? null;
$pais = $userData['usr_pais'] ?? null;
$edad = null;
if (!empty($userData['usr_fecha_nacimiento'])) {
    try {
        $fechaNac = new DateTime($userData['usr_fecha_nacimiento']);
        $hoy = new DateTime();
        $edad = $hoy->diff($fechaNac)->y;
    } catch (Exception $e) { $edad = null; } 
}


$profilePicSrc = null;
if ($profilePicData && $profilePicMime) {
    $profilePicSrc = 'data:' . htmlspecialchars($profilePicMime) . ';base64,' . base64_encode($profilePicData);
} else {
  
    $profilePicSrc = htmlspecialchars($base_path) . 'Views/pictures/defaultpfp.jpg';
}

$coverPicSrc = null;
if ($coverPicData && $coverPicMime) {
    $coverPicSrc = 'data:' . htmlspecialchars($coverPicMime) . ';base64,' . base64_encode($coverPicData);
} else {
     
    $coverPicSrc = htmlspecialchars($base_path) . 'Views/pictures/defaultcover.jpg';
}



?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path)?>Views/css/main.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path)?>Views/css/marketplace.css">
    
</head>
<body>
    
    <div id="navbar-container">
        
    </div>

<div class="container-fluid px-5" style="margin-top: 80px; min-height: calc(100vh - 80px);">
    <div class="row">
       
        <div class="col-md-3 border-end p-4" style="height: calc(100vh - 80px); overflow-y: auto;">
       
            <button id="createProductListingBtn" class="btn rounded-pill btn-custom w-100 mb-4 py-2">
                <i class="bi bi-plus-lg me-2"></i>Crear Publicación
            </button>

           
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-action border-0">
                    <i class="bi bi-house-door me-2"></i>Todas las categorías
                </a>
                <a href="#" class="list-group-item list-group-item-action border-0">
                    <i class="bi bi-phone me-2"></i>Electrónica
                </a>
                <a href="#" class="list-group-item list-group-item-action border-0">
                    <i class="bi bi-shop me-2"></i>Negocios Locales
                </a>
                <a href="#" class="list-group-item list-group-item-action border-0">
                    <i class="bi bi-car-front me-2"></i>Vehículos
                </a>
                <a href="#" class="list-group-item list-group-item-action border-0">
                    <i class="bi bi-house-heart me-2"></i>Hogar
                </a>
                <a href="#" class="list-group-item list-group-item-action border-0">
                    <i class="bi bi-bag me-2"></i>Moda
                </a>
            </div>
        </div>

        
        <div class="col-md-9 p-4">
            <div class="row">
                
                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <a href="./product.html"><img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb2.jpg" class="card-img-top object-fit-cover" style="height: 200px;"></a>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">iPhone 15 Pro Max</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$1,299.99</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Electrónica</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb1.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb3.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb2.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb3.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb1.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb2.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb3.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb3.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb1.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb2.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-2 mb-3">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($base_path);?>views/pictures/thumb3.jpg" class="card-img-top object-fit-cover" style="height: 200px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">Sofá Chesterfield</h5>
                                <i class="bi bi-tag"></i>
                            </div>
                            <p class=" fw-bold mt-2">$850.00</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>Hogar</small>
                                <button class="btn btn-sm btn-custom">Ver Detalles</button>
                            </div>
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
<script>
        console.log("Definiendo currentUserData...");
        window.currentUserData = {
            userId: <?php echo json_encode($userId); ?>,
            username: <?php echo json_encode($username); ?>,
            nombreCompleto: <?php echo json_encode($nombreCompleto); ?>,
            nombre: <?php echo json_encode($nombre); ?>,
            apellidoPaterno: <?php echo json_encode($apellidoPaterno); ?>,
            apellidoMaterno: <?php echo json_encode($apellidoMaterno); ?>,
            biografia: <?php echo json_encode($biografia === 'Aún no has añadido una biografía.' ? '' : $biografia); ?>, 
            profilePicSrc: <?php echo json_encode($profilePicSrc); ?>, 
            coverPicSrc: <?php echo json_encode($coverPicSrc); ?>,     
            privacidad: <?php echo json_encode($privacidad); ?>,
            telefono: <?php echo json_encode($telefono); ?>,
            genero: <?php echo json_encode($genero); ?>,
            ciudad: <?php echo json_encode($ciudad); ?>,
            provincia: <?php echo json_encode($provincia); ?>,
            pais: <?php echo json_encode($pais); ?>,
            fechaNacimiento: <?php echo json_encode($fechaNacimiento); ?> 
        };
        window.basePath = <?php echo json_encode($base_path); ?>;
        console.log("currentUserData definido:", window.currentUserData);
    </script>
<script src="<?php echo htmlspecialchars($base_path)?>Views/js/validation.js"></script>
<script src="<?php echo htmlspecialchars($base_path);?>Views/js/modal.js"></script>
<script src="<?php echo htmlspecialchars($base_path);?>Views/js/main.js"></script>
</body>
</html>