/**
 * Filtra las tarjetas manipulando clases, respetando el CSS responsive.
 * @param {string} estado - 'activa' o 'finalizada'
 */
function filterRifas(estado) {
    // 1. Manejo visual de los botones (Se mantiene igual)
    const btnActive = document.getElementById('btn-active-rifas');
    const btnFinished = document.getElementById('btn-finished-rifas');
    
    if (btnActive && btnFinished) {
        btnActive.classList.remove('active');
        btnFinished.classList.remove('active');
        
        if (estado === 'activa') {
            btnActive.classList.add('active');
        } else {
            btnFinished.classList.add('active');
        }
    }

    // 2. Filtrado Lógico (SIN tocar style.display)
    // Seleccionamos TODAS las tarjetas usando la clase común
    const cards = document.querySelectorAll('.estado-rifa');

    cards.forEach(card => {
        const cardState = card.getAttribute('data-estado');

        // Limpieza de seguridad: Si había estilos inline viejos, los borramos
        card.style.display = ''; 

        // Lógica de comparación
        if (cardState === estado) {
            // COINCIDE: Quitamos la clase de ocultar.
            // El CSS (Media Query) decidirá si se muestra la Mobile o la Desktop.
            card.classList.remove('hidden-by-filter');
        } else {
            // NO COINCIDE: Ocultamos forzosamente.
            card.classList.add('hidden-by-filter');
        }
    });
}