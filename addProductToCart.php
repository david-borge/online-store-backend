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

    $productId = $resultado2->id;

    // Si el userId existe (el usuario ha iniciado sesión)
    if ( $resultado ) {
        
        // Leer el id del usuario
        $userId = $resultado->id;

        anadirProductAlCarrito($bd, $userId, $productId);

    }

    // Si el userId NO existe (el usuario NO ha iniciado sesión)
    else {

        // Crear un usuario temporal con token y sin firstName, sin lastName, sin email, sin password, sin signUpFullDate y sin lastLoginFullDate
        $sentencia3 = $bd->prepare("INSERT INTO users(firstName, lastName, email, password, signUpFullDate, lastLoginFullDate, token) VALUES ('', '', ?, '', '', '', ?)"); // Meto temporalmente el token como el email para que no me de el error "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '' for key 'users.email'"
        $resultado3 = $sentencia3->execute([$authTokenAPIPayload, $authTokenAPIPayload]);
        echo json_encode($resultado3);

        // Sacar el ID del usuario temporal creado
        $userId = $bd->query("SELECT LAST_INSERT_ID()");

        anadirProductAlCarrito($bd, $userId, $productId);
        
    }

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'hewemim@mailinator.com' for key 'users.email'"
    ]);

}



function anadirProductAlCarrito($bd, $userId, $productId) {
    
    // Comprobar si el usuario con $userId ya ha añadido el producto con $productId al carrito
    $sentencia3 = $bd->prepare("SELECT cart.productQuantity FROM cart WHERE cart.userId = ? AND cart.productId = ?");
    $resultado3 = $sentencia3->execute([$userId, $productId]);
    $resultado3 = $sentencia3->fetchObject();

    // Si el usuario NO había añadido el producto al carrito, añadir el producto al carrito
    if (!$resultado3) { // $resultado3 no existe (es null)
        
        $sentencia4 = $bd->prepare("INSERT INTO cart(userId, productId, productQuantity) VALUES (?, ?, 1)"); // Por ahora, cuando añado un producto al carrito siempre es una unidad
        $resultado4 = $sentencia4->execute([$userId, $productId]);

        // Si el añadir el producto al carrito ha ido bien
        if ($resultado4) {

            echo json_encode($resultado4);

        } else {

            echo json_encode([
                "resultado" => 'ADD_PRODUCT_TO_CART_ERROR_COULD_NOT_ADD_PRODUCT_TO_CART',
            ]);
            
        }
        
    }
    
    // Si el usuario SÍ había añadido el producto al carrito, aumentar su cantidad en 1
    else {

        $sentencia4 = $bd->prepare("UPDATE cart SET productQuantity = (? + 1) WHERE cart.userId = ? AND cart.productId = ?");
        $resultado4 = $sentencia4->execute([$resultado3->productQuantity, $userId, $productId]);
        
        // Si el aumentar la cantidad del producto en 1 ha ido bien
        if ($resultado4) {

            echo json_encode($resultado4);

        } else {

            echo json_encode([
                "resultado" => 'ADD_PRODUCT_TO_CART_ERROR_COULD_NOT_UPDATE_PRODUCT_QUANTITY',
            ]);
            
        }

    }

}