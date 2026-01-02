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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $rifa->titulo; ?> | Rifas Las Trojes</title>
    <link rel="stylesheet" href="assets/css/styles.css"> <style>
        .grid-boletos { display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px; }
        .boleto { border: 1px solid #ccc; padding: 10px; text-align: center; cursor: pointer; }
        .boleto.ocupado { background-color: #ffcccc; color: #900; pointer-events: none; }
        .boleto.seleccionado { background-color: #ccffcc; border-color: #090; }
    </style>
</head>
<body>

    <h1><?php echo $rifa->titulo; ?></h1>
    <img src="assets/uploads/<?php echo $rifa->imagen; ?>" style="max-width:300px;">
    
    <h3>Selecciona tu número:</h3>
    <div id="grid-container" class="grid-boletos">
        Cargando boletos...
    </div>

    <script>
        const rifaId = <?php echo $rifa->id; ?>;
        const totalBoletos = <?php echo $rifa->num_boletos; ?>;
        
        // 1. Cargar disponibilidad desde la API que creamos
        fetch(`api/disponibilidad.php?rifa_id=${rifaId}`)
            .then(response => response.json())
            .then(ocupados => {
                const contenedor = document.getElementById('grid-container');
                contenedor.innerHTML = ''; // Limpiar

                // 2. Generar la cuadrícula
                for (let i = 0; i < totalBoletos; i++) {
                    let div = document.createElement('div');
                    div.classList.add('boleto');
                    // Formato 005 en vez de 5
                    let numeroFormateado = i.toString().padStart(3, '0'); 
                    div.textContent = numeroFormateado;

                    // 3. Verificar si está ocupado usando el array de la API
                    if (ocupados.includes(i)) {
                        div.classList.add('ocupado');
                        div.title = "Vendido";
                    } else {
                        // Click para seleccionar
                        div.onclick = function() {
                            this.classList.toggle('seleccionado');
                            // Aquí lógica para añadir al carrito
                            console.log("Seleccionaste: " + numeroFormateado);
                        };
                    }
                    contenedor.appendChild(div);
                }
            });
    </script>

</body>
</html>