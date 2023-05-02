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
    exit("LOGIN_ERROR_API_DIDNT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {


    // - Contraseña introducida en el formulario de Log In:
    $passwordFromLogInForm = $jsonAPIPayload->password;

    // Comprobación
    // echo json_encode($passwordFromLogInForm);



    // - Obtener de la Base de Datos la contraseña del usuario con el email introducido en el formulario de Log In
    $sentencia = $bd->prepare("select password from users where email = ?"); // SELECT `password` FROM `users` WHERE `email`='david.borge.olmedo@gmail.com'
    $sentencia->execute([$jsonAPIPayload->email]);
    $resultado = $sentencia->fetchObject();

    // Si el email introducido en el formulario de Log In existe en la Base de Datos, guardar su contraseña en una variable y compruebo si coincide con la de la Base de Datos
    if( $resultado == true ) {

        // Guardar en una variable la contraseña del usuario sacada de la Base de Datos
        $passwordFromDataBase = $resultado->password;
        
        // Comprobación
        /* echo json_encode([
            "resultado" => $passwordFromDataBase,
        ]); */

        // Compruebo si la contraseña del formulario de Log In coincide con la de la Base de Datos
        if ( $passwordFromLogInForm == $passwordFromDataBase) {
            
            echo json_encode([
                "resultado" => true,
            ]);
            
        } else {
            
            echo json_encode([
                "resultado" => 'LOGIN_ERROR_PASSWORD_IS_NOT_CORRECT',
            ]);

        }

    }

    // Si el email introducido en el formulario de Log In NO existe en la Base de Datos, devolver el aviso
    else {
        
        echo json_encode([
            "resultado" => 'LOGIN_ERROR_EMAIL_DOES_NOT_EXIST_IN_THE_DATABASE',
        ]);

    }

    

} catch (Exception $e) {

    // exception is raised and it'll be handled here 
    // $e->getMessage() contains the error message

    echo json_encode([
        "resultado" => $e->getMessage(), // Ejemplo: "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'hewemim@mailinator.com' for key 'users.email'"
    ]);

}