<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StarNest - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/login.css">
</head>
<body>

    <a href="<?php echo htmlspecialchars($base_path); ?>registro" class="create-account-btn d-none d-md-block">Crear cuenta</a>

    <nav class="mobile-nav d-md-none">
        <div class="brand-text-mobile" style="color: var(--main-color); font-weight: 600;">StarNest</div>
        <a href="<?php echo htmlspecialchars($base_path); ?>registro" class="btn btn-sm" style="background: var(--main-color); color: white;">Crear cuenta</a>
    </nav>

    <div class="sky">
        <div class="cloud"></div>
        <div class="cloud"></div>
        <div class="cloud"></div>
        <div class="cloud"></div>
        <div class="cloud"></div>
        <div class="cloud"></div>
    </div>

    <div class="login-container">
        <div class="brand-text">StarNest</div>
        
        <form method="POST" action="<?php echo htmlspecialchars($base_path); ?>login">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required>
            </div>

            <div class="mb-4">
                <input type="password" name="contrasena" class="form-control" placeholder="Contraseña" required>
            </div>

            <button type="submit" id="loginbtn" class="btn btn-custom">LOG IN</button>

            <a href="#" class="forgot-password">¿Olvidaste tu contraseña?</a>
        </form>
        
    </div>

    
   

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>/Views/js/login.js"></script>
    
</body>
</html>