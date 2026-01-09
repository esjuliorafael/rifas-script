/* --- assets/js/ui.js --- */

const TrojesUI = {
    // --- 1. SISTEMA DE TOASTS ---
    toast: function(type, message, duration = 5000) {
        const container = document.getElementById('ui-toast-container');
        if (!container) return;

        // Iconos según tipo
        const icons = {
            success: 'check_circle',
            error: 'error',
            warning: 'warning',
            info: 'info'
        };

        // Crear elemento
        const toast = document.createElement('div');
        toast.className = `ui-toast ${type}`;
        toast.innerHTML = `
            <span class="material-symbols-outlined ui-toast-icon">${icons[type] || 'info'}</span>
            <div class="ui-toast-content">${message}</div>
        `;

        // Agregar al DOM
        container.appendChild(toast);

        // Auto eliminar
        setTimeout(() => {
            toast.classList.add('closing');
            toast.addEventListener('animationend', () => toast.remove());
        }, duration);
    },

    // --- 2. SISTEMA DE CONFIRMACIÓN (Promesa) ---
    confirm: function(options) {
        return new Promise((resolve) => {
            const modal = document.getElementById('ui-modal-confirm');
            const titleEl = document.getElementById('ui-modal-title');
            const msgEl = document.getElementById('ui-modal-message');
            const confirmBtn = document.getElementById('ui-btn-confirm');
            const cancelBtn = document.getElementById('ui-btn-cancel');

            // Configurar contenido
            titleEl.textContent = options.title || '¿Estás seguro?';
            msgEl.textContent = options.message || 'Esta acción no se puede deshacer.';
            confirmBtn.textContent = options.confirmText || 'Confirmar';
            
            // Mostrar modal
            modal.classList.add('active');

            // Manejadores de eventos (Limpieza automática para evitar duplicados)
            const handleConfirm = () => {
                cleanup();
                resolve(true);
            };

            const handleCancel = () => {
                cleanup();
                resolve(false);
            };

            const cleanup = () => {
                modal.classList.remove('active');
                confirmBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
            };

            confirmBtn.addEventListener('click', handleConfirm);
            cancelBtn.addEventListener('click', handleCancel);
        });
    },

    // --- 3. AUTO-DETECCIÓN DE MENSAJES PHP ---
    init: function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('msg')) {
            const msg = urlParams.get('msg');
            let text = 'Operación exitosa';
            if(msg === 'creado') text = '¡Registro creado exitosamente!';
            if(msg === 'actualizado') text = '¡Registro actualizado correctamente!';
            if(msg === 'eliminado') text = '¡Registro eliminado permanentemente!';
            
            this.toast('success', text);
            // Limpiar URL para que no salga al recargar
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        if (urlParams.has('error')) {
            const err = urlParams.get('error');
            let text = 'Ocurrió un error inesperado.';
            if(err === 'tiene_ventas') text = 'No se puede eliminar: Tiene ventas asociadas.';
            if(err === 'bd') text = 'Error de base de datos.';
            
            this.toast('error', text);
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }
};

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', () => TrojesUI.init());