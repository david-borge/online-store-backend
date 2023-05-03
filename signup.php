<?php
// Prevenir que falle la llamada a la API por la CORS Policy porque la web está en un dominio (https://online-store.davidborge.com o http://localhost:4200) y la API en otro (davidborge.com)
// MUCHO CUIDADO: si se me olvida poner la extensión del dominio en una de las alternativas, provoca un Error 500.
$http_origin = $_SERVER['HTTP_ORIGIN'];
if ($http_origin == "http://localhost:4200" || $http_origin == "http://localhost:4000" || $http_origin == "https://online-store.davidborge.com" || $http_origin == "https://online-store-web.herokuapp.com" || $http_origin == "https://online-store-ssr.davidborge.com" )
{  
    header("Access-Control-Allow-Origin: $http_origin");
}

header("Access-Control-Allow-Headers: *");

$jsonUser = json_decode(file_get_contents("php://input"));
if (!$jsonUser) {
    exit("SIGNUP_ERROR_API_DID_NOT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {

    $sentencia = $bd->prepare("insert into users(firstName, lastName, email, password, signUpFullDate, lastLoginFullDate, token) values (?,?,?,?,?,?,?)");
    $resultado = $sentencia->execute([$jsonUser->firstName, $jsonUser->lastName, $jsonUser->email, $jsonUser->password, $jsonUser->signUpFullDate, $jsonUser->lastLoginFullDate, $jsonUser->token]);
    echo json_encode([
        "resultado" => $resultado,
    ]);

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'hewemim@mailinator.com' for key 'users.email'"
    ]);

}