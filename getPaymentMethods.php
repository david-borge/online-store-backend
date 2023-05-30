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
    exit("GET_PAYMENT_METHODS_DATA_ERROR_API_DID_NOT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {


    // - API Payload (authToken cookie value)
    $authTokenAPIPayload = $jsonAPIPayload->authToken;

    // Sacar de la Base de Datos el userId correspondiente al authToken
    $sentencia = $bd->prepare("SELECT id FROM users WHERE token = ?");
    $sentencia->execute([$authTokenAPIPayload]);
    $resultado = $sentencia->fetchObject();

    // Si recuperar el userId ha ido bien
    if ( $resultado ) {
        $userId = $resultado->id;

        // Recuperar: direcciones del usuario con email $emailFromLogInForm
        $sentencia = $bd->prepare("SELECT paymentMethods.id, paymentMethods.type, paymentMethods.cardBankName, paymentMethods.cardPersonFullName, RIGHT(paymentMethods.cardNumber, 4) AS cardLastFourNumbers, paymentMethods.cardExpirationMonth, paymentMethods.cardExpirationYear, paymentMethods.cardType, paymentMethods.isDefault FROM paymentMethods WHERE paymentMethods.userId = ?");
        $sentencia->execute([$userId]);
        $resultado = $sentencia->fetchAll(PDO::FETCH_OBJ);

        // Si se ha encontrado algún payment method
        if ( $resultado ) {

            echo json_encode([
                "resultado" => true,
                "paymentMethods" => $resultado,
            ]);

        // Si NO se ha encontrado algún pedido, devolver un array vacío (porque el valor por defecto de $resultado es null)
        } else {

            echo json_encode([
                "resultado" => true,
                "paymentMethods" => [],
            ]);

        }
    
    }

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: -
    ]);

}