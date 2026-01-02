document.addEventListener('DOMContentLoaded', () => {
    
    // Función global para togglear contraseña
    window.toggleVisibility = (inputId, btn) => {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('span');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility_off'; 
        } else {
            input.type = 'password';
            icon.textContent = 'visibility'; 
        }
    };

    // Función para "limpiar" el rojo cuando el usuario intenta corregir
    // Se llama desde el HTML onclick="resetInputStyle(this)"
    window.resetInputStyle = (wrapper) => {
        // Quitar clase de error del wrapper
        wrapper.classList.remove('is-invalid');
        
        // Quitar clase de error del label asociado
        const formGroup = wrapper.closest('.input-group'); // Ajustado a input-group si usas esa clase
        if (formGroup) {
            const label = formGroup.querySelector('label');
            if (label) label.classList.remove('is-invalid');
        }
    };

    // Manejar reintento
    const form = document.getElementById('retryForm');
    if(form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault(); // Pausa momentánea para animación
            
            const btn = form.querySelector('button[type="submit"]');
            
            // Animación visual
            btn.innerHTML = '<span>Verificando...</span>';
            btn.style.opacity = '0.8';
            
            // Enviar formulario real a login.php
            setTimeout(() => {
                form.submit();
            }, 500); 
        });
    }
});