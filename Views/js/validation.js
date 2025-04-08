
const regexPatterns = {
    name: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s']+$/, 
    username: /^[a-zA-Z0-9_]{3,20}$/,    
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, 
    phone: /^\+?[\d\s-]{8,}$/,
    
};

const errorMessages = {
    required: 'Este campo es obligatorio.',
    name: 'Solo se permiten letras, espacios y apóstrofes.',
    username: 'Entre 3 y 20 caracteres. Solo letras, números y guion bajo (_).',
    email: 'Ingresa un correo electrónico válido.',
    passwordLength: (min) => `La contraseña debe tener al menos ${min} caracteres.`,
    passwordMatch: 'Las contraseñas no coinciden.',
    date: 'Ingresa una fecha válida.',
    phone: 'Ingresa un número de teléfono válido.',
    selection: 'Debes seleccionar una opción.',
    age: (minAge) => `Debes ser mayor de ${minAge} años.`,
    generic: 'Entrada inválida.'
};


function applyValidationFeedback(inputElement, isValid, message = '') {
    const formGroup = inputElement.closest('.mb-3, .form-group'); 
    const feedbackElement = formGroup ? formGroup.querySelector('.invalid-feedback') : null;

    if (isValid) {
        inputElement.classList.remove('is-invalid');
        inputElement.classList.add('is-valid');
        if (feedbackElement) {
            feedbackElement.textContent = '';
            
        }
       
        inputElement.setAttribute('aria-invalid', 'false');
    } else {
        inputElement.classList.remove('is-valid');
        inputElement.classList.add('is-invalid');
        if (feedbackElement) {
            feedbackElement.textContent = message || errorMessages.generic;
          
            feedbackElement.style.display = 'block';
        }
         
        inputElement.setAttribute('aria-invalid', 'true');
        
        if (feedbackElement && inputElement.getAttribute('aria-describedby') !== feedbackElement.id) {
            
            if (!feedbackElement.id) {
                feedbackElement.id = `feedback-for-${inputElement.id || inputElement.name}`;
            }
            inputElement.setAttribute('aria-describedby', feedbackElement.id);
        }
    }
}


function validateRequired(inputElement) {
    const isValid = inputElement.value.trim() !== '';
    applyValidationFeedback(inputElement, isValid, errorMessages.required);
    return isValid;
}


function validateName(inputElement) {
    
    if (!inputElement.required && inputElement.value.trim() === '') {
        inputElement.classList.remove('is-invalid', 'is-valid');
         if (inputElement.nextElementSibling?.classList.contains('invalid-feedback')) {
            inputElement.nextElementSibling.textContent = '';
         }
        return true;
    }
    const isValid = regexPatterns.name.test(inputElement.value.trim());
    applyValidationFeedback(inputElement, isValid, errorMessages.name);
    return isValid;
}


function validateUsername(inputElement) {
    if (!inputElement.required && inputElement.value.trim() === '') {
        inputElement.classList.remove('is-invalid', 'is-valid');
        if (inputElement.nextElementSibling?.classList.contains('invalid-feedback')) {
            inputElement.nextElementSibling.textContent = '';
         }
        return true;
    }
    const isValid = regexPatterns.username.test(inputElement.value.trim());
    applyValidationFeedback(inputElement, isValid, errorMessages.username);
    return isValid;
}


function validateEmail(inputElement) {
     if (!inputElement.required && inputElement.value.trim() === '') {
        inputElement.classList.remove('is-invalid', 'is-valid');
        if (inputElement.nextElementSibling?.classList.contains('invalid-feedback')) {
            inputElement.nextElementSibling.textContent = '';
         }
        return true;
    }
    const isValid = regexPatterns.email.test(inputElement.value.trim());
    applyValidationFeedback(inputElement, isValid, errorMessages.email);
    return isValid;
}


function validatePasswordLength(passwordInput, minLength = 6) {
     if (!passwordInput.required && passwordInput.value === '') { 
        passwordInput.classList.remove('is-invalid', 'is-valid');
         if (passwordInput.nextElementSibling?.classList.contains('invalid-feedback')) {
            passwordInput.nextElementSibling.textContent = '';
         }
        return true;
    }
    const isValid = passwordInput.value.length >= minLength;
    applyValidationFeedback(passwordInput, isValid, errorMessages.passwordLength(minLength));
    return isValid;
}


function validatePasswordMatch(passwordInput, confirmPasswordInput) {
    if (confirmPasswordInput.value === '' && !confirmPasswordInput.required) {
         confirmPasswordInput.classList.remove('is-invalid', 'is-valid');
         if (confirmPasswordInput.nextElementSibling?.classList.contains('invalid-feedback')) {
            confirmPasswordInput.nextElementSibling.textContent = '';
         }
        return true;
    }
    const isValid = passwordInput.value === confirmPasswordInput.value;
    applyValidationFeedback(confirmPasswordInput, isValid, errorMessages.passwordMatch);
    return isValid;
}

function validateDate(dateInput, minAge = null) {
    const value = dateInput.value;
    if (!dateInput.required && value === '') {
        dateInput.classList.remove('is-invalid', 'is-valid');
         if (dateInput.nextElementSibling?.classList.contains('invalid-feedback')) {
            dateInput.nextElementSibling.textContent = '';
         }
        return true;
    }

    let isValid = value !== ''; 
    let message = errorMessages.date;

    if (isValid && minAge !== null) {
        try {
            const birthDate = new Date(value);
            const today = new Date();
            
            const minAgeDate = new Date(today.getFullYear() - minAge, today.getMonth(), today.getDate());

            if (birthDate > minAgeDate) {
                isValid = false;
                message = errorMessages.age(minAge);
            }
        } catch (e) {
           
            isValid = false;
            message = errorMessages.date;
        }
    }

    applyValidationFeedback(dateInput, isValid, message);
    return isValid;
}


function validateSelection(selectInput) {
    const isValid = selectInput.value !== '';
    applyValidationFeedback(selectInput, isValid, errorMessages.selection);
    return isValid;
}


function validatePhone(phoneInput) {
    if (!phoneInput.required && phoneInput.value.trim() === '') {
        phoneInput.classList.remove('is-invalid', 'is-valid');
        if (phoneInput.nextElementSibling?.classList.contains('invalid-feedback')) {
            phoneInput.nextElementSibling.textContent = '';
        }
        return true;
    }
    const isValid = regexPatterns.phone.test(phoneInput.value.trim());
    applyValidationFeedback(phoneInput, isValid, errorMessages.phone);
    return isValid;
}



function validateForm(formElement, fieldsToValidate) {
    let isFormValid = true;
    let firstInvalidElement = null;

    for (const name in fieldsToValidate) {
        const inputElement = formElement.elements[name];
        if (!inputElement) {
            console.warn(`Elemento con name="${name}" no encontrado en el formulario.`);
            continue;
        }

        const [validationFn, ...args] = fieldsToValidate[name];
        let isValid = false;

        
        if (inputElement instanceof NodeList || inputElement instanceof HTMLCollection) {
             console.warn(`Validación de NodeList/HTMLCollection para name="${name}" no implementada en este ejemplo.`);
        } else {
             isValid = validationFn(inputElement, ...args);
        }


        if (!isValid) {
            isFormValid = false;
            if (!firstInvalidElement) {
                firstInvalidElement = inputElement instanceof NodeList ? inputElement[0] : inputElement; 
            }
        }
    }

    
    if (!isFormValid && firstInvalidElement) {
        firstInvalidElement.focus();
        
    }

    return isFormValid;
}