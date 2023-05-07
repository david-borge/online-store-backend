<?php
// Prevenir que falle la llamada a la API por la CORS Policy porque la web está en un dominio (https://online-store.davidborge.com o http://localhost:4200) y la API en otro (davidborge.com)
// MUCHO CUIDADO: si se me olvida poner la extensión del dominio en una de las alternativas, provoca un Error 500.
$http_origin = $_SERVER['HTTP_ORIGIN'];
if ($http_origin == "http://localhost:4200" || $http_origin == "http://localhost:4000" || $http_origin == "https://online-store.davidborge.com" || $http_origin == "https://online-store-web.herokuapp.com" || $http_origin == "https://online-store-ssr.davidborge.com" )
{  
    header("Access-Control-Allow-Origin: $http_origin");
}

header("Access-Control-Allow-Headers: *");

$jsonAPIPayload = json_decode(file_get_contents("php://input"));
if (!$jsonAPIPayload) {
    exit("GET_ORDERS_DATA_ERROR_API_DID_NOT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {


    // - API Payload (email introducido en el formulario de Log In)
    $emailFromLogInForm = $jsonAPIPayload->email;

    // Recuperar: datos necesarios para Active Orders (imageThumbnail, orderTotal, deliveryFullDate)
    $sentencia = $bd->prepare("SELECT orders.id, products.imageThumbnail, SUM(products.price * orderProducts.productQuantity) AS orderTotal, orders.deliveryFullDate, orders.active FROM orders, orderProducts, products, users WHERE orders.id = orderId AND products.id = productId AND users.email = ? GROUP BY orders.id");
    $sentencia->execute([$emailFromLogInForm]);
    $resultado = $sentencia->fetchAll(PDO::FETCH_OBJ);

    // Si recuperar los datos del usuario ha ido bien
    if ( $resultado ) {

        echo json_encode([
            "resultado" => true,
            "orders"    => $resultado,
        ]);

        // Nota $resultado es false si no hay ninguna Active Order

    } else {

        echo json_encode([
            "resultado" => 'GET_ORDERS_DATA_ERROR_GET_ORDERS_FAILED',
        ]);

    }

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: -
    ]);

}