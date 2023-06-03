<?php
// Prevenir que falle la llamada a la API por la CORS Policy porque la web está en un dominio (https://online-store.davidborge.com o http://localhost:4200) y la API en otro (davidborge.com)
// MUCHO CUIDADO: si se me olvida poner la extensión del dominio en una de las alternativas, provoca un Error 500.
$http_origin = $_SERVER['HTTP_ORIGIN'];
if ( true )
// if ($http_origin == "http://localhost:4200" || $http_origin == "http://localhost:4000" || $http_origin == "https://online-store.davidborge.com" || $http_origin == "https://online-store-web.herokuapp.com" || $http_origin == "https://online-store-ssr.davidborge.com" || $http_origin == "http://192.168.1.43:4200"  )
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

    // - API Payload
    $authTokenAPIPayload = $jsonUser->token;
    
    // - Comprobar si el authToken ya existe, es decir, si hay un usuario temporal que solo tiene token porque el usuario ha añadido productos al carrito sin iniciar sesión
    $sentencia = $bd->prepare("SELECT token FROM users WHERE token = ?");
    $sentencia->execute([$authTokenAPIPayload]);
    $resultado = $sentencia->fetchObject();
    
    // - Si el token NO existe en la Base de datos (el usuario es totalmente nuevo), crearlo
    if ( $resultado->token == '' ) {
        
        // - Insertar el usuario en la Base de datos
        $sentencia2 = $bd->prepare("INSERT INTO users(firstName, lastName, email, password, signUpFullDate, lastLoginFullDate, token) VALUES (?,?,?,?,?,?,?)");
        $resultado2 = $sentencia2->execute([$jsonUser->firstName, $jsonUser->lastName, $jsonUser->email, $jsonUser->password, $jsonUser->signUpFullDate, $jsonUser->lastLoginFullDate, $authTokenAPIPayload]);

    }
    
    // - Si el token YA existe en la Base de datos (hay un usuario temporal), actualizarlo manteniendo el mismo token, pero añadiendo el resto de datos (nombre, apellidos, email, contraseña y fechas)
    else {

        $sentencia2 = $bd->prepare("UPDATE users SET firstName = ?, lastName = ?, email = ?, password = ?, signUpFullDate = ?, lastLoginFullDate = ? WHERE token = ?");
        $resultado2 = $sentencia2->execute([$jsonUser->firstName, $jsonUser->lastName, $jsonUser->email, $jsonUser->password, $jsonUser->signUpFullDate, $jsonUser->lastLoginFullDate, $authTokenAPIPayload]);
        
    }

    echo json_encode([
        "resultado" => $resultado2,
    ]);

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'hewemim@mailinator.com' for key 'users.email'"
    ]);

}