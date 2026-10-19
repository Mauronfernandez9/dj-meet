<?php 

session_start();


include __DIR__ . '/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if($_SERVER['REQUEST_METHOD'] === 'GET'){

    $salaId = $_GET["salaId"] ?? "";
    $salaId = is_string($salaId) ? trim($salaId) : "";
    $participanteId = $_SESSION['participante_id'] ?? null;

    if(!ctype_digit($salaId) || $salaId < 1){
        http_response_code(422);
        echo json_encode(["error" => "Debe existir un id de sala valido"]);
        exit;
    }

    if($participanteId === null){
        http_response_code(401);
        echo json_encode(["error" => "Debes entrar a la sala primero"]);
        exit;
    }

    try{
        $sql = "SELECT salas.id,salas.estado,sala_participantes.rol FROM salas
                INNER JOIN sala_participantes ON sala_participantes.sala_id = salas.id
                INNER JOIN participantes ON participantes.id = sala_participantes.participante_id
                WHERE salas.id = ? AND participantes.id = ?
                AND salas.borrado IS NULL AND participantes.borrado IS NULL";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$salaId,$participanteId]);
        $resultado = $consulta->get_result();
        $sala = $resultado->fetch_assoc();

        if($sala === null){
            http_response_code(404);
            echo json_encode(["error" => "La sala no existe o no tienes acceso"]);
            exit;
        }

        $sql = "SELECT propuestas.id,propuestas.sala_id,propuestas.participante_id,
                propuestas.titulo,propuestas.artista,propuestas.youtube_id,propuestas.imagen_url,
                propuestas.estado,propuestas.creada_en,participantes.nombre AS propuesto_por,
                (SELECT COUNT(*) FROM votos WHERE votos.propuesta_id = propuestas.id) AS votos,
                (SELECT COUNT(*) FROM votos WHERE votos.propuesta_id = propuestas.id AND votos.participante_id = ?) AS votada
                FROM propuestas
                INNER JOIN participantes ON participantes.id = propuestas.participante_id
                WHERE propuestas.sala_id = ? AND propuestas.borrada_en IS NULL
                AND propuestas.estado IN ('pendiente','reproduciendo')
                ORDER BY votos DESC,propuestas.creada_en ASC,propuestas.id ASC";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$participanteId,$salaId]);
        $resultado = $consulta->get_result();

        $propuestas = [];

        while($fila = $resultado->fetch_assoc()){
            $fila["votos"] = (int)$fila["votos"];
            $fila["votada"] = (int)$fila["votada"] > 0;
            $fila["puede_eliminar"] = (int)$fila["participante_id"] === (int)$participanteId;
            $fila["puede_votar"] = !$fila["votada"] && $fila["estado"] === "pendiente" && $sala["estado"] === "abierta";
            $fila["puede_seleccionar"] = $sala["rol"] === "anfitrion" && $fila["estado"] === "pendiente" && $sala["estado"] === "abierta";
            array_push($propuestas,$fila);
        }

        http_response_code(200);
        echo json_encode($propuestas);
        exit;
    }
    catch(mysqli_sql_exception $error){
        http_response_code(500);
        echo json_encode(["error" => "No se pudieron obtener las propuestas"]);
        exit;
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $enlaceYoutube = $_POST["youtube_url"] ?? null;
    $enlaceYoutube = is_string($enlaceYoutube) ? trim($enlaceYoutube) : "";
    $tituloCancion = $_POST["titulo"] ?? null;
    $tituloCancion = is_string($tituloCancion) ? trim($tituloCancion) : "";
    $artista = $_POST["artista"] ?? null;
    $artista = is_string($artista) ? trim($artista) : "";

    $salaId = $_POST["salaId"] ?? null;
    $participanteId = $_SESSION['participante_id'] ?? null;

    if($salaId === null){
        http_response_code(422);
        echo  json_encode(["error" => "Debe existir un id de sala"]);
        exit;
    }

    if($participanteId === null){
        http_response_code(422);
        echo  json_encode(["error" => "Debe existir un id de participante"]);
        exit;
        }

    if($tituloCancion === ""){
    http_response_code(422);
    echo json_encode(["error" => "Debe existir un titulo"]);
    exit;
}


    if($enlaceYoutube === ""){
    http_response_code(422);
    echo json_encode(["error" => "Debe existir un enlace de youtube"]);
    exit;
}

if($artista === ""){
    http_response_code(422);
    echo json_encode(["error" => "Debe existir un artista"]);
    exit;
}

$parametros = parse_url($enlaceYoutube,PHP_URL_QUERY);
parse_str($parametros ?? "",$datosVideo);
$youtubeId = $datosVideo["v"] ?? "";

if($youtubeId === "" && parse_url($enlaceYoutube, PHP_URL_HOST) === "youtu.be"){
    $youtubeId = trim(parse_url($enlaceYoutube, PHP_URL_PATH) ?? "", "/");
}

if(!is_string($youtubeId) || $youtubeId === ""){
    http_response_code(422);
    echo json_encode(["error" => "El enlace no contiene un identificador de video"]);
    exit;
}

    try{


    $sql = "INSERT INTO propuestas (artista,creada_en,estado,participante_id,sala_id,titulo,youtube_id) values (?,now(),?,?,?,?,?);";
    
    $consulta = $conexion->prepare($sql);

    $consulta->execute([$artista,"pendiente",$participanteId,$salaId,$tituloCancion,$youtubeId]);
    
    $idPropuesta = $conexion->insert_id;

    http_response_code(201);



    echo json_encode([
    'id' => $idPropuesta,
    'titulo' => $tituloCancion,
    'url' => $enlaceYoutube,
    'artista' => $artista
]);
    exit;
}catch (mysqli_sql_exception $error){
     $conexion->rollback();

    http_response_code(500);
    echo json_encode(['error' => 'No se pudo crear la propuesta.']);
    exit;


}

    }









if($_SERVER['REQUEST_METHOD'] === 'DELETE'){

    $propuestaId = $_GET["id"] ?? "";
    $propuestaId = is_string($propuestaId) ? trim($propuestaId) : "";
    $participanteId = $_SESSION['participante_id'] ?? null;

    if(!ctype_digit($propuestaId) || $propuestaId < 1){
        http_response_code(422);
        echo json_encode(["error" => "Debe existir un id de propuesta valido"]);
        exit;
    }

    if($participanteId === null){
        http_response_code(401);
        echo json_encode(["error" => "Debes entrar a la sala primero"]);
        exit;
    }

    try{
        $sql = "SELECT propuestas.id FROM propuestas
                INNER JOIN salas ON salas.id = propuestas.sala_id
                INNER JOIN participantes ON participantes.id = propuestas.participante_id
                WHERE propuestas.id = ? AND propuestas.participante_id = ?
                AND propuestas.borrada_en IS NULL
                AND salas.borrado IS NULL AND participantes.borrado IS NULL";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$propuestaId,$participanteId]);
        $propuesta = $consulta->get_result()->fetch_assoc();

        if($propuesta === null){
            http_response_code(404);
            echo json_encode(["error" => "La propuesta no existe o no puedes eliminarla"]);
            exit;
        }

        $sql = "UPDATE propuestas SET borrada_en = NOW()
                WHERE id = ? AND participante_id = ? AND borrada_en IS NULL";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$propuestaId,$participanteId]);

        if($consulta->affected_rows === 0){
            http_response_code(404);
            echo json_encode(["error" => "La propuesta ya no esta disponible"]);
            exit;
        }

        http_response_code(200);
        echo json_encode(["mensaje" => "Propuesta eliminada"]);
        exit;
    }
    catch(mysqli_sql_exception $error){
        http_response_code(500);
        echo json_encode(["error" => "No se pudo eliminar la propuesta"]);
        exit;
    }
}


if($_SERVER['REQUEST_METHOD'] === 'PUT'){

    $salaId = $_GET["salaId"] ?? "";
    $propuestaId = $_GET["id"] ?? "";
    $participanteId = $_SESSION['participante_id'] ?? null;

    if(!is_string($salaId) || !ctype_digit($salaId) || $salaId < 1 ||
       !is_string($propuestaId) || !ctype_digit($propuestaId) || $propuestaId < 1){
        http_response_code(422);
        echo json_encode(["error" => "Falta el id de la sala o de la propuesta"]);
        exit;
    }

    if($participanteId === null){
        http_response_code(401);
        echo json_encode(["error" => "Debes entrar a la sala primero"]);
        exit;
    }

    try{
        $conexion->begin_transaction();

        $sql = "SELECT salas.estado, sala_participantes.rol FROM salas
                INNER JOIN sala_participantes ON sala_participantes.sala_id = salas.id
                INNER JOIN participantes ON participantes.id = sala_participantes.participante_id
                WHERE salas.id = ? AND participantes.id = ?
                AND salas.borrado IS NULL AND participantes.borrado IS NULL
                FOR UPDATE";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$salaId,$participanteId]);
        $sala = $consulta->get_result()->fetch_assoc();

        if($sala === null){
            $conexion->rollback();
            http_response_code(404);
            echo json_encode(["error" => "La sala no existe o no tienes acceso"]);
            exit;
        }

        if($sala["rol"] !== "anfitrion"){
            $conexion->rollback();
            http_response_code(403);
            echo json_encode(["error" => "Solo el anfitrion puede elegir la cancion"]);
            exit;
        }

        if($sala["estado"] !== "abierta"){
            $conexion->rollback();
            http_response_code(409);
            echo json_encode(["error" => "La sala esta cerrada"]);
            exit;
        }

        $sql = "SELECT id,estado FROM propuestas
                WHERE id = ? AND sala_id = ? AND borrada_en IS NULL FOR UPDATE";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$propuestaId,$salaId]);
        $propuesta = $consulta->get_result()->fetch_assoc();

        if($propuesta === null){
            $conexion->rollback();
            http_response_code(404);
            echo json_encode(["error" => "La propuesta no existe en esta sala"]);
            exit;
        }

        if($propuesta["estado"] === "reproduciendo"){
            $conexion->commit();
            http_response_code(200);
            echo json_encode(["mensaje" => "La cancion ya esta seleccionada", "id" => (int)$propuestaId]);
            exit;
        }

        if($propuesta["estado"] !== "pendiente"){
            $conexion->rollback();
            http_response_code(409);
            echo json_encode(["error" => "La cancion ya salio de la cola"]);
            exit;
        }

        $sql = "UPDATE propuestas SET estado = 'reproducida'
                WHERE sala_id = ? AND estado = 'reproduciendo'";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$salaId]);

        $sql = "UPDATE propuestas SET estado = 'reproduciendo' WHERE id = ? AND sala_id = ?";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$propuestaId,$salaId]);

        $conexion->commit();
        http_response_code(200);
        echo json_encode(["mensaje" => "Cancion seleccionada", "id" => (int)$propuestaId]);
        exit;
    }
    catch(mysqli_sql_exception $error){
        $conexion->rollback();
        http_response_code(500);
        echo json_encode(["error" => "No se pudo seleccionar la cancion"]);
        exit;
    }
}

?>