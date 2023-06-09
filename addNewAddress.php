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

$jsonAPIPayload = json_decode(file_get_contents("php://input"));
if (!$jsonAPIPayload) {
    exit("ADD_NEW_ADDRESS_ERROR_API_DID_NOT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {

    // - API Payload (email y contraseña introducidos en el formulario de Log In, lastLoginFullDate y el token (que cambia cada vez que se inicia sesión), que es la fecha actual)
    $newAddressAPIPayload = $jsonAPIPayload->newAddress;
    $authTokenAPIPayload = $jsonAPIPayload->authToken;
    
    // Sacar de la Base de Datos el userId correspondiente al authToken
    $sentencia = $bd->prepare("SELECT id FROM users WHERE token = ?");
    $sentencia->execute([$authTokenAPIPayload]);
    $resultado = $sentencia->fetchObject();

    // Si recuperar el userId ha ido bien
    if ( $resultado ) {
        $userId = $resultado->id;

        // Guardar la nueva address en la Base de Datos (como default)
        $sentencia2 = $bd->prepare("INSERT INTO addresses(userId, fullName, address, postalCode, city, countryId, isDefault) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $resultado2 = $sentencia2->execute([$userId, $newAddressAPIPayload->fullName, $newAddressAPIPayload->address, $newAddressAPIPayload->postalCode, $newAddressAPIPayload->city, $newAddressAPIPayload->countryId]);
        
        // Leer el id de la address insertada (el último id insertado en addresses)
        $sentencia3 = $bd->query("SELECT LAST_INSERT_ID()");
        $newAddressId = $sentencia3->fetchColumn();

        // Poner el isDefault a 0 de las Addresses con id que NO sea $newAddressId del usuario correspondiente en la Base de Datos
        $sentencia4 = $bd->prepare("UPDATE addresses SET addresses.isDefault = 0 WHERE addresses.id != ? AND addresses.userId = ?");
        $resultado4 = $sentencia4->execute([$newAddressId, $userId]);
        
        // Si el añadir la nueva address ha ido bien
        if ($resultado2 && $sentencia4) {

            echo json_encode($newAddressId);

        } else {
            echo json_encode([
                "resultado" => 'ADD_NEW_ADDRESS_COULD_NOT_ADD_NEW_ADDRESS_OR_SET_THE_REST_TO_NOT_DEFAULT',
            ]);
        }
        
    } else {
        echo json_encode([
            "resultado" => 'ADD_NEW_ADDRESS_COULD_NOT_GET_USER_ID',
        ]);
    }

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'hewemim@mailinator.com' for key 'users.email'"
    ]);

}