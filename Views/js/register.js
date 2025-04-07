
        let mouseX = 0;
        let mouseY = 0;
        const sensitivity = 0.02; 

        function createStars() {
            const container = document.getElementById('stars');
            for(let i = 0; i < 150; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.width = Math.random() * 3 + 'px';
                star.style.height = star.style.width;
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.animationDelay = Math.random() * 1.5 + 's';
                container.appendChild(star);
            }
        }

        function updateStarsPosition() {
            const starsContainer = document.getElementById('stars');
            const centerX = window.innerWidth / 2;
            const centerY = window.innerHeight / 2;
            
            const offsetX = (mouseX - centerX) * -sensitivity;
            const offsetY = (mouseY - centerY) * -sensitivity;
            
            starsContainer.style.transform = `translate(
                calc(-50% + ${offsetX}px),
                calc(-50% + ${offsetY}px)
            )`;
            
            requestAnimationFrame(updateStarsPosition);
        }

        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
        });

        function createShootingStar() {
            const star = document.createElement('div');
            star.className = 'shooting-star';

            const startX = Math.random() * 120 - 10; 
            const startY = -10; 
            
            
            const angle = -45; 
            const velocityVariation = 0.8 + Math.random() * 0.4; 

            star.style.left = `${startX}%`;
            star.style.top = `${startY}%`;
            star.style.transform = `rotate(${angle}deg)`;
            
            star.style.width = `${150 + Math.random() * 100}px`;
            star.style.opacity = 0.5 + Math.random() * 0.3;
            star.style.animationDuration = `${velocityVariation}s`;

            document.body.appendChild(star);

            setTimeout(() => star.remove(), velocityVariation * 1000);
        }

        function scheduleShootingStar() {
            setTimeout(() => {
                createShootingStar();
                scheduleShootingStar();
            }, 4000 + Math.random() * 8000);
        }

        function populateDates() {
         
            const daySelect = document.getElementById('birth-day');
            for(let d = 1; d <= 31; d++) {
                const option = document.createElement('option');
                option.value = d;
                option.textContent = d;
                daySelect.appendChild(option);
            }

            
            const yearSelect = document.getElementById('birth-year');
            const currentYear = new Date().getFullYear();
            for(let y = currentYear; y >= 1900; y--) {
                const option = document.createElement('option');
                option.value = y;
                option.textContent = y;
                yearSelect.appendChild(option);
            }
        }

        
        populateDates();
        createStars();
        updateStarsPosition();
        scheduleShootingStar();


        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form');
            const inputs = {
                nombre: document.querySelector('input[placeholder="Nombre"]'),
                apellidoPaterno: document.querySelector('input[placeholder="Apellido paterno"]'),
                apellidoMaterno: document.querySelector('input[placeholder="Apellido materno"]'),
                username: document.querySelector('input[placeholder="Username"]'),
                email: document.querySelector('input[placeholder="E-mail"]'),
                password: document.querySelector('input[placeholder="Contraseña"]'),
                confirmPassword: document.querySelector('input[placeholder="Confirmar contraseña"]'),
                day: document.getElementById('birth-day'),
                month: document.getElementById('birth-month'),
                year: document.getElementById('birth-year'),
                gender: document.querySelector('select[name="gender"]')
            };
        

            const regex = {
                name: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/,
                username: /^[a-zA-Z0-9_]+$/,
                email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            };
        
            
            Object.entries(inputs).forEach(([key, input]) => {
                if (!input) return;
                
                input.addEventListener('input', () => {
                    if (key === 'confirmPassword') {
                        validatePasswordConfirmation();
                    } else {
                        validateField(input, validationFunctions[key]());
                    }
                });
            });
        
         
            const validationFunctions = {
                nombre: () => regex.name.test(inputs.nombre.value.trim()),
                apellidoPaterno: () => regex.name.test(inputs.apellidoPaterno.value.trim()),
                apellidoMaterno: () => regex.name.test(inputs.apellidoMaterno.value.trim()),
                username: () => regex.username.test(inputs.username.value.trim()),
                email: () => regex.email.test(inputs.email.value.trim()),
                password: () => inputs.password.value.length >= 8,
                confirmPassword: () => inputs.password.value === inputs.confirmPassword.value,
                birthdate: () => {
                    const day = inputs.day.value;
                    const month = inputs.month.value;
                    const year = inputs.year.value;
                    
                    if (!day || !month || !year) {
                        showBirthdateError('Completa todos los campos');
                        return false;
                    }
                    
                    const birthDate = new Date(year, month - 1, day);
                    const today = new Date();
                    let age = today.getFullYear() - birthDate.getFullYear();
                    const monthDiff = today.getMonth() - birthDate.getMonth();
                    
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                        age--;
                    }
                    
                    if (age < 18) {
                        showBirthdateError('Debes ser mayor de 18 años');
                        return false;
                    }
                    
                    clearBirthdateError();
                    return true;
                },
                gender: () => {
                    const isValid = inputs.gender.value !== '';
                    inputs.gender.classList.toggle('is-invalid', !isValid);
                    inputs.gender.classList.toggle('is-valid', isValid);
                    return isValid;
                }
            };
        
            function validateField(field, isValid) {
                const feedback = field.nextElementSibling;
                if (!isValid && field.value.trim() !== '') {
                    field.classList.add('is-invalid');
                    field.classList.remove('is-valid');
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
                
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = isValid ? 'none' : 'block';
                }
            }
        
            function validatePasswordConfirmation() {
                const isValid = validationFunctions.confirmPassword();
                inputs.confirmPassword.classList.toggle('is-invalid', !isValid);
                inputs.confirmPassword.classList.toggle('is-valid', isValid);
                
                const feedback = inputs.confirmPassword.nextElementSibling;
                if (feedback) {
                    feedback.textContent = isValid ? '' : 'Las contraseñas no coinciden';
                    feedback.style.display = isValid ? 'none' : 'block';
                }
            }
        
           
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                let isFormValid = true;
        
              
                Object.values(inputs).forEach(input => {
                    if (!input || (input.tagName === 'SELECT' && !input.value)) {
                        input.classList.add('is-invalid');
                        isFormValid = false;
                    }
                });
        
                Object.entries(validationFunctions).forEach(([key, validate]) => {
                    if (!validate()) {
                        inputs[key].classList.add('is-invalid');
                        isFormValid = false;
                    }
                });
        
                if (isFormValid) {
                    form.submit();
                } else {
                   
                    document.querySelectorAll('.invalid-feedback').forEach(feedback => {
                        feedback.style.display = 'block';
                    });
                }
            });
        
            
            const daySelect = document.getElementById('birth-day');
            for (let i = 1; i <= 31; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i;
                daySelect.appendChild(option);
            }
        
            const yearSelect = document.getElementById('birth-year');
            const currentYear = new Date().getFullYear();
            for (let i = currentYear - 17; i >= currentYear - 100; i--) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i;
                yearSelect.appendChild(option);
            }
        });

        