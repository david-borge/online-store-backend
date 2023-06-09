<?php
// Prevenir que falle la llamada a la API por la CORS Policy porque la web está en un dominio (https://online-store.davidborge.com o http://localhost:4200) y la API en otro (davidborge.com)
// MUCHO CUIDADO: si se me olvida poner la extensión del dominio en una de las alternativas, provoca un Error 500.
$http_origin = $_SERVER['HTTP_ORIGIN'];
if ( true )
// if ($http_origin == "http://localhost:4200" || $http_origin == "http://localhost:4000" || $http_origin == "https://online-store.davidborge.com" || $http_origin == "https://online-store-web.herokuapp.com" || $http_origin == "https://online-store-ssr.davidborge.com" || $http_origin == "http://192.168.1.43:4200"  )
{  
    header("Access-Control-Allow-Origin: $http_origin");
}

$bd = include_once "bd.php";

// En esta sentencia hago una unión: (productos que tienen reseñas) UNION (productos que no tienen reseñas)
$sentencia = $bd->query("(SELECT products.id, products.slug, products.name, products.manufacturer, products.price, products.descripcion, products.category, products.imageThumbnail, products.imageFull, products.imageWidth, products.imageHeight, ( ((SUM(reviews.ratingNumber) / COUNT(reviews.ratingNumber)) * 5) / 100 ) AS ratingNumber, products.cardAndHeaderBackgroundColor, products.featured, products.deal FROM products, reviews WHERE products.id = reviews.productId GROUP BY products.id) UNION (SELECT products.id, products.slug, products.name, products.manufacturer, products.price, products.descripcion, products.category, products.imageThumbnail, products.imageFull, products.imageWidth, products.imageHeight, NULL AS ratingNumber, products.cardAndHeaderBackgroundColor, products.featured, products.deal FROM products, reviews WHERE products.id NOT IN (SELECT reviews.productId FROM reviews) GROUP BY products.id)");

// $sentencia = $bd->query("SELECT * FROM products");

$products = $sentencia->fetchAll(PDO::FETCH_OBJ);
echo json_encode($products);