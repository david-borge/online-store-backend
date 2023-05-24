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
    exit("GET_CURRENT_PRODUCT_REVIEWS_ERROR_API_DID_NOT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {

    // - API Payload (email y contraseña introducidos en el formulario de Log In, lastLoginFullDate y el token (que cambia cada vez que se inicia sesión), que es la fecha actual)
    $currentProductSlugAPIPayload = $jsonAPIPayload->currentProductSlug;
    
    // Sacar de la Base de Datos el product.id correspondiente al currentProductSlug
    $sentencia = $bd->prepare("SELECT id FROM products WHERE slug = ?");
    $sentencia->execute([$currentProductSlugAPIPayload]);
    $resultado = $sentencia->fetchObject();

    // Si recuperar el productId ha ido bien
    if ( $resultado ) {
        $productId = $resultado->id;

        // Leer las review del producto con $productId
        $sentencia2 = $bd->prepare("SELECT reviews.title, reviews.ratingNumber AS starsWidth, reviews.publicationFullDate, reviews.content, CONCAT(users.firstName, ' ', users.lastName) AS fullName FROM reviews, users WHERE reviews.productId = ? AND reviews.userId = users.id");
        $resultado2 = $sentencia2->execute([$productId]);
        $resultado2 = $sentencia2->fetchAll(PDO::FETCH_OBJ);
        
        // Si leer las review del producto con $productId ha ido bien
        if ($resultado2) {

            echo json_encode($resultado2);

        } else {

            echo json_encode([]); // Si el producto no tiene ninguna review, devolver un array vacío (en lugar de null, que sería el valor de $resultado2 si la query no devuelve ninguna review)

        }
        
    } else {
        echo json_encode([
            "resultado" => 'GET_CURRENT_PRODUCT_REVIEWS_ERROR_COULD_NOT_GET_PRODUCT_ID',
        ]);
    }

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'hewemim@mailinator.com' for key 'users.email'"
    ]);

}