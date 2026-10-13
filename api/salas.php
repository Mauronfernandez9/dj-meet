<?php 

session_start();



include __DIR__ . '/conexion.php';




header('Content-Type: application/json; charset=utf-8');



if($_SERVER["REQUEST_METHOD"] == 'GET'){

$codigo = $_GET["codigo"] ?? "";

$codigo = is_string($codigo) ? strtoupper(trim($codigo)) : "";

if($codigo === ""){
    http_response_code(400);
    echo json_encode(['error' => "No hay un codigo que permita localizar una sala"]);
    exit;
}




$sql = "SELECT id,nombre,estado,creada_en,codigo FROM salas WHERE borrado is null and codigo = ?";

$consulta = $conexion->prepare($sql);

$consulta->execute([$codigo]);

$resultado = $consulta->get_result();

$sala = $resultado->fetch_assoc();

if($sala === null){
    http_response_code(404);
    echo json_encode(['error' => 'La sala no existe.']);
    exit;
}


// Solo los participantes de esta sala pueden cargar su pantalla.
$participanteId = $_SESSION['participante_id'] ?? null;
if ($participanteId === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Entrá a la sala desde el formulario de inicio.']);
    exit;
}

$consulta = $conexion->prepare(
    'SELECT sp.rol FROM sala_participantes sp
     INNER JOIN participantes p ON p.id = sp.participante_id
     WHERE sp.sala_id = ? AND sp.participante_id = ? AND p.borrado IS NULL'
);
$consulta->execute([$sala['id'], $participanteId]);
$participacion = $consulta->get_result()->fetch_assoc();

if ($participacion === null) {
    http_response_code(403);
    echo json_encode(['error' => 'Todavía no participás en esta sala. Entrá desde el inicio.']);
    exit;
}

$sala['rol'] = $participacion['rol'];
echo json_encode($sala);
exit;

}

if($_SERVER["REQUEST_METHOD"] == 'POST'){

$nombreSala = $_POST["nombre_sala"] ?? '';
$nombreSala = is_string($nombreSala) ? trim($nombreSala) : '';

if($nombreSala === ''){
    echo json_encode(['error' => 'la sala debe tener un nombre']);
    exit;
}
$estado = "abierta";
$codigo = strtoupper(bin2hex(random_bytes(4)));

$nombreAnfitrion = $_POST['nombre_anfitrion'] ?? '';

$nombreAnfitrion = is_string($nombreAnfitrion) ? trim($nombreAnfitrion) : '';

if($nombreAnfitrion === ''){
    http_response_code(422);
    echo json_encode([
        'error' => "Debe exisitir un nombre de anfitrion"
    ]);
    exit;
}

try{
    $conexion->begin_transaction();

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

    if($participanteId === null){
        $consulta = $conexion->prepare("INSERT INTO participantes(nombre,creado) values (?,NOW());");
        $result = $consulta->execute([$nombreAnfitrion]);
        $participanteId = $conexion->insert_id;
    }

    $sql = "INSERT INTO salas (nombre,estado,creada_en,codigo) values (?,?,NOW(),?);";
    $consulta = $conexion->prepare($sql);
    $resultado = $consulta->execute([$nombreSala,$estado,$codigo]); 
    $id_sala = $conexion->insert_id;

    $sqlSalaParticipante = "INSERT INTO sala_participantes (sala_id,participante_id,rol,ingreso) VALUES (?,?,'anfitrion',NOW())";
    
    $consultaSalaParticipante = $conexion->prepare($sqlSalaParticipante);
    $resultSalaParticipante = $consultaSalaParticipante->execute([$id_sala,$participanteId]);

    $conexion->commit();

    $_SESSION['participante_id'] = $participanteId;

    http_response_code(201);



    echo json_encode([
    'id' => $id_sala,
    'nombreSala' => $nombreSala,
    'estado' => $estado,
    'codigo' => $codigo
]);
    exit;
}
catch (mysqli_sql_exception $error){
    $conexion->rollback();

    http_response_code(500);
    echo json_encode(['error' => 'No se pudo crear la sala.']);
    exit;
}

}



?>



