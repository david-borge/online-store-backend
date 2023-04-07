<?php
// Prevenir que falle la llamada a la API por la CORS Policy porque la web está en un dominio (https://online-store.davidborge.com o http://localhost:4200) y la API en otro (davidborge.com)
$http_origin = $_SERVER['HTTP_ORIGIN'];
if ($http_origin == "http://localhost:4200" || $http_origin == "https://online-store.davidborge.com" )
{  
    header("Access-Control-Allow-Origin: $http_origin");
}

header("Access-Control-Allow-Methods: DELETE");

$metodo = $_SERVER["REQUEST_METHOD"];
if ($metodo != "DELETE" && $metodo != "OPTIONS") {
    exit("Solo se permite método DELETE");
}

if (empty($_GET["idMascota"])) {
    exit("No hay id de mascota para eliminar");
}
$idMascota = $_GET["idMascota"];
$bd = include_once "bd.php";
$sentencia = $bd->prepare("DELETE FROM mascotas WHERE id = ?");
$resultado = $sentencia->execute([$idMascota]);
echo json_encode($resultado);