<?php include 'includes/header.php'; ?>

<section id="view-new-raffle" class="view-section active">
    <header class="section-header">
        <div class="header-content">
            <h2>Crear Nueva Rifa</h2>
            <p>Configura los detalles de tu próximo sorteo.</p>
        </div>
        <div class="header-actions">
            <button type="button" class="btn-text" onclick="window.location.href='rifas.php'">Cancelar</button>
            <button type="submit" form="raffleForm" class="btn-primary btn-glow">Guardar Rifa</button>
        </div>
    </header>

    <div class="create-raffle-layout">
        
        <div class="form-column">
            <form id="raffleForm" class="main-form" method="POST" action="actions/guardar_rifa.php" enctype="multipart/form-data">
                
                <div class="card form-section">
                    <h3 class="section-title">
                        <span class="icon-box bg-blue"><span class="material-symbols-outlined">category</span></span>
                        Tipo de Rifa
                    </h3>
                    
                    <div class="options-grid">
                        <label class="option-card-label">
                            <input type="radio" name="tipo_rifa" value="simple" class="hidden-radio" checked onchange="toggleOpportunities(false)">
                            <div class="option-card">
                                <div class="option-icon bg-gray">
                                    <span class="material-symbols-outlined">casino</span>
                                </div>
                                <div class="option-info">
                                    <div class="option-header">
                                        <span class="option-title">Simple</span>
                                        <span class="material-symbols-outlined check-icon">check_circle</span>
                                    </div>
                                    <p class="option-desc">Rifa tradicional con un solo número por boleto.</p>
                                </div>
                            </div>
                        </label>

                        <label class="option-card-label">
                            <input type="radio" name="tipo_rifa" value="oportunidades" class="hidden-radio" onchange="toggleOpportunities(true)">
                            <div class="option-card">
                                <div class="option-icon bg-gray">
                                    <span class="material-symbols-outlined">stars</span>
                                </div>
                                <div class="option-info">
                                    <div class="option-header">
                                        <span class="option-title">Oportunidades</span>
                                        <span class="material-symbols-outlined check-icon">check_circle</span>
                                    </div>
                                    <p class="option-desc">Múltiples chances de ganar con un solo boleto.</p>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div id="opportunity-options" class="sub-options hidden">
                        <label class="field-label">Distribución de Oportunidades</label>
                        <div class="options-grid small">
                            <label class="option-card-label">
                                <input type="radio" name="tipo_oportunidad" value="aleatorio" class="hidden-radio">
                                <div class="option-card small">
                                    <span class="material-symbols-outlined option-icon-small">shuffle</span>
                                    <div class="option-info">
                                        <span class="option-title-small">Aleatoria</span>
                                        <span class="option-desc-small">Números al azar</span>
                                    </div>
                                </div>
                            </label>
                            <label class="option-card-label">
                                <input type="radio" name="tipo_oportunidad" value="lineal" class="hidden-radio" checked>
                                <div class="option-card small">
                                    <span class="material-symbols-outlined option-icon-small">format_list_numbered</span>
                                    <div class="option-info">
                                        <span class="option-title-small">Lineal</span>
                                        <span class="option-desc-small">Secuencia (1, 2, 3...)</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card form-section">
                    <h3 class="section-title">
                        <span class="icon-box bg-purple"><span class="material-symbols-outlined">info</span></span>
                        Información General
                    </h3>
                    
                    <div class="form-group">
                        <label class="field-label">Título de la Rifa</label>
                        <input type="text" name="titulo" class="input-field" placeholder="Ej: Gran Sorteo Navideño 2025" required>
                    </div>

                    <div class="form-group">
                        <label class="field-label">Descripción</label>
                        <textarea name="descripcion" rows="4" class="input-field textarea" placeholder="Detalles de premios, condiciones y lugar..."></textarea>
                    </div>

                    <div class="media-grid">
                        <div class="form-group">
                            <label class="field-label">Foto de Portada</label>
                            <div class="upload-box" onclick="document.getElementById('fileInput').click()">
                                <span class="material-symbols-outlined upload-icon">add_photo_alternate</span>
                                <p class="upload-text">Subir Portada</p>
                                <p class="upload-hint">PNG, JPG hasta 5MB</p>
                                <input type="file" id="fileInput" name="imagen" style="display:none" accept="image/*" onchange="previewImage(this)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label">Galería Adicional</label>
                            <div class="upload-box white">
                                <span class="material-symbols-outlined upload-icon">video_library</span>
                                <p class="upload-text">Fotos o Videos</p>
                                <p class="upload-hint">Opcional</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card form-section">
                    <h3 class="section-title">
                        <span class="icon-box bg-green"><span class="material-symbols-outlined">radio_button_unchecked</span></span>
                        Configuración
                    </h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="field-label">Fecha del Sorteo</label>
                            <input type="date" name="fecha_sorteo" class="input-field">
                        </div>
                        <div class="form-group">
                            <label class="field-label">Hora</label>
                            <input type="time" name="hora_sorteo" class="input-field">
                        </div>
                    </div>

                    <hr class="divider">

                    <div class="form-row three-cols">
                        <div class="form-group">
                            <label class="field-label">Rango Numérico</label>
                            <div class="input-wrapper">
                                <span class="input-prefix">0 -</span>
                                <input type="number" class="input-field pl-prefix" placeholder="99" id="rangeInput" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="field-label">Total Boletos</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">confirmation_number</span>
                                <input type="number" name="num_boletos" class="input-field pl-icon" placeholder="100" id="ticketsInput" oninput="calculateRevenue(); updateRange();" required>
                            </div>
                        </div>
                        <div class="form-group hidden" id="opp-per-ticket-field">
                            <label class="field-label text-blue">Oportunidades / Boleto</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon text-blue">stars</span>
                                <input type="number" name="oportunidades" class="input-field pl-icon input-blue" placeholder="5" value="1" id="oppInput" oninput="updateRange()">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="field-label">Precio por Boleto</label>
                        <div class="input-wrapper">
                            <span class="input-prefix">$</span>
                            <input type="number" name="precio_boleto" class="input-field pl-prefix pr-suffix font-large" placeholder="0.00" id="priceInput" oninput="calculateRevenue()" step="0.01" required>
                            <span class="input-suffix">MXN</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="summary-column">
            <div class="sticky-wrapper">
                
                <div class="summary-card">
                    <div class="summary-header">
                        <div class="rainbow-line"></div>
                        <p class="summary-label">Proyección</p>
                        <h2 class="summary-title">Recaudación</h2>
                        <div class="circle-notch bottom-left"></div>
                        <div class="circle-notch bottom-right"></div>
                    </div>

                    <div class="summary-body">
                        <div class="summary-details">
                            <div class="detail-row">
                                <span class="detail-label">Total Boletos</span>
                                <span class="detail-value" id="displayTickets">0</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Precio Unitario</span>
                                <span class="detail-value" id="displayPrice">$0.00</span>
                            </div>
                        </div>

                        <div class="total-section">
                            <p class="total-label">Total Estimado</p>
                            <p class="total-value" id="displayTotal">$0.00</p>
                        </div>

                        <div class="info-box">
                            <span class="material-symbols-outlined info-icon">info</span>
                            <p class="info-text">El cálculo asume la venta del <strong>100%</strong> de los boletos disponibles.</p>
                        </div>
                    </div>
                </div>

                <div class="help-box">
                    <p>¿Necesitas ayuda configurando?</p>
                    <a href="#">Ver guía de rifas</a>
                </div>

            </div>
        </div>

    </div>
</section>

<script>
    function toggleOpportunities(show) {
        const section = document.getElementById('opportunity-options');
        const field = document.getElementById('opp-per-ticket-field');
        const oppInput = document.getElementById('oppInput');

        if (show) {
            section.classList.remove('hidden');
            field.classList.remove('hidden');
        } else {
            section.classList.add('hidden');
            field.classList.add('hidden');
            oppInput.value = 1; 
            updateRange();
        }
    }

    function calculateRevenue() {
        const tickets = document.getElementById('ticketsInput').value || 0;
        const price = document.getElementById('priceInput').value || 0;
        const total = tickets * price;

        document.getElementById('displayTickets').innerText = tickets;
        document.getElementById('displayPrice').innerText = '$' + parseFloat(price).toFixed(2);
        document.getElementById('displayTotal').innerText = '$' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const text = input.closest('.upload-box').querySelector('.upload-text');
            text.innerText = "Seleccionado: " + input.files[0].name;
            text.style.color = 'var(--primary-blue)';
            text.style.fontWeight = 'bold';
        }
    }

    function updateRange() {
        const boletos = parseInt(document.getElementById('ticketsInput').value) || 0;
        const opp = parseInt(document.getElementById('oppInput').value) || 1;
        const totalNumeros = boletos * opp;
        
        if(totalNumeros > 0) {
            const log = Math.log10(totalNumeros);
            const isPowerOf10 = Number.isInteger(log);
            
            let max = isPowerOf10 ? totalNumeros - 1 : totalNumeros;
            document.getElementById('rangeInput').value = max;
            document.querySelector('.input-prefix').innerText = isPowerOf10 ? "0 -" : "1 -";
        }
    }
</script>

<?php include 'includes/footer.php'; ?>