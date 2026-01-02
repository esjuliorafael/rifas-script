CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS rifas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio_boleto DECIMAL(10,2) NOT NULL,
    cantidad_boletos INT NOT NULL,
    imagen VARCHAR(255),
    estado ENUM('activa', 'finalizada', 'cancelada') DEFAULT 'activa',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rifa_id INT NOT NULL,
    numero_boleto INT NOT NULL,
    cliente_nombre VARCHAR(100) NOT NULL,
    cliente_telefono VARCHAR(20) NOT NULL,
    estado_pago ENUM('pendiente', 'pagado', 'cancelado') DEFAULT 'pendiente',
    metodo_pago VARCHAR(50),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rifa_id) REFERENCES rifas(id) ON DELETE CASCADE
);

INSERT INTO usuarios (usuario, password) VALUES ('admin', '$2y$10$wOq5jF8.m4X.0/0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0');