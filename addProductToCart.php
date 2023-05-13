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
    exit("ADD_PRODUCT_TO_CART_ERROR_API_DID_NOT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {

    // - API Payload (email y contraseña introducidos en el formulario de Log In, lastLoginFullDate y el token (que cambia cada vez que se inicia sesión), que es la fecha actual)
    $authTokenAPIPayload = $jsonAPIPayload->authToken;
    $productSlugAPIPayload = $jsonAPIPayload->productSlug;
    
    // Sacar de la Base de Datos el userId correspondiente al authToken
    $sentencia = $bd->prepare("SELECT id FROM users WHERE token = ?");
    $sentencia->execute([$authTokenAPIPayload]);
    $resultado = $sentencia->fetchObject();
    
    // Sacar de la Base de Datos el product.id correspondiente al productSlug
    $sentencia2 = $bd->prepare("SELECT id FROM products WHERE slug = ?");
    $sentencia2->execute([$productSlugAPIPayload]);
    $resultado2 = $sentencia2->fetchObject();

    // Si recuperar el userId ha ido bien
    if ( $resultado && $resultado2 ) {
        $userId    = $resultado->id;
        $productId = $resultado2->id;

        // Añadir el producto al carrito
        $sentencia3 = $bd->prepare("INSERT INTO cart(userId, productId, productQuantity) VALUES (?, ?, 1)"); // Por ahora, cuando añado un producto al carrito siempre es una unidad
        $resultado3 = $sentencia3->execute([$userId, $productId]);
        
        // Si el añadir la nueva address ha ido bien
        if ($resultado3) {

            echo json_encode($resultado3);

        } else {
            echo json_encode([
                "resultado" => 'ADD_PRODUCT_TO_CART_ERROR_COULD_NOT_ADD_PRODUCT_TO_CART',
            ]);
        }
        
    } else {
        echo json_encode([
            "resultado" => 'ADD_PRODUCT_TO_CART_ERROR_COULD_NOT_GET_USER_ID',
        ]);
    }

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'hewemim@mailinator.com' for key 'users.email'"
    ]);

}