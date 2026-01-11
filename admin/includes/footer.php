</main> </div> </div> </div> <div id="ui-toast-container"></div>

    <div id="ui-modal-confirm" class="ui-modal-overlay">
        <div class="ui-modal">
            <div class="ui-modal-icon">
                <span class="material-symbols-outlined">warning</span>
            </div>
            <h3 id="ui-modal-title">Confirmar Acción</h3>
            <p id="ui-modal-message">¿Estás seguro de continuar?</p>
            <div class="ui-modal-actions">
                <button id="ui-btn-cancel" class="ui-btn ui-btn-cancel">Cancelar</button>
                <button id="ui-btn-confirm" class="ui-btn ui-btn-confirm">Confirmar</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/ui.js"></script>
    
    <script>
        // Lógica del Menú 3D
        document.addEventListener('DOMContentLoaded', () => {
            const trigger = document.getElementById('mobileMenuTrigger');
            const container = document.getElementById('perspectiveContainer');
            const mainInterface = document.getElementById('mainInterface');

            if(trigger && container) {
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    container.classList.toggle('drawer-open');
                });

                mainInterface.addEventListener('click', () => {
                    if(container.classList.contains('drawer-open')) {
                        container.classList.remove('drawer-open');
                    }
                });
            }
        });
    </script>
</body>
</html>