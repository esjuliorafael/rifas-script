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

            if (!modal) {
                console.error("TrojesUI: Modal element not found in DOM");
                return resolve(false);
            }

            // Configurar contenido
            titleEl.textContent = options.title || 'Confirmar';
            msgEl.textContent = options.message || '¿Estás seguro?';
            confirmBtn.textContent = options.confirmText || 'Confirmar';
            
            // Configurar color del botón (opcional)
            if (options.confirmColor) {
                confirmBtn.style.backgroundColor = options.confirmColor;
                confirmBtn.style.borderColor = options.confirmColor;
            } else {
                confirmBtn.style.removeProperty('background-color');
                confirmBtn.style.removeProperty('border-color');
            }

            // Mostrar modal
            modal.classList.add('active');

            // Handlers (Una sola vez)
            const cleanup = () => {
                modal.classList.remove('active');
                confirmBtn.replaceWith(confirmBtn.cloneNode(true));
                cancelBtn.replaceWith(cancelBtn.cloneNode(true));
            };

            const handleConfirm = () => {
                cleanup();
                resolve(true);
            };

            const handleCancel = () => {
                cleanup();
                resolve(false);
            };

            // Re-asignar eventos (usando clones para limpiar listeners viejos)
            // Nota: En una app simple, esto basta.
            document.getElementById('ui-btn-confirm').addEventListener('click', handleConfirm);
            document.getElementById('ui-btn-cancel').addEventListener('click', handleCancel);
        });
    },

    // --- 3. AUTO-DETECCIÓN DE MENSAJES PHP (URL) ---
    init: function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // DETECCIÓN DE ÉXITO
        if (urlParams.has('msg')) {
            const msg = urlParams.get('msg');
            let text = 'Operación exitosa';
            
            // Mapeo de mensajes estándar
            if(msg === 'creado') text = '¡Registro creado exitosamente!';
            if(msg === 'actualizado') text = '¡Registro actualizado correctamente!';
            if(msg === 'eliminado') text = '¡Registro eliminado permanentemente!';
            
            // Mapeo de mensajes de Configuración (NUEVO)
            if(msg === 'pref_ok') text = 'Preferencias de notificación actualizadas.';
            if(msg === 'config_ok') text = 'Configuración guardada correctamente.';
            
            this.toast('success', text);
            // Limpiar URL para que no salga al recargar
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // DETECCIÓN DE ERRORES
        if (urlParams.has('error')) {
            const err = urlParams.get('error');
            let text = 'Ocurrió un error inesperado.';
            
            if(err === 'tiene_ventas') text = 'No se puede eliminar: Tiene ventas asociadas.';
            if(err === 'bd') text = 'Error de base de datos.';
            if(err === 'id_invalido') text = 'Identificador no válido.';
            if(err === 'no_permitido') text = 'No tienes permiso para realizar esta acción.';
            
            this.toast('error', text);
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }
};

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', () => {
    TrojesUI.init();
});