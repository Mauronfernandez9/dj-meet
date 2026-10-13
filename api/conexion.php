<?php 

$host = getenv('DB_HOST');
$usuario = getenv('DB_USER');

$conexion = new mysqli($host,$usuario,"","dj_meet");

if($conexion->connect_error){
    echo "Hubo un error al conectarse a la bd";
}


?>