/**
 * Filtra las tarjetas de rifas según su estado
 * @param {string} estado - 'activa' o 'finalizada'
 */
function filterRifas(estado) {
    // 1. Manejo visual de los botones
    const btnActive = document.getElementById('btn-active-rifas');
    const btnFinished = document.getElementById('btn-finished-rifas');
    
    if (!btnActive || !btnFinished) return; // Seguridad si no existen

    // Resetear clases
    btnActive.classList.remove('active');
    btnFinished.classList.remove('active');

    // Activar el botón presionado
    if (estado === 'activa') {
        btnActive.classList.add('active');
    } else {
        btnFinished.classList.add('active');
    }

    // 2. Filtrado real del DOM
    const cards = document.querySelectorAll('.rifa-card');
    let visibles = 0;

    cards.forEach(card => {
        // Obtenemos el estado real desde el atributo data-estado PHP
        const cardState = card.getAttribute('data-estado'); 

        if (cardState === estado) {
            card.style.display = 'flex'; // Mostrar
            visibles++;
        } else {
            card.style.display = 'none'; // Ocultar
        }
    });

    // Opcional: Mostrar mensaje si no hay ninguna en esa categoría
    // (Podrías tener un div oculto con id="no-results" y mostrarlo si visibles === 0)
}