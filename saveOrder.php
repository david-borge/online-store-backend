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
    exit("SAVE_ORDER_ERROR_API_DID_NOT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {

    // - API Payload
    $authTokenAPIPayload          = $jsonAPIPayload->authToken;
    $orderFullDateAPIPayload      = $jsonAPIPayload->orderFullDate;
    $deliveryFullDateAPIPayload   = $jsonAPIPayload->deliveryFullDate;
    $addressIdAPIPayload          = $jsonAPIPayload->addressId;
    $paymentMethodIdAPIPayload    = $jsonAPIPayload->paymentMethodId;
    $orderProductsDataAPIPayload  = $jsonAPIPayload->orderProductsData;
    
    // Sacar de la Base de Datos el userId correspondiente al authToken
    $sentencia = $bd->prepare("SELECT id FROM users WHERE token = ?");
    $sentencia->execute([$authTokenAPIPayload]);
    $resultado = $sentencia->fetchObject();

    // Si recuperar el userId ha ido bien
    if ( $resultado ) {
        $userId = $resultado->id;

        // Guardar la nueva Order en la Base de Datos
        $sentencia2 = $bd->prepare("INSERT INTO orders(userId, orderFullDate, deliveryFullDate, addressId, paymentMethodId, active) VALUES (?, ?, ?, ?, ?, ?)");
        $resultado2 = $sentencia2->execute([$userId, $orderFullDateAPIPayload, $deliveryFullDateAPIPayload, $addressIdAPIPayload, $paymentMethodIdAPIPayload, 1]); // 1: todas las Orders nuevas empiezan como activas (no entregadas todavía)

        // Leer el id de la order insertada (el último id insertado en orders)
        $sentencia3 = $bd->query("SELECT LAST_INSERT_ID()");
        $orderId = $sentencia3->fetchColumn();

        // Guardar la orderProductsData en la Base de Datos
        $resultado4IsOK = true;
        foreach($orderProductsDataAPIPayload as $key => $orderProductData ) {

            $sentencia4 = $bd->prepare("INSERT INTO orderProducts(orderId, productId, productQuantity) VALUES (?, ?, ?)");
            $resultado4 = $sentencia4->execute([$orderId, $orderProductData->productId, $orderProductData->productQuantity]);

            if (!$resultado4) {
                $resultado4IsOK = false;
                break;
            }

        }

        // Si el añadir la nueva Order ha ido bien, delete products from cart when Order is saved
        if ($resultado2 && $resultado4IsOK) {
        // if ($resultado2 && $resultado4IsOK) {

            // Delete products from cart when Order is saved
            $sentencia5 = $bd->prepare("DELETE FROM cart WHERE cart.userId = ?");
            $resultado5 = $sentencia5->execute([$userId]);
            
            if ($resultado5) {

                echo json_encode($resultado5);
    
            } else {
                echo json_encode([
                    "resultado" => 'SAVE_ORDER_COULD_NOT_DELETE_CART_PRODUCTS',
                ]);
            }

        } else {
            echo json_encode([
                "resultado" => 'SAVE_ORDER_COULD_NOT_SAVE_ORDER',
            ]);
        }
        
    } else {
        echo json_encode([
            "resultado" => 'SAVE_ORDER_COULD_NOT_GET_USER_ID',
        ]);
    }

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'hewemim@mailinator.com' for key 'users.email'"
    ]);

}