<?php

session_start();
include __DIR__ . '/conexion.php';
header('Content-Type: application/json; charset=utf-8');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(["error" => "Usa POST para votar"]);
    exit;
}

$propuestaId = $_POST["propuesta_id"] ?? "";
$participanteId = $_SESSION['participante_id'] ?? null;

if(!is_string($propuestaId) || !ctype_digit($propuestaId) || $propuestaId < 1 || $propuestaId > 2147483647){
    http_response_code(422);
    echo json_encode(["error" => "Falta el id de la propuesta"]);
    exit;
}

if($participanteId === null){
    http_response_code(401);
    echo json_encode(["error" => "Debes entrar a la sala primero"]);
    exit;
}

try{
    $sql = "SELECT propuestas.estado, salas.estado AS estado_sala
            FROM propuestas
            INNER JOIN salas ON salas.id = propuestas.sala_id
            INNER JOIN sala_participantes ON sala_participantes.sala_id = salas.id
            INNER JOIN participantes ON participantes.id = sala_participantes.participante_id
            WHERE propuestas.id = ? AND participantes.id = ?
            AND propuestas.borrada_en IS NULL AND salas.borrado IS NULL
            AND participantes.borrado IS NULL";
    $consulta = $conexion->prepare($sql);
    $consulta->execute([$propuestaId,$participanteId]);
    $propuesta = $consulta->get_result()->fetch_assoc();

    if($propuesta === null){
        http_response_code(404);
        echo json_encode(["error" => "La propuesta no existe o no perteneces a esa sala"]);
        exit;
    }

    if($propuesta["estado"] !== "pendiente" || $propuesta["estado_sala"] !== "abierta"){
        http_response_code(409);
        echo json_encode(["error" => "Esta propuesta ya no admite votos"]);
        exit;
    }

    $sql = "INSERT INTO votos (propuesta_id,participante_id,creado_en) VALUES (?,?,NOW())";
    $consulta = $conexion->prepare($sql);
    $consulta->execute([$propuestaId,$participanteId]);

    http_response_code(201);
    echo json_encode(["mensaje" => "Voto guardado"]);
    exit;
}
catch(mysqli_sql_exception $error){
    if($error->getCode() === 1062){
        http_response_code(409);
        echo json_encode(["error" => "Ya votaste esta cancion"]);
        exit;
    }

    http_response_code(500);
    echo json_encode(["error" => "No se pudo guardar el voto"]);
    exit;
}
