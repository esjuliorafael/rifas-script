document.addEventListener('DOMContentLoaded', () => {
    
    // Referencias al DOM
    const recoveryForm = document.querySelector('.recovery-form');

    if(recoveryForm) {
        recoveryForm.addEventListener('submit', (e) => {
            e.preventDefault(); // Detenemos para cambiar el botón

            // 1. Simular carga en el botón
            const btn = recoveryForm.querySelector('.btn-primary');
            
            if(btn) {
                btn.innerHTML = '<span>Enviando...</span>';
                btn.style.opacity = '0.8';
                btn.disabled = true;
            }

            // 2. Enviar datos reales al servidor
            recoveryForm.submit();
        });
    }
});