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
                
                // --- CAMBIO IMPORTANTE ---
                // Se comenta el envío de notificación aquí para evitar duplicidad y errores de datos.
                // La notificación ahora se maneja en el controlador (api/reservar.php)
                /*
                try {
                    $cantidad = is_array($datos['boletos'] ?? null) ? count($datos['boletos']) : 1;
                    $datos['total'] = $this->calcularTotal($datos['rifa_id'], $cantidad);
                    $this->notificarVentaNueva($datos);
                } catch (Exception $e) { }
                */
                // -------------------------

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
    // 3. MÉTODOS DE LECTURA
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
        // 1. Configuración y Dependencias
        include_once 'Configuracion.php'; 
        $configModel = new Configuracion($this->conn);
        
        // Configuración de Remitente
        $remitente = $configModel->obtener('email_remitente');
        if(empty($remitente)) $remitente = 'notificaciones@rancholastrojes.com.mx'; // Fallback seguro

        // Configuración de Dominio para Imágenes (Proyecto Rifas)
        $DOMAIN_URL = "https://rifas.rancholastrojes.com.mx/"; 
        $IMG_PLACEHOLDER = "assets/images/logo.png"; // Ruta relativa del logo como fallback

        // 2. Obtener Destinatarios (Admins)
        $queryDest = "SELECT email, email_alternativo, nombre FROM usuarios WHERE recibir_avisos = 1 AND estado = 1";
        $stmtDest = $this->conn->prepare($queryDest);
        $stmtDest->execute();
        $destinatarios = $stmtDest->fetchAll(PDO::FETCH_ASSOC);

        if(count($destinatarios) === 0) return;

        // 3. Obtener Información de la Rifa (Imagen, Título, Precio)
        // Esto es crucial para no mostrar "Pendiente" y mostrar la foto real
        $rifa_id = $datos_venta['rifa_id'];
        $qRifa = "SELECT titulo, imagen, precio_boleto FROM rifas WHERE id = :id LIMIT 1";
        $stmtRifa = $this->conn->prepare($qRifa);
        $stmtRifa->execute([':id' => $rifa_id]);
        $infoRifa = $stmtRifa->fetch(PDO::FETCH_ASSOC);

        // Datos de la Rifa (con fallbacks)
        $tituloRifa = $infoRifa ? $infoRifa['titulo'] : 'Rifa General';
        $precioUnitario = $infoRifa ? floatval($infoRifa['precio_boleto']) : 0;
        
        // Construcción URL Imagen
        $img_src = $DOMAIN_URL . $IMG_PLACEHOLDER;
        if ($infoRifa && !empty($infoRifa['imagen'])) {
            // La BD tiene: assets/uploads/rifas/portadas/foto.jpg
            // Concatenamos dominio + ruta
            $img_src = $DOMAIN_URL . ltrim($infoRifa['imagen'], '/');
        }

        // 4. Procesar Datos del Participante y Boletos
        $cliente = htmlspecialchars($datos_venta['nombre']);
        $telefono = htmlspecialchars($datos_venta['telefono']);
        
        // Normalizar boletos a Array
        $array_boletos = [];
        if (isset($datos_venta['boletos']) && is_array($datos_venta['boletos'])) {
            $array_boletos = $datos_venta['boletos'];
        } elseif (isset($datos_venta['numero'])) {
            $array_boletos = [$datos_venta['numero']];
        }

        $cantidad = count($array_boletos);
        
        // Calcular Total Real
        $totalCalculado = $cantidad * $precioUnitario;
        $totalFormateado = "$" . number_format($totalCalculado, 2);

        // Generar HTML de Tags de Boletos
        $boletos_html = "";
        foreach ($array_boletos as $num) {
            $boletos_html .= "<span class='ticket-tag'>" . $num . "</span>";
        }

        $asunto = "Nuevo Apartado: " . $cliente . " (" . $cantidad . " boletos)";
        
        // 5. Plantilla HTML (Diseño Unificado)
        $mensaje = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap');

                body { margin: 0; padding: 1.25rem 0; background-color: #f3efeb !important; font-family: 'Plus Jakarta Sans', Arial, sans-serif; }
                .container { max-width: 512px; margin: 0 auto; background-color: #ffffff !important; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
                
                /* Header & Footer */
                .header { padding: 2.25rem 1.75rem 1.75rem; text-align: center; }
                .header-logo { width: 100px; height: auto; border-radius: 50%; }
                .header-subtitle { margin-top: 15px; font-size: 15px; color: #6b7280; }
                .footer { padding: 20px; border-top: 1px solid #e5e7eb; background-color: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
                
                /* Content */
                .content { padding: 0 40px 40px; color: #4b5563; }
                .section-title { margin-bottom: 1.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 1rem; color: #1a1a1a; }
                .lora-italic { font-family: 'Lora', serif; font-weight: 600; font-style: italic; color: #8b5e3c; }
                
                /* Data Rows */
                .data-row { margin-bottom: 1rem; }
                .label { display: block; margin-bottom: 0.25rem; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
                .value { font-weight: 500; font-size: 1rem; color: #1f2937; }
                
                /* Raffle Item Table */
                .item-table { width: 100%; border-collapse: collapse; border: 0; margin-bottom: 12px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px; }
                .item-img { display: block; border-radius: 8px; object-fit: cover; border: 1px solid #e5e7eb; background-color: #ffffff; }
                .item-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; font-weight: 700; color: #1a1a1a; line-height: 1.2; margin-bottom: 4px; }
                .item-meta { font-family: monospace; font-size: 13px; color: #6b7280; }

                /* Tickets Tags Container */
                .tickets-container { margin-bottom: 2rem; }
                .ticket-tag { 
                    display: inline-block; 
                    padding: 4px 8px; 
                    margin: 0 4px 4px 0; 
                    border-radius: 8px; 
                    font-family: monospace; 
                    font-size: 12px; 
                    font-weight: 600; 
                    color: #ffffff; 
                    background-color: #8b5e3c; 
                    text-decoration: none;
                }
                
                /* Totals Box */
                .total-box { margin-top: 1rem; padding: 1.25rem; background-color: #f9f7f5 !important; border-radius: 0.75rem; }
                .table-totals { width: 100%; border-collapse: collapse; border: 0; }
                .td-total-label { font-size: 18px; font-weight: 700; color: #1a1a1a; }
                .td-total-value { font-size: 18px; font-weight: 700; color: #1a1a1a; text-align: right; }

                /* Button */
                .btn-wrapper { text-align: center; margin-top: 20px; }
                .btn-admin { display: inline-block; padding: 12px 24px; background-color: #1a1a1a; border-radius: 8px; color: #ffffff; font-weight: 600; font-size: 14px; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='{$DOMAIN_URL}assets/images/logo.png' alt='Rancho Las Trojes' class='header-logo'>
                    <p class='header-subtitle'>Nuevo apartado de boletos registrado</p>
                </div>

                <div class='content'>
                    
                    <div class='section-title'>
                        Datos <span class='lora-italic'>del Participante</span>
                    </div>
                    
                    <div class='data-row'>
                        <span class='label'>Nombre</span>
                        <div class='value'>{$cliente}</div>
                    </div>
                    <div class='data-row'>
                        <span class='label'>Teléfono</span>
                        <div class='value'>{$telefono}</div>
                    </div>

                    <div class='section-title' style='margin-top: 40px;'>
                        Detalle <span class='lora-italic'>del Apartado</span>
                    </div>
                    
                    <table class='item-table' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td width='70' valign='top' style='padding-right: 15px;'>
                                <img src='{$img_src}' alt='Rifa' width='60' height='60' class='item-img'>
                            </td>
                            <td valign='middle'>
                                <div class='item-title'>
                                    {$tituloRifa}
                                </div>
                                <div class='item-meta'>
                                    Cantidad: {$cantidad} boleto(s)
                                </div>
                            </td>
                        </tr>
                    </table>

                    <div class='data-row'>
                        <span class='label' style='margin-bottom: 8px;'>Boletos Seleccionados</span>
                        <div class='tickets-container'>
                            {$boletos_html}
                        </div>
                    </div>

                    <div class='total-box'>
                        <table class='table-totals' cellpadding='0' cellspacing='0'>
                            <tr>
                                <td class='td-total-label'>Total a Pagar</td>
                                <td class='td-total-value'>{$totalFormateado}</td>
                            </tr>
                        </table>
                    </div>

                    <div class='btn-wrapper'>
                        <a href='https://rifas.rancholastrojes.com.mx/admin' class='btn-admin'>Gestionar en Panel</a>
                    </div>
                </div>

                <div class='footer'>
                    Notificación interna de sistema.<br>
                    © " . date('Y') . " Rancho Las Trojes
                </div>
            </div>
        </body>
        </html>
        ";

        // 6. Enviar Correo
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