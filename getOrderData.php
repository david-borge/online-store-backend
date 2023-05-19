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
    exit("GET_ORDER_DATA_ERROR_API_DID_NOT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {


    // - API Payload (email y contraseña introducidos en el formulario de Log In, lastLoginFullDate y el token (que cambia cada vez que se inicia sesión), que es la fecha actual)
    $orderNumberAPIPayload = $jsonAPIPayload->orderNumber;

    // Recuperar los datos de la Order
    $sentenciaOrderData = $bd->prepare("SELECT orders.orderFullDate, orders.deliveryFullDate, SUM(products.price * orderProducts.productQuantity) AS orderTotal FROM orders, orderProducts, products WHERE products.id = orderProducts.productId AND orders.id = ? AND orderProducts.orderId = ?");
    $sentenciaOrderData->execute([$orderNumberAPIPayload, $orderNumberAPIPayload]);
    $resultadoOrderData = $sentenciaOrderData->fetchObject();

    $sentenciaOrderProducts = $bd->prepare("SELECT products.imageThumbnail, products.name, products.price, orderProducts.productQuantity FROM products, orderProducts WHERE products.id = orderProducts.productId AND orderProducts.orderId = ?");
    $sentenciaOrderProducts->execute([$orderNumberAPIPayload]);
    $resultadoOrderProducts = $sentenciaOrderProducts->fetchAll(PDO::FETCH_OBJ);

    $sentenciaOrderAddress = $bd->prepare("SELECT addresses.id, addresses.fullName, addresses.address, addresses.postalCode, addresses.city, countries.name AS country FROM addresses, orders, countries WHERE orders.id = ? AND addresses.id = orders.addressId AND countries.id = addresses.countryId GROUP BY fullName");
    $sentenciaOrderAddress->execute([$orderNumberAPIPayload]);
    $resultadoOrderAddress = $sentenciaOrderAddress->fetchObject();

    $sentenciaOrderPaymentMethod = $bd->prepare("SELECT paymentMethods.type, paymentMethods.cardBankName, paymentMethods.cardPersonFullName, RIGHT(paymentMethods.cardNumber, 4) AS cardLastFourNumbers, paymentMethods.cardExpirationMonth, paymentMethods.cardExpirationYear, paymentMethods.cardType FROM paymentMethods, orders WHERE orders.id = ? AND paymentMethods.id = orders.paymentMethodId");
    $sentenciaOrderPaymentMethod->execute([$orderNumberAPIPayload]);
    $resultadoOrderPaymentMethod = $sentenciaOrderPaymentMethod->fetchObject();

    // Si recuperar los datos del usuario ha ido bien
    if ( $resultadoOrderData && $resultadoOrderProducts && $resultadoOrderAddress && $resultadoOrderPaymentMethod ) {

        echo json_encode([
            "resultado"          => true,
            "orderData"          => $resultadoOrderData,
            "orderProducts"      => $resultadoOrderProducts,
            "orderAddress"       => $resultadoOrderAddress,
            "orderPaymentMethod" => $resultadoOrderPaymentMethod,
        ]);

    } else {

        echo json_encode([
            "resultado" => 'GET_ORDER_DATA_ERROR_GET_ORDER_DATA_FAILED',
        ]);

    }

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: -
    ]);

}