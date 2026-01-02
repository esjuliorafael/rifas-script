document.addEventListener('DOMContentLoaded', () => {
    
    // Elementos
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordForm = document.getElementById('passwordForm');
    const submitBtn = document.getElementById('submitBtn');
    
    const bars = [
        document.getElementById('bar1'),
        document.getElementById('bar2'),
        document.getElementById('bar3'),
        document.getElementById('bar4')
    ];
    
    const strengthText = document.getElementById('strengthText'); // Asegúrate que exista en tu HTML o quita esta línea
    const matchText = document.getElementById('matchText'); // Asegúrate que exista en tu HTML

    // 1. Alternar Visibilidad (Ojo) - Definido globalmente para los onclick del HTML
    window.toggleVisibility = (inputId, btn) => {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('span'); // Asegúrate que el botón tenga un span dentro
        
        if (input && icon) {
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    };

    // 2. Lógica de Fuerza de Contraseña
    if(newPassword) {
        newPassword.addEventListener('input', () => {
            const val = newPassword.value;
            let score = 0;

            // Reglas simples
            if(val.length > 5) score++;
            if(val.length > 8) score++;
            if(/[A-Z]/.test(val)) score++;
            if(/[0-9]/.test(val) || /[^A-Za-z0-9]/.test(val)) score++;

            // Pintar barras
            bars.forEach((bar, index) => {
                if(bar) { // Check por si acaso no existen en el HTML
                    if (index < score) {
                        bar.classList.remove('active', 'weak', 'medium', 'strong');
                        bar.classList.add('active');
                        if (score <= 2) bar.classList.add('weak');
                        else if (score === 3) bar.classList.add('medium');
                        else bar.classList.add('strong');
                    } else {
                        bar.className = 'strength-bar'; // Reset
                    }
                }
            });

            validateMatch();
        });
    }

    // 3. Validar Coincidencia
    if(confirmPassword) {
        confirmPassword.addEventListener('input', validateMatch);
    }

    function validateMatch() {
        if(!newPassword || !confirmPassword || !submitBtn) return;

        const pass1 = newPassword.value;
        const pass2 = confirmPassword.value;

        // Mostrar texto de error si no coinciden
        if (matchText) {
            if (pass2.length > 0 && pass1 !== pass2) {
                matchText.classList.remove('hidden');
                submitBtn.disabled = true;
            } else {
                matchText.classList.add('hidden');
                // Habilitar solo si hay contraseña decente y coinciden
                submitBtn.disabled = (pass1.length < 6 || pass1 !== pass2);
            }
        } else {
            // Fallback si no hay elemento de texto
             submitBtn.disabled = (pass1.length < 6 || pass1 !== pass2);
        }
    }

    // 4. Submit REAL
    if(passwordForm) {
        passwordForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Simular envío visual
            if(submitBtn) {
                submitBtn.innerHTML = '<span>Guardando...</span>';
                submitBtn.style.opacity = '0.8';
            }

            // Enviar formulario al PHP
            passwordForm.submit();
        });
    }
});