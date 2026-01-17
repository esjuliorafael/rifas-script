<?php
class Boleto {
    private $conn;
    private $table = 'ventas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // ==========================================
    // 1. NUEVA LÓGICA: LIMPIEZA AUTOMÁTICA
    // ==========================================
    
    public function liberarReservasExpiradas() {
        // Incluir configuración para leer reglas dinámicas
        include_once 'Configuracion.php';
        $configModel = new Configuracion($this->conn);
        
        // Obtener reglas de la BD
        $sistema_apartado = $configModel->obtener('sistema_apartado');
        $tiempo_limite = $configModel->obtener('tiempo_limite');

        // Si el sistema no está activo (1), no hacemos nada
        if ($sistema_apartado != 1) {
            return 0;
        }

        // Definir horas límite (Fallback de seguridad: 48 horas)
        $horas = (is_numeric($tiempo_limite) && $tiempo_limite > 0) ? (int)$tiempo_limite : 48;

        // Ejecutar eliminación: Estado 'pendiente' Y Fecha < (Ahora - Horas)
        $query = "DELETE FROM " . $this->table . " 
                  WHERE estado_pago = 'pendiente' 
                  AND fecha < DATE_SUB(NOW(), INTERVAL :horas HOUR)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':horas', $horas, PDO::PARAM_INT);
        
        if($stmt->execute()) {
            return $stmt->rowCount(); 
        }
        return 0;
    }

    // ==========================================
    // 2. LÓGICA TRANSACCIONAL (RESERVA)
    // ==========================================

    public function reservar($datos) {
        $this->conn->beginTransaction();

        try {
            // Verificar disponibilidad (Bloqueo FOR UPDATE)
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

            // Insertar venta (Usando NOW() para la fecha exacta)
            $query = "INSERT INTO " . $this->table . " 
                      (rifa_id, numero_boleto, cliente_nombre, cliente_telefono, cliente_estado, estado_pago, fecha) 
                      VALUES (:rifa_id, :numero, :nombre, :telefono, :estado_cliente, 'pendiente', NOW())";

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

            if($stmt->execute()) {
                $this->conn->commit();
                
                // Notificar sin detener el flujo si falla el correo
                try {
                    // Calcular cantidad para el asunto del correo
                    $cantidad = is_array($datos['boletos'] ?? null) ? count($datos['boletos']) : 1;
                    $datos['total'] = $this->calcularTotal($datos['rifa_id'], $cantidad);
                    $this->notificarVentaNueva($datos);
                } catch (Exception $e) {
                    // Log silencioso o ignorar
                }

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

    // ==========================================
    // 3. MÉTODOS DE LECTURA (RESTAURADOS)
    // ==========================================

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

    public function obtenerOcupados($rifa_id) {
        $query = "SELECT numero_boleto, estado_pago, cliente_nombre, cliente_estado 
                  FROM " . $this->table . " 
                  WHERE rifa_id = :rifa_id AND estado_pago IN ('pagado', 'pendiente')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rifa_id', $rifa_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
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
    // 4. NOTIFICACIONES Y HELPERS
    // ==========================================
    
    public function notificarVentaNueva($datos_venta) {
        // 1. Configuración Dinámica (Remitente)
        include_once 'Configuracion.php'; 
        $configModel = new Configuracion($this->conn);
        $remitente = $configModel->obtener('email_remitente');
        if(empty($remitente)) $remitente = 'notificaciones@rancholastrojes.com.mx';

        // 2. Destinatarios Dinámicos (Usuarios con alertas)
        $query = "SELECT email, email_alternativo, nombre FROM usuarios WHERE recibir_avisos = 1 AND estado = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(count($destinatarios) === 0) return;

        // 3. Preparar Datos
        $cliente = htmlspecialchars($datos_venta['nombre']);
        $telefono = htmlspecialchars($datos_venta['telefono']);
        
        $boletos = '';
        $cantidad = 1;
        if (isset($datos_venta['boletos']) && is_array($datos_venta['boletos'])) {
            $boletos = implode(', ', $datos_venta['boletos']);
            $cantidad = count($datos_venta['boletos']);
        } else {
            $boletos = $datos_venta['numero'];
        }

        $total = isset($datos_venta['total']) ? "$" . number_format($datos_venta['total'], 2) : 'Pendiente';
        $asunto = "Nueva Venta: " . $cliente . " (" . $cantidad . " boletos)";
        
        // 4. Plantilla HTML (Diseño Completo)
        $mensaje = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f4f4f4; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
                
                <div style='background-color: #2563eb; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin:0; font-size: 24px;'>¡Nueva Venta Registrada!</h2>
                    <p style='margin: 5px 0 0; opacity: 0.9;'>Sistema de Rifas Las Trojes</p>
                </div>
                
                <div style='padding: 30px;'>
                    <p style='margin-bottom: 20px; font-size: 16px;'>Se ha registrado un nuevo apartado de boletos. Aquí están los detalles:</p>
                    
                    <table style='width: 100%; border-collapse: collapse; background-color: #f9fafb; border-radius: 8px; overflow: hidden;'>
                        <tr>
                            <td style='padding: 12px 15px; border-bottom: 1px solid #eee; color: #666;'><strong>Cliente:</strong></td>
                            <td style='padding: 12px 15px; border-bottom: 1px solid #eee; font-weight: 500;'>{$cliente}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 15px; border-bottom: 1px solid #eee; color: #666;'><strong>Teléfono:</strong></td>
                            <td style='padding: 12px 15px; border-bottom: 1px solid #eee; font-weight: 500;'>{$telefono}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 15px; border-bottom: 1px solid #eee; color: #666;'><strong>Cantidad:</strong></td>
                            <td style='padding: 12px 15px; border-bottom: 1px solid #eee; font-weight: 500;'>{$cantidad} boletos</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 15px; border-bottom: 1px solid #eee; color: #666;'><strong>Números:</strong></td>
                            <td style='padding: 12px 15px; border-bottom: 1px solid #eee; color: #2563eb; font-weight: bold;'>{$boletos}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 15px; color: #666;'><strong>Total Estimado:</strong></td>
                            <td style='padding: 12px 15px; color: #166534; font-weight: bold;'>{$total}</td>
                        </tr>
                    </table>
                    
                    <div style='margin-top: 30px; text-align: center;'>
                        <a href='" . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/admin' : '#') . "' style='display: inline-block; padding: 12px 24px; background-color: #2563eb; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>Gestionar Venta en Panel</a>
                    </div>
                </div>
                
                <div style='background-color: #f1f5f9; padding: 15px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                    Este es un mensaje automático. Por favor no respondas a este correo.<br>
                    &copy; " . date('Y') . " Rancho Las Trojes. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>
        ";

        // 5. Enviar
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $remitente . "\r\n";
        $headers .= "Reply-To: " . $remitente . "\r\n";

        foreach($destinatarios as $admin) {
            $email_destino = !empty($admin['email_alternativo']) ? $admin['email_alternativo'] : $admin['email'];
            @mail($email_destino, $asunto, $mensaje, $headers);
        }
    }

    private function calcularTotal($rifa_id, $cantidad) {
        $q = "SELECT precio_boleto FROM rifas WHERE id = :id";
        $s = $this->conn->prepare($q);
        $s->bindParam(':id', $rifa_id);
        $s->execute();
        $r = $s->fetch(PDO::FETCH_ASSOC);
        return $r ? $r['precio_boleto'] * $cantidad : 0;
    }
}
?>