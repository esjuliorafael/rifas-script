<?php
include_once 'config/database.php';
include_once 'models/Rifa.php';

$id_rifa = isset($_GET['id']) ? $_GET['id'] : die('Error: ID no especificado');

$database = new Database();
$db = $database->getConnection();

$rifa = new Rifa($db);
$rifa->id = $id_rifa;

if(!$rifa->obtenerUna()) {
    die("Rifa no encontrada");
}

// LÓGICA DE CIERRE AUTOMÁTICO
$venta_cerrada = false;
$mensaje_cierre = "";

if($rifa->fecha_sorteo) {
    $fecha_limite = strtotime($rifa->fecha_sorteo);
    $ahora = time();
    
    if($ahora > $fecha_limite) {
        $venta_cerrada = true;
        $mensaje_cierre = "Venta Cerrada (Tiempo límite alcanzado)";
    }
}

// Obtenemos oportunidades extra
$mapa_oportunidades = [];
if($rifa->oportunidades > 1) {
    $mapa_oportunidades = $rifa->obtenerOportunidades($id_rifa);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($rifa->titulo); ?> | Rifas Las Trojes</title>
    <link rel="stylesheet" href="assets/css/dashboard.css"> 
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    
    <style>
        body { background-color: #f3f4f6; font-family: 'Inter', sans-serif; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        
        h1 { color: #111827; font-size: 1.8rem; margin-bottom: 10px; }
        .info-rifa { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb; }
        
        /* Grid de Boletos */
        .grid-boletos { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr)); 
            gap: 8px; 
            margin-top: 20px; 
        }
        
        .boleto { 
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db; 
            border-radius: 8px;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            color: #374151;
        }
        
        .boleto:hover { border-color: #2563eb; color: #2563eb; background-color: #eff6ff; }
        .boleto.ocupado { background-color: #fee2e2; color: #ef4444; border-color: #fecaca; pointer-events: none; opacity: 0.6; }
        
        /* Estilo para boletos deshabilitados por tiempo */
        .boleto.disabled-time { background-color: #f3f4f6; color: #9ca3af; cursor: not-allowed; border-color: #e5e7eb; }
        .boleto.disabled-time:hover { background-color: #f3f4f6; color: #9ca3af; }

        /* Banner de Cierre */
        .closed-banner {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 1rem;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Modal simple */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 2rem; border-radius: 16px; width: 90%; max-width: 400px; text-align: center; animation: fadeIn 0.3s ease; }
        .btn-confirm { background: #111827; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; }
        .btn-close { background: transparent; border: none; color: #6b7280; margin-top: 10px; cursor: pointer; text-decoration: underline; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container">
    
    <?php if($venta_cerrada): ?>
        <div class="closed-banner">
            <span class="material-symbols-outlined">lock_clock</span>
            <?php echo $mensaje_cierre; ?>
        </div>
    <?php endif; ?>

    <div class="info-rifa">
        <h1><?php echo htmlspecialchars($rifa->titulo); ?></h1>
        <p><?php echo htmlspecialchars($rifa->descripcion); ?></p>
        <p><strong>Precio:</strong> $<?php echo number_format($rifa->precio_boleto, 2); ?></p>
        
        <?php if($rifa->fecha_sorteo): ?>
            <p><strong>Cierre de Venta:</strong> <?php echo date("d/m/Y h:i A", strtotime($rifa->fecha_sorteo)); ?></p>
        <?php endif; ?>

        <?php if($rifa->oportunidades > 1): ?>
            <p style="color: #2563eb; font-weight: bold;">¡Cada boleto incluye <?php echo ($rifa->oportunidades - 1); ?> oportunidades extra!</p>
        <?php endif; ?>
    </div>

    <h3>Selecciona tu número:</h3>
    <div id="grid-container" class="grid-boletos">
        Cargando boletos...
    </div>
</div>

<div class="modal-overlay" id="selectionModal">
    <div class="modal-content">
        <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Boleto Seleccionado</h2>
        <div id="modalChips" style="margin-bottom: 1.5rem;"></div>
        <button class="btn-confirm" onclick="addToCart()">Apartar Boleto</button>
        <button class="btn-close" onclick="closeModal()">Cancelar</button>
    </div>
</div>

<script>
    const rifaId = <?php echo $rifa->id; ?>;
    const totalBoletos = <?php echo $rifa->num_boletos; ?>;
    const oportunidades = <?php echo json_encode($mapa_oportunidades); ?>;
    const cifras = <?php echo $rifa->cifras; ?>;
    const isClosed = <?php echo $venta_cerrada ? 'true' : 'false'; ?>;
    
    let boletoSeleccionado = null;

    fetch(`api/disponibilidad.php?rifa_id=${rifaId}`)
        .then(response => response.json())
        .then(ocupados => {
            const contenedor = document.getElementById('grid-container');
            contenedor.innerHTML = '';

            for (let i = <?php echo ($rifa->usa_cero ? 0 : 1); ?>; i < (totalBoletos + <?php echo ($rifa->usa_cero ? 0 : 1); ?>); i++) {
                let div = document.createElement('div');
                div.classList.add('boleto');
                let numeroStr = i.toString().padStart(cifras, '0');
                div.textContent = numeroStr;

                if (ocupados.includes(i)) {
                    div.classList.add('ocupado');
                    div.title = "Vendido";
                } 
                else if (isClosed) {
                    // Si está cerrada la venta, deshabilitamos visualmente
                    div.classList.add('disabled-time');
                    div.title = "Venta Finalizada";
                }
                else {
                    div.onclick = () => openModal(numeroStr);
                }
                contenedor.appendChild(div);
            }
        });

    function openModal(numero) {
        if(isClosed) return; // Doble seguridad frontend

        boletoSeleccionado = numero;
        const modal = document.getElementById('selectionModal');
        const container = document.getElementById('modalChips');
        container.innerHTML = `<h3>#${numero}</h3>`;
        
        if (oportunidades[numero]) {
            container.innerHTML += `<p style="color:#6b7280; font-size:0.9rem;">Oportunidades extra: ${oportunidades[numero].join(', ')}</p>`;
        }

        modal.style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('selectionModal').style.display = 'none';
    }

    function addToCart() {
        alert("Proceso de reserva para el boleto: " + boletoSeleccionado);
        closeModal();
    }
</script>

</body>
</html>