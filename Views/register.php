<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StarNest - Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/register.css">
  
</head>
<body>

    <a href="<?php echo htmlspecialchars($base_path); ?>login" class="signin-btn d-none d-md-block">Iniciar sesión</a>

    <nav class="mobile-nav d-md-none">
        <div class="brand-text-mobile" style="color: var(--main-color); font-weight: 600;">StarNest</div>
        <a href="<?php echo htmlspecialchars($base_path); ?>login" class="btn btn-sm" 
           style="background: var(--main-color); color: white; border-radius: 20px;">
            Iniciar sesión
        </a>
    </nav>

    <div class="stars-container" id="stars"></div>

    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="register-container">
            <h1 class="site-title">StarNest</h1>
            <h5 class="text-center fw-light mb-4">Crear nueva cuenta</h5>
            
                    <form method="POST" action="<?php echo htmlspecialchars($base_path); ?>registro">

                    <div class="form-row">
                        <div class="form-group">
                           
                            <input type="text" name="nombre" class="form-control mb-0" placeholder="Nombre" required>
                            <div class="invalid-feedback">Solo letras y espacios</div>
                        </div>
                    </div>

                    <div class="form-row mt-2">
                        <div class="form-group">
                            
                            <input type="text" name="apellidoPaterno" class="form-control mb-0" placeholder="Apellido paterno" required>
                            <div class="invalid-feedback">Solo letras y espacios</div>
                        </div>
                        <div class="form-group">
                           
                            <input type="text" name="apellidoMaterno" class="form-control mb-0" placeholder="Apellido materno">
                            <div class="invalid-feedback">Solo letras y espacios</div>
                        </div>
                    </div>

                    <div class="mb-3">
                       
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                        <div class="invalid-feedback">Solo letras, números y guiones bajos (_)</div>
                    </div>

                    <div class="mb-3">
                        
                        <input type="email" name="email" class="form-control" placeholder="E-mail" required>
                        <div class="invalid-feedback">Ingresa un correo válido</div>
                    </div>

                    <div class="mb-3">
                       
                        <input type="password" name="contrasena" class="form-control" placeholder="Contraseña" required minlength="6">
                        <div class="invalid-feedback">Mínimo 6 caracteres</div> 
                    </div>

                    <div class="mb-3">
                        
                        <input type="password" name="confirmar_contrasena" class="form-control" placeholder="Confirmar contraseña" required>
                        <div class="invalid-feedback">Las contraseñas no coinciden</div>
                    </div>

                    
                    <div class="mb-3">
                        <label for="fechaNacimiento" class="form-label visually-hidden">Fecha de Nacimiento</label> 
                        
                        <input type="date" id="fechaNacimiento" name="fechaNacimiento" class="form-control" required>
                        <div class="invalid-feedback">Ingresa tu fecha de nacimiento</div>
                    </div>
                    

                  
                    <select class="form-control custom-select mb-3" name="genero" required>
                        <option value="" disabled selected>Género</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                        <option value="Prefiero no decirlo">Prefiero no decirlo</option>
                    </select>

                    
                    <button type="submit" class="btn btn-register fw-bold">SIGN UP</button>

                    
                    <a href="<?php echo htmlspecialchars($base_path); ?>login" class="login-link">¿Ya tienes una cuenta? <span class="fw-bold">Inicia sesión</span></a>

                    </form>
        </div>
    </div>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/validation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const registerForm = document.querySelector('form[action="<?php echo htmlspecialchars($base_path); ?>registro"]');
            if (!registerForm) return;

           
            const nombreInput = registerForm.elements['nombre'];
            const apellidoPInput = registerForm.elements['apellidoPaterno'];
            const apellidoMInput = registerForm.elements['apellidoMaterno'];
            const usernameInput = registerForm.elements['username'];
            const emailInput = registerForm.elements['email'];
            const passwordInput = registerForm.elements['contrasena'];
            const confirmPasswordInput = registerForm.elements['confirmar_contrasena'];
            const fechaNacInput = registerForm.elements['fechaNacimiento']; 
            const generoSelect = registerForm.elements['genero'];

         
            const fieldsToValidate = {
                'nombre': [validateName],
                'apellidoPaterno': [validateName],
                'apellidoMaterno': [validateName], 
                'username': [validateUsername],
                'email': [validateEmail],
                'contrasena': [validatePasswordLength, 6], 
                'confirmar_contrasena': [validatePasswordMatch, passwordInput],
                'fechaNacimiento': [validateDate, 18], 
                'genero': [validateSelection]
            };

            
            Object.keys(fieldsToValidate).forEach(name => {
                const inputElement = registerForm.elements[name];
                if (inputElement) {
                    const [validationFn, ...args] = fieldsToValidate[name];
                    
                    const eventType = (inputElement.type === 'checkbox' || inputElement.type === 'radio' || inputElement.tagName === 'SELECT') ? 'change' : 'blur';

                    inputElement.addEventListener(eventType, () => {
                         if (name === 'confirmar_contrasena') {
                             validatePasswordMatch(passwordInput, inputElement);
                         } else if (name === 'contrasena') {
                             
                             validatePasswordLength(inputElement, ...args);
                             if (confirmPasswordInput.value) {
                                 validatePasswordMatch(inputElement, confirmPasswordInput);
                             }
                         }
                         else {
                             validationFn(inputElement, ...args);
                         }
                    });

                    
                    if (!inputElement.required && inputElement.value === '') {
                         inputElement.classList.remove('is-invalid', 'is-valid');
                    }
                }
            });

             
             if (confirmPasswordInput) {
                 confirmPasswordInput.addEventListener('blur', () => {
                      validatePasswordMatch(passwordInput, confirmPasswordInput);
                 });
             }


            
            registerForm.addEventListener('submit', (event) => {
               
                event.preventDefault();

                
                const isFormValid = validateForm(registerForm, fieldsToValidate);

                if (isFormValid) {
                    console.log('Formulario de registro válido, enviando...');
                    
                    registerForm.submit();
                } else {
                    console.log('Formulario de registro inválido.');
                   
                }
            });
        });
    </script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/register.js"></script>
    
</body>
</html>