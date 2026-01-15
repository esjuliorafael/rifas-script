<?php
class Boleto {
    private $conn;
    private $table = 'ventas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // --- LÓGICA DE RESERVA (INTACTA) ---
    public function reservar($datos) {
        // 1. Validar disponibilidad (Bloqueo para concurrencia)
        $this->conn->beginTransaction();

        try {
            // Verificar si ya existe
            $queryCheck = "SELECT id FROM " . $this->table . " 
                           WHERE rifa_id = :rifa_id AND numero_boleto = :numero FOR UPDATE";
            $stmtCheck = $this->conn->prepare($queryCheck);
            $stmtCheck->bindParam(':rifa_id', $datos['rifa_id']);
            $stmtCheck->bindParam(':numero', $datos['numero']);
            $stmtCheck->execute();

            if($stmtCheck->rowCount() > 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'El boleto ' . $datos['numero'] . ' ya ha sido ganado o apartado.'];
            }

            // Insertar venta
            $query = "INSERT INTO " . $this->table . " 
                      (rifa_id, numero_boleto, cliente_nombre, cliente_telefono, cliente_estado, estado_pago, fecha) 
                      VALUES (:rifa_id, :numero, :nombre, :telefono, :estado_cliente, :estado_pago, NOW())";

            $stmt = $this->conn->prepare($query);

            // Sanitizar
            $datos['nombre'] = htmlspecialchars(strip_tags($datos['nombre']));
            $datos['telefono'] = htmlspecialchars(strip_tags($datos['telefono']));
            $datos['estado'] = htmlspecialchars(strip_tags($datos['estado']));

            $stmt->bindParam(':rifa_id', $datos['rifa_id']);
            $stmt->bindParam(':numero', $datos['numero']);
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':telefono', $datos['telefono']);
            $stmt->bindParam(':estado_cliente', $datos['estado']);
            // Por defecto entra como pendiente
            $estado_pago = 'pendiente'; 
            $stmt->bindParam(':estado_pago', $estado_pago);

            if($stmt->execute()) {
                $this->conn->commit();
                return ['success' => true, 'message' => 'Boleto apartado exitosamente.'];
            } else {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Error al guardar en base de datos.'];
            }

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Error del sistema: ' . $e->getMessage()];
        }
    }

    // --- OBTENER VENTAS AGRUPADAS (INTACTA) ---
    public function obtenerVentas($filtros = [], $limit = 20, $offset = 0) {
        $query = "SELECT 
                    GROUP_CONCAT(v.id SEPARATOR ',') as ids_venta,
                    GROUP_CONCAT(v.numero_boleto ORDER BY v.numero_boleto ASC SEPARATOR ',') as boletos_agrupados,
                    COUNT(v.id) as cantidad_boletos,
                    SUM(r.precio_boleto) as total_venta,
                    MAX(v.fecha) as fecha_reciente,
                    v.cliente_nombre, 
                    v.cliente_telefono, 
                    v.cliente_estado, 
                    v.estado_pago,
                    r.titulo as nombre_rifa, 
                    r.precio_boleto, 
                    r.cifras,
                    r.estado as estado_rifa
                  FROM " . $this->table . " v
                  LEFT JOIN rifas r ON v.rifa_id = r.id
                  WHERE 1=1";

        $params = [];

        if (!empty($filtros['busqueda'])) {
            $query .= " AND (v.cliente_nombre LIKE :q 
                        OR v.cliente_telefono LIKE :q 
                        OR v.cliente_estado LIKE :q
                        OR r.titulo LIKE :q)";
            $params[':q'] = "%" . $filtros['busqueda'] . "%";
        }

        if (!empty($filtros['estado']) && $filtros['estado'] !== 'todos') {
            $query .= " AND v.estado_pago = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $query .= " GROUP BY v.rifa_id, v.cliente_telefono, v.cliente_nombre, v.estado_pago";
        $query .= " ORDER BY fecha_reciente DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CONTAR GRUPOS (INTACTA) ---
    public function contarVentas($filtros = []) {
        $query = "SELECT COUNT(*) as total FROM (
                    SELECT v.id 
                    FROM " . $this->table . " v
                    LEFT JOIN rifas r ON v.rifa_id = r.id
                    WHERE 1=1";

        $params = [];

        if (!empty($filtros['busqueda'])) {
            $query .= " AND (v.cliente_nombre LIKE :q 
                        OR v.cliente_telefono LIKE :q 
                        OR v.cliente_estado LIKE :q
                        OR r.titulo LIKE :q)";
            $params[':q'] = "%" . $filtros['busqueda'] . "%";
        }

        if (!empty($filtros['estado']) && $filtros['estado'] !== 'todos') {
            $query .= " AND v.estado_pago = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $query .= " GROUP BY v.rifa_id, v.cliente_telefono, v.cliente_nombre, v.estado_pago
                  ) as grupos";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // --- OBTENER OCUPADOS (INTACTA) ---
    public function obtenerOcupados($rifa_id) {
        $query = "SELECT numero_boleto, estado_pago, cliente_nombre, cliente_estado 
                  FROM " . $this->table . " 
                  WHERE rifa_id = :rifa_id AND estado_pago IN ('pagado', 'pendiente')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rifa_id', $rifa_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // --- OBTENER VENTAS DASHBOARD (INTACTA) ---
    public function obtenerUltimasVentasAgrupadas($limite = 8) {
        $query = "SELECT 
                    v.cliente_nombre, 
                    v.cliente_telefono, 
                    v.estado_pago, 
                    r.titulo as nombre_rifa, 
                    COUNT(v.id) as cantidad_boletos, 
                    SUM(r.precio_boleto) as total_venta, 
                    MAX(v.fecha) as fecha_venta 
                  FROM " . $this->table . " v 
                  JOIN rifas r ON v.rifa_id = r.id 
                  GROUP BY v.cliente_nombre, v.cliente_telefono, v.rifa_id, v.estado_pago 
                  ORDER BY fecha_venta DESC 
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // NUEVA FUNCIONALIDAD: NOTIFICACIONES
    // ==========================================
    
    /**
     * Envía un único correo a los administradores suscritos
     * Se llama DESPUÉS de procesar el array de boletos en el controlador
     */
    public function notificarVentaNueva($datos_venta) {
        // 1. Obtener usuarios que quieren recibir avisos (recibir_avisos = 1 y activos)
        $query = "SELECT email, nombre FROM usuarios WHERE recibir_avisos = 1 AND estado = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si nadie quiere correos, terminamos
        if(count($destinatarios) === 0) return;

        // 2. Preparar Datos del Correo
        $cliente = htmlspecialchars($datos_venta['nombre']);
        $telefono = htmlspecialchars($datos_venta['telefono']);
        // Manejo flexible de boletos (array o string)
        $boletos = is_array($datos_venta['boletos']) ? implode(', ', $datos_venta['boletos']) : $datos_venta['boletos'];
        $cantidad = is_array($datos_venta['boletos']) ? count($datos_venta['boletos']) : 1;
        $total = isset($datos_venta['total']) ? "$" . number_format($datos_venta['total'], 2) : 'Pendiente';
        
        $asunto = "Nueva Venta: " . $cliente . " (" . $cantidad . " boletos)";
        
        // Plantilla HTML Limpia
        $mensaje = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>
                <div style='background-color: #2563eb; color: white; padding: 15px; text-align: center;'>
                    <h2 style='margin:0;'>¡Nueva Venta Registrada!</h2>
                </div>
                <div style='padding: 20px;'>
                    <p>Se ha registrado un nuevo apartado de boletos en el sistema.</p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Cliente:</strong></td>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$cliente}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Teléfono:</strong></td>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$telefono}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Cantidad:</strong></td>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$cantidad} boletos</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Números:</strong></td>
                            <td style='padding: 8px; border-bottom: 1px solid #eee; color: #2563eb; font-weight: bold;'>{$boletos}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>Total Estimado:</strong></td>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$total}</td>
                        </tr>
                    </table>
                    
                    <p style='margin-top: 20px; font-size: 0.9em; color: #666;'>
                        Ingresa al panel administrativo para gestionar esta venta.
                    </p>
                </div>
                <div style='background-color: #f9fafb; padding: 10px; text-align: center; font-size: 0.8em; color: #888;'>
                    Notificación automática del sistema de rifas.
                </div>
            </div>
        </body>
        </html>
        ";

        // 3. Cabeceras
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        // IMPORTANTE: Asegúrate de configurar un remitente válido en tu servidor
        $headers .= "From: notificaciones@tudominio.com" . "\r\n"; 

        // 4. Enviar correos
        foreach($destinatarios as $admin) {
            @mail($admin['email'], $asunto, $mensaje, $headers);
        }
    }
}
?>