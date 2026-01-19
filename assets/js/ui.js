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
            document.getElementById('ui-btn-confirm').addEventListener('click', handleConfirm);
            document.getElementById('ui-btn-cancel').addEventListener('click', handleCancel);
        });
    },

    // --- 3. SISTEMA DE MODAL FORMULARIO (NUEVO) ---
    formModal: {
        currentCallback: null,

        // Inicializar (Crear el HTML en el DOM si no existe)
        init: function() {
            if (document.getElementById('ui-modal-form-overlay')) return;

            const overlay = document.createElement('div');
            overlay.id = 'ui-modal-form-overlay';
            overlay.className = 'ui-modal-overlay'; // Reusa clase base de ui.css
            overlay.innerHTML = `
                <div class="ui-modal-panel">
                    <div class="ui-modal-header">
                        <div>
                            <h3 id="ui-form-title">Título</h3>
                            <p id="ui-form-subtitle">Subtítulo</p>
                        </div>
                        <button type="button" class="btn-close-modal" onclick="TrojesUI.formModal.close()">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    
                    <div class="ui-modal-body" id="ui-form-body">
                        </div>

                    <div class="ui-modal-footer">
                        <button type="button" class="btn-secondary" onclick="TrojesUI.formModal.close()">Cancelar</button>
                        <button type="button" id="ui-form-submit-btn" class="btn-primary flex-center gap-2">
                            <span id="ui-btn-text">Guardar</span>
                            <span id="ui-btn-loader" class="material-symbols-outlined animate-spin" style="display:none; font-size:1.1rem;">progress_activity</span>
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);

            // Listener para cerrar al hacer clic fuera
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) TrojesUI.formModal.close();
            });

            // Listener para el botón de guardar
            document.getElementById('ui-form-submit-btn').addEventListener('click', this.handleSubmit);
        },

        open: function(templateId, options = {}) {
            this.init(); // Asegurar que existe en el DOM

            const { title, subtitle, btnText, onConfirm } = options;
            
            // 1. Llenar Textos
            document.getElementById('ui-form-title').textContent = title || 'Formulario';
            const subEl = document.getElementById('ui-form-subtitle');
            if(subtitle) {
                subEl.textContent = subtitle;
                subEl.style.display = 'block';
            } else {
                subEl.style.display = 'none';
            }
            document.getElementById('ui-btn-text').textContent = btnText || 'Guardar';

            // 2. Inyectar Template
            const template = document.getElementById(templateId);
            const body = document.getElementById('ui-form-body');
            body.innerHTML = ''; // Limpiar anterior
            
            if (template) {
                // Clonar el contenido del template
                body.appendChild(template.content.cloneNode(true));
            } else {
                console.error(`Template ${templateId} no encontrado`);
                return;
            }

            // 3. Guardar callback y Mostrar
            this.currentCallback = onConfirm;
            const overlay = document.getElementById('ui-modal-form-overlay');
            overlay.classList.add('active'); // Clase que activa la opacidad/visibilidad
        },

        close: function() {
            const overlay = document.getElementById('ui-modal-form-overlay');
            if(overlay) overlay.classList.remove('active');
            this.setLoading(false);
            this.currentCallback = null;
        },

        setLoading: function(isLoading) {
            const btn = document.getElementById('ui-form-submit-btn');
            const loader = document.getElementById('ui-btn-loader');
            const text = document.getElementById('ui-btn-text');
            
            if(isLoading) {
                btn.disabled = true;
                btn.style.opacity = '0.7';
                loader.style.display = 'block';
                // text.style.display = 'none'; // Opcional: ocultar texto
            } else {
                btn.disabled = false;
                btn.style.opacity = '1';
                loader.style.display = 'none';
                text.style.display = 'block';
            }
        },

        handleSubmit: async function() {
            const context = TrojesUI.formModal;
            const body = document.getElementById('ui-form-body');
            const form = body.querySelector('form');

            // Validación HTML5
            if (form && !form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Extraer datos
            const formData = form ? new FormData(form) : new FormData();
            const data = Object.fromEntries(formData.entries());

            if (context.currentCallback) {
                context.setLoading(true);
                try {
                    // Esperar a que la promesa del callback se resuelva
                    await context.currentCallback(data);
                    context.close();
                } catch (error) {
                    console.error(error);
                    TrojesUI.toast('error', error.message || 'Error al procesar');
                } finally {
                    context.setLoading(false);
                }
            } else {
                context.close();
            }
        }
    },

    // --- 4. AUTO-DETECCIÓN DE MENSAJES PHP (URL) ---
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
            
            // Mapeo de mensajes de Configuración
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