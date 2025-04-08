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
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/userprofile.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/main.css">
    
</head>
<body>
    
    <div id="navbar-container">
        
    </div>
    
    <div class="container-fluid px-5">
      
        <div class="row mb-4">
            <div class="col-12">
                <div class="card mt-3">
                    <div style="height: 325px">
                        <img src="<?php echo htmlspecialchars($coverPicSrc);?>" class="profile-cover" id="currentCover">
                    </div>
                    <img src="<?php echo htmlspecialchars($profilePicSrc);?>" class="mt-5 profile-img rounded-circle" id="currentAvatar">
                    <div class="card-body text-center pt-5">
                        <h2 class="card-title mt-5 pt-3"><?php echo htmlspecialchars($nombreCompleto)?></h2>
                        <div class="d-flex justify-content-around my-4">
                            <div>
                                <h5>1.2k</h5>
                                <span >Publicaciones</span>
                            </div>
                            <div>
                                <h5>15.8k</h5>
                                <span >Seguidores</span>
                            </div>
                            <div>
                                <h5>856</h5>
                                <span >Siguiendo</span>
                            </div>
                        </div>
                        <button class="btn btn-custom rounded-pill mb-2"><span><i class="bi bi-person-fill-add"></i></span> Seguir</button>
                        <button class="btn btn-custom rounded-pill mb-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#settingsModal">
                                <i class="bi bi-pencil-fill"></i> Editar Perfil    
                        </button>
                        <button class="btn btn-custom rounded-pill mb-2"
                                data-bs-toggle="modal"
                                data-bs-target="#changePasswordModal">
                            <i class="bi bi-key-fill"></i>
                        </button>

                        <button onclick="showInsightsModal()" class="btn btn-custom rounded-pill mb-2">
                            <i class="bi bi-graph-up"></i> Ver Insights
                        </button>
                    </div>
                </div>
            </div>
        </div>
    
       

            <div class="row">

                <div class="col-md-3 ">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Biografía</h5>
                            <p class="card-text"><?php echo htmlspecialchars($biografia);?></p>
                            <ul class="list-unstyled">
                                <li><small>Se unió en: <?php echo htmlspecialchars($fechaAlta);?></small></li>
                                <li><small>País: México</small></li>
                                <li><small>Edad: <?php echo htmlspecialchars($edad);?></small></li>
                                <li><a href="#" class="text-decoration-none">miweb.com</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Amigos <span class="badge">258</span></h5>
                            <ul class="list-group list-group-flush">
                               
                                <li class="list-group-item d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($base_path)?>Views/pictures/fuyu.jpg" class="rounded-circle me-2" width="40" height="40">
                                    <span class="flex-grow-1">Fuyusito</span>
                                    <button class="btn btn-sm btn-custom rounded-pill">Añadir</button>
                                </li>
                               
                            </ul>
                        </div>
                    </div>
                </div>
        
                <div class="col-md-6">
                   
                    <div class="card mb-3">
                        <div class="card-body">

                            <div class="container-fluid pt-2">
                                <div class="row  p-2">
                                    <div class="col-1">
                                        <img src="<?php echo htmlspecialchars($profilePicSrc)?>" class="rounded-circle" style="width: 50px;" alt="profile picture">
                                    </div>
                                    <div class="col-10">
                                        <h4 class="mt-2 ms-0"><?php echo htmlspecialchars($nombreCompleto)?></h4>
                                    </div>
                                </div>
                                <div class="row">
                                    <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis 
                                        praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias 
                                        excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui 
                                        officia deserunt mollitia animi, id est laborum et dolorum fuga.</p>
                                    <img src="<?php echo htmlspecialchars($base_path)?>/pictures/img.jpg" alt="">
                                </div>
                                <div class="d-flex justify-content-between mt-3">
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
        
                
                <div class="col-md-3">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Fotos <span class="badge bg-primary">45</span></h5>
                            <div class="row g-1">
                                
                               
                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($base_path)?>Views/pictures/img.jpg" class="img-preview rounded">
                                </div>

                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($base_path)?>Views/pictures/img2.jpg" class="img-preview rounded">
                                </div>

                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($base_path)?>Views/pictures/img3.jpg" class="img-preview rounded">
                                </div>

                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($base_path)?>Views/pictures/img4.jpg" class="img-preview rounded">
                                </div>

                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($base_path)?>Views/pictures/img5.jpg" class="img-preview rounded">
                                </div>

                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($base_path)?>Views/pictures/img6.jpg" class="img-preview rounded">
                                </div>

                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($base_path)?>Views/pictures/miku.jpg" class="img-preview rounded">
                                </div>

                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($base_path)?>Views/pictures/cat.jpg" class="img-preview rounded">
                                </div>

                                <div class="col-4">
                                    <img src="<?php echo htmlspecialchars($base_path)?>Views/pictures/meme icon.jpg" class="img-preview rounded">
                                </div>
                            
                            </div>
                            
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            
                        </div>
                    </div>
                </div>

            </div>
       
    </div>

    <div class="theme-toggle" onclick="toggleTheme()">
        <i class="bi" id="theme-icon"></i>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo htmlspecialchars($base_path)?>Views/js/validation.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/modal.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/main.js"></script>
</body>
</html>