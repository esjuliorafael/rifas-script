document.addEventListener('DOMContentLoaded', () => {
    
    // Referencias
    const togglePassBtn = document.getElementById('togglePassBtn');
    const passwordInput = document.getElementById('password');
    const loginForm = document.querySelector('.login-form'); // Ajustado para ser más genérico

    // 1. Mostrar / Ocultar Contraseña
    if(togglePassBtn && passwordInput) {
        togglePassBtn.addEventListener('click', () => {
            // Verificar tipo actual
            const currentType = passwordInput.getAttribute('type');
            
            if (currentType === 'password') {
                passwordInput.setAttribute('type', 'text');
                togglePassBtn.querySelector('span').textContent = 'visibility_off'; 
            } else {
                passwordInput.setAttribute('type', 'password');
                togglePassBtn.querySelector('span').textContent = 'visibility'; 
            }
        });
    }

    // 2. Manejar Login (Funcional con PHP)
    if(loginForm) {
        loginForm.addEventListener('submit', (e) => {
            // Prevenir el envío automático solo un momento para la animación
            e.preventDefault();
            
            const btn = loginForm.querySelector('.btn-primary');
            
            // Efecto visual de "Cargando..."
            if(btn) {
                btn.innerHTML = '<span>Verificando...</span>';
                btn.style.opacity = '0.8';
                btn.disabled = true;
            }

            // Enviamos el formulario al servidor (PHP)
            loginForm.submit();
        });
    }
});