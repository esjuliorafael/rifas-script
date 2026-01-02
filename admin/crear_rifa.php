<?php include 'includes/header.php'; ?>

<div class="form-container">
    <h1>Nueva Rifa</h1>
    <form action="actions/guardar_rifa.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Título de la Rifa</label>
            <input type="text" name="titulo" required>
        </div>
        <div class="form-group">
            <label>Precio del Boleto</label>
            <input type="number" name="precio_boleto" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Cantidad de Boletos (Ej: 100, 1000)</label>
            <input type="number" name="num_boletos" required>
        </div>
        <div class="form-group">
            <label>Imagen</label>
            <input type="file" name="imagen" required>
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Guardar Rifa</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>