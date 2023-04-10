<?php
// Prevenir que falle la llamada a la API por la CORS Policy porque la web está en un dominio (https://online-store.davidborge.com o http://localhost:4200) y la API en otro (davidborge.com)
$http_origin = $_SERVER['HTTP_ORIGIN'];
if ($http_origin == "http://localhost:4200" || $http_origin == "http://localhost:4000" || $http_origin == "https://online-store.davidborg0" || $http_origin == "https://online-store.davidborge.com" || $http_origin == "https://online-store-web.herokuapp.com" || $http_origin == "https://online-store-ssrr.davidborge.com" )
{  
    header("Access-Control-Allow-Origin: $http_origin");
}

header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: *");

if ($_SERVER["REQUEST_METHOD"] != "PUT") {
    exit("Solo acepto peticiones PUT");
}
$jsonMascota = json_decode(file_get_contents("php://input"));
if (!$jsonMascota) {
    exit("No hay datos");
}
$bd = include_once "bd.php";
$sentencia = $bd->prepare("UPDATE mascotas SET nombre = ?, raza = ?, edad = ? WHERE id = ?");
$resultado = $sentencia->execute([$jsonMascota->nombre, $jsonMascota->raza, $jsonMascota->edad, $jsonMascota->id]);
echo json_encode($resultado);