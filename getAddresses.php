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
    exit("GET_ADDRESSES_DATA_ERROR_API_DID_NOT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {


    // - API Payload (email introducido en el formulario de Log In)
    $authTokenAPIPayload = $jsonAPIPayload->authToken;

    // Sacar de la Base de Datos el userId correspondiente al authToken
    $sentencia = $bd->prepare("SELECT id FROM users WHERE token = ?");
    $sentencia->execute([$authTokenAPIPayload]);
    $resultado = $sentencia->fetchObject();

    // Si recuperar el userId ha ido bien
    if ( $resultado ) {
        $userId = $resultado->id;
        
        // Recuperar: direcciones del usuario $userId
        $sentencia2 = $bd->prepare("SELECT addresses.id, addresses.fullName, addresses.address, addresses.postalCode, addresses.city, countries.name AS country, addresses.isDefault FROM addresses, countries WHERE addresses.userId = ? AND countries.id = addresses.countryId ORDER BY addresses.id DESC");
        $sentencia2->execute([$userId]);
        $resultado2 = $sentencia2->fetchAll(PDO::FETCH_OBJ);

        // Si se ha encontrado alguna address
        if ( $resultado2 ) {

            echo json_encode([
                "resultado" => true,
                "addresses" => $resultado2,
            ]);

        // Si NO se ha encontrado algún pedido, devolver un array vacío (porque el valor por defecto de $resultado es null)
        } else {

            echo json_encode([
                "resultado" => true,
                "addresses" => [],
            ]);

        }

    } else {

        echo json_encode([
            "resultado" => 'GET_ADDRESSES_DATA_ERROR_COULD_NOT_FIND_USER_ID',
        ]);

    }

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: -
    ]);

}