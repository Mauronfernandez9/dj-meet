<?php

session_start();
include __DIR__ . '/conexion.php';

if($_SERVER['REQUEST_METHOD'] !== 'GET'){
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['error' => 'Usa GET para buscar canciones.']);
    exit;
}

$busqueda = $_GET['busqueda'] ?? '';
$salaId = $_GET['salaId'] ?? '';
$participanteId = $_SESSION['participante_id'] ?? null;
$busqueda = is_string($busqueda) ? trim($busqueda) : '';

if($participanteId === null){
    http_response_code(401);
    echo json_encode(['error' => 'Debes entrar a una sala primero.']);
    exit;
}

if(mb_strlen($busqueda, 'UTF-8') < 2 || mb_strlen($busqueda, 'UTF-8') > 200 ||
   !is_string($salaId) || !ctype_digit($salaId) || $salaId < 1 || $salaId > 2147483647){
    http_response_code(422);
    echo json_encode(['error' => 'Indica una sala y una busqueda de entre 2 y 200 caracteres.']);
    exit;
}

try{
    $consulta = $conexion->prepare("SELECT salas.id FROM salas
        INNER JOIN sala_participantes ON sala_participantes.sala_id = salas.id
        INNER JOIN participantes ON participantes.id = sala_participantes.participante_id
        WHERE salas.id = ? AND participantes.id = ?
        AND salas.borrado IS NULL AND participantes.borrado IS NULL");
    $consulta->execute([$salaId, $participanteId]);

    if($consulta->get_result()->fetch_assoc() === null){
        http_response_code(404);
        echo json_encode(['error' => 'La sala no existe o no tienes acceso.']);
        exit;
    }
}
catch(mysqli_sql_exception $error){
    error_log($error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar la sala.']);
    exit;
}

if(getenv('CATALOGO_ACTIVO') === '0' || !function_exists('curl_init')){
    http_response_code(503);
    echo json_encode(['error' => 'La busqueda no esta disponible. Podes escribir titulo y artista a mano.']);
    exit;
}

$url = 'https://itunes.apple.com/search?' . http_build_query([
    'term' => $busqueda,
    'country' => 'AR',
    'media' => 'music',
    'entity' => 'song',
    'limit' => 5
]);

$peticion = curl_init($url);
curl_setopt_array($peticion, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 6,
    CURLOPT_USERAGENT => 'DJMeet/1.0',
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);
$respuesta = curl_exec($peticion);
$estadoHttp = curl_getinfo($peticion, CURLINFO_HTTP_CODE);
$errorCurl = curl_errno($peticion);
curl_close($peticion);
$datos = is_string($respuesta) ? json_decode($respuesta, true) : null;

if($respuesta === false || $estadoHttp !== 200 || !is_array($datos) || !isset($datos['results']) || !is_array($datos['results'])){
    error_log('Catalogo no disponible. HTTP: ' . $estadoHttp . ', cURL: ' . $errorCurl);
    http_response_code(503);
    echo json_encode(['error' => 'No pudimos consultar el catalogo. Podes completar la cancion a mano.']);
    exit;
}

$resultados = [];
foreach($datos['results'] as $cancion){
    $titulo = $cancion['trackName'] ?? '';
    $artista = $cancion['artistName'] ?? '';
    $enlace = $cancion['trackViewUrl'] ?? '';
    if(!is_string($titulo) || !is_string($artista) || $titulo === '' || $artista === ''){
        continue;
    }

    if(!is_string($enlace) || !preg_match('~^https://(music|itunes)\.apple\.com/~', $enlace)){
        $enlace = '';
    }

    $resultados[] = ['titulo' => $titulo, 'artista' => $artista, 'enlace' => $enlace];
}

echo json_encode(['resultados' => $resultados]);
