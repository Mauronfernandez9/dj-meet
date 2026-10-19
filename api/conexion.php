<?php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('DB_HOST');
$usuario = getenv('DB_USER');
$password = getenv('DB_PASS');
$base = getenv('DB_NAME');
$puerto = getenv('DB_PORT');

if(getenv('APP_ENV') === 'production' && (!$host || !$usuario || $password === false || !$base)){
    http_response_code(500);
    echo json_encode(['error' => 'Falta configurar la conexion a la base de datos.']);
    exit;
}

$password = $password === false ? '' : $password;
$base = $base ?: 'dj_meet';
$puerto = $puerto ?: '3306';

if(!$host || !$usuario || !ctype_digit($puerto) || $puerto < 1 || $puerto > 65535){
    http_response_code(500);
    echo json_encode(['error' => 'Revisa las variables de conexion a la base de datos.']);
    exit;
}

try{
    $conexion = new mysqli($host, $usuario, $password, $base, (int)$puerto);
}
catch(mysqli_sql_exception $error){
    error_log($error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo conectar a la base de datos.']);
    exit;
}
