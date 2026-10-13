<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['error' => 'Usá POST para entrar a una sala.']);
    exit;
}

$nombre = $_POST['nombre'] ?? '';
$codigo = $_POST['codigo'] ?? '';
$nombre = is_string($nombre) ? trim($nombre) : '';
$codigo = is_string($codigo) ? strtoupper(trim($codigo)) : '';

if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 40) {
    http_response_code(422);
    echo json_encode(['error' => 'Tu nombre debe tener entre 1 y 40 caracteres.']);
    exit;
}

if (!preg_match('/^[A-F0-9]{8}$/', $codigo)) {
    http_response_code(422);
    echo json_encode(['error' => 'Ingresá el código de 8 caracteres de la sala.']);
    exit;
}

$transaccionActiva = false;

try {
    include __DIR__ . '/conexion.php';
    $conexion->set_charset('utf8mb4');

    // Si falla el ingreso, tampoco queda un participante nuevo sin su relación.
    $conexion->begin_transaction();
    $transaccionActiva = true;

    $consulta = $conexion->prepare(
        'SELECT id, nombre, codigo, estado FROM salas
         WHERE codigo = ? AND borrado IS NULL FOR UPDATE'
    );
    $consulta->execute([$codigo]);
    $sala = $consulta->get_result()->fetch_assoc();

    if ($sala === null) {
        $conexion->rollback();
        http_response_code(404);
        echo json_encode(['error' => 'La sala no existe.']);
        exit;
    }

    if ($sala['estado'] !== 'abierta') {
        $conexion->rollback();
        http_response_code(409);
        echo json_encode(['error' => 'La sala está cerrada.']);
        exit;
    }

    $participanteId = $_SESSION['participante_id'] ?? null;
    if ($participanteId !== null) {
        $consulta = $conexion->prepare(
            'SELECT id FROM participantes WHERE id = ? AND borrado IS NULL'
        );
        $consulta->execute([$participanteId]);
        if ($consulta->get_result()->fetch_assoc() === null) {
            $participanteId = null;
        }
    }

    $nuevoParticipante = $participanteId === null;
    if ($nuevoParticipante) {
        $consulta = $conexion->prepare(
            'INSERT INTO participantes (nombre, creado) VALUES (?, NOW())'
        );
        $consulta->execute([$nombre]);
        $participanteId = $conexion->insert_id;
    }

    $consulta = $conexion->prepare(
        'SELECT id, rol FROM sala_participantes
         WHERE sala_id = ? AND participante_id = ?'
    );
    $consulta->execute([$sala['id'], $participanteId]);
    $participacion = $consulta->get_result()->fetch_assoc();
    $nuevaParticipacion = $participacion === null;

    if ($nuevaParticipacion) {
        $consulta = $conexion->prepare(
            "INSERT INTO sala_participantes (sala_id, participante_id, rol, ingreso)
             VALUES (?, ?, 'invitado', NOW())"
        );
        $consulta->execute([$sala['id'], $participanteId]);
        $rol = 'invitado';
    } else {
        $rol = $participacion['rol'];
    }

    $conexion->commit();
    $transaccionActiva = false;

    if ($nuevoParticipante) {
        session_regenerate_id(true);
    }
    $_SESSION['participante_id'] = $participanteId;

    http_response_code($nuevaParticipacion ? 201 : 200);
    echo json_encode([
        'id' => (int) $sala['id'],
        'nombre' => $sala['nombre'],
        'codigo' => $sala['codigo'],
        'estado' => $sala['estado'],
        'rol' => $rol
    ]);
} catch (mysqli_sql_exception $error) {
    if ($transaccionActiva) {
        $conexion->rollback();
    }
    error_log($error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo completar el ingreso a la sala.']);
}
