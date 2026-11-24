<?php
// api.php
header('Content-Type: application/json');
require 'conexion.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    
    // 1. Iniciar sesión
    case 'login':
        $data = json_decode(file_get_contents("php://input"), true);
        $user = $data['username'] ?? '';
        $pass = $data['password'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE username = ? AND password = ?');
        $stmt->execute([$user, $pass]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            echo json_encode(["success" => true, "mensaje" => "Login correcto"]);
        } else {
            echo json_encode(["success" => false, "mensaje" => "Credenciales incorrectas"]);
        }
        break;

    // 2. Obtener todos los casilleros
    case 'get_casilleros':
        $stmt = $pdo->query('SELECT * FROM casilleros ORDER BY numero ASC');
        $casilleros = $stmt->fetchAll();
        echo json_encode($casilleros);
        break;

    // 3. Asignar un casillero
    case 'asignar':
        $data = json_decode(file_get_contents("php://input"), true);
        $numero = $data['numero'] ?? 0;
        
        // Verificar si es coach
        $stmt = $pdo->prepare('SELECT es_coach FROM casilleros WHERE numero = ?');
        $stmt->execute([$numero]);
        $casillero = $stmt->fetch();

        if ($casillero) {
            if ($casillero['es_coach']) {
                // Tiempo infinito para coaches (lo representaremos dejando tiempo_fin en NULL o a 10 años)
                $stmtUpdate = $pdo->prepare("UPDATE casilleros SET estado = 'Ocupado', tiempo_fin = NULL WHERE numero = ?");
                $stmtUpdate->execute([$numero]);
            } else {
                // Exactamente 3 horas a partir de ahora para regulares
                $stmtUpdate = $pdo->prepare("UPDATE casilleros SET estado = 'Ocupado', tiempo_fin = DATE_ADD(NOW(), INTERVAL 3 HOUR) WHERE numero = ?");
                $stmtUpdate->execute([$numero]);
            }
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "mensaje" => "Casillero no encontrado"]);
        }
        break;

    // 4. Liberar un casillero
    case 'liberar':
        $data = json_decode(file_get_contents("php://input"), true);
        $numero = $data['numero'] ?? 0;

        $stmtUpdate = $pdo->prepare("UPDATE casilleros SET estado = 'Disponible', tiempo_fin = NULL WHERE numero = ?");
        $stmtUpdate->execute([$numero]);
        
        echo json_encode(["success" => true]);
        break;

    default:
        echo json_encode(["error" => "Acción no válida"]);
        break;
}
?>