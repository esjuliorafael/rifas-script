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
        document.addEventListener('DOMContentLoaded', () => {
            // Elementos
            const trigger = document.getElementById('mobileMenuTrigger');
            const container = document.getElementById('perspectiveContainer');
            const mainInterface = document.getElementById('mainInterface');
            const menuLinks = document.querySelectorAll('.drawer-menu a'); 

            // Variables para el gesto Swipe
            let touchStartX = 0;
            let touchEndX = 0;
            const swipeThreshold = 50; 

            // --- FUNCIONES ---
            function toggleMenu() {
                container.classList.toggle('drawer-open');
            }

            function closeMenu() {
                container.classList.remove('drawer-open');
            }

            // --- EVENTOS ---

            if(trigger && container && mainInterface) {
                
                // 1. Abrir/Cerrar con Botón Hamburguesa
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleMenu();
                });

                // (ELIMINADO) 2. Cerrar al tocar el contenido principal (Tap Outside)
                // Se ha eliminado este bloque para evitar conflictos.

                // 3. Gesto Swipe (Deslizar para cerrar) - SE MANTIENE
                document.addEventListener('touchstart', (e) => {
                    touchStartX = e.changedTouches[0].screenX;
                }, {passive: true});

                document.addEventListener('touchend', (e) => {
                    touchEndX = e.changedTouches[0].screenX;
                    handleSwipe();
                }, {passive: true});

                function handleSwipe() {
                    if(container.classList.contains('drawer-open')) {
                        // Si desliza de Derecha a Izquierda
                        if (touchStartX - touchEndX > swipeThreshold) {
                            closeMenu();
                        }
                    }
                }

                // 4. Navegación Suave (Cerrar -> Esperar -> Navegar)
                menuLinks.forEach(link => {
                    link.addEventListener('click', (e) => {
                        if (window.innerWidth <= 900 && container.classList.contains('drawer-open')) {
                            e.preventDefault(); 
                            const targetUrl = link.href;
                            closeMenu(); 
                            setTimeout(() => {
                                window.location.href = targetUrl; 
                            }, 350); 
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>