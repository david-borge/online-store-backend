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
    exit("LOGIN_ERROR_API_DID_NOT_RECIEVE_ITS_PAYLOAD");
}
$bd = include_once "bd.php";

try {


    // - API Payload (email y contraseña introducidos en el formulario de Log In, lastLoginFullDate y el token (que cambia cada vez que se inicia sesión), que es la fecha actual)
    $emailFromLogInForm = $jsonAPIPayload->email;
    $passwordFromLogInForm = $jsonAPIPayload->password;
    $lastLoginFullDate = $jsonAPIPayload->lastLoginFullDate;
    $token = $jsonAPIPayload->token;

    // Comprobación
    // echo json_encode($passwordFromLogInForm);



    // Obtener de la Base de Datos la contraseña y el token del usuario con el email introducido en el formulario de Log In
    $sentencia = $bd->prepare("select password, token from users where email = ?"); // SELECT `password` FROM `users` WHERE `email`='david.borge.olmedo@gmail.com'
    $sentencia->execute([$jsonAPIPayload->email]);
    $resultado = $sentencia->fetchObject();

    // Si el email introducido en el formulario de Log In existe en la Base de Datos, guardar su contraseña en una variable y compruebo si coincide con la de la Base de Datos
    if( $resultado == true ) {

        // Guardar en una variable la contraseña y el token del usuario sacada de la Base de Datos
        $passwordFromDataBase = $resultado->password;
        $tokenFromDataBase    = $resultado->token;
        
        // Comprobación
        /* echo json_encode([
            "resultado" => $passwordFromDataBase,
        ]); */

        // Compruebo si estoy iniciando sesión desde el formulario ($passwordFromLogInForm != '') o estoy haciendo auto log in (con los datos de las cookies authEmail y authToken que recibo aquí como Payload de la API en $emailFromLogInForm y en $token)

        // - Si estoy iniciando sesión desde el formulario
        if ( $passwordFromLogInForm != '' ) {
            
            // Compruebo si la contraseña del formulario de Log In coincide con la de la Base de Datos
            if ( $passwordFromLogInForm == $passwordFromDataBase) {

                // Actualizo en la Base de Datos el lastLoginFullDate y el token (que cambia cada vez que se inicia sesión) del email $emailFromLogInForm
                $sentencia2 = $bd->prepare("UPDATE users SET lastLoginFullDate = ?, token = ? WHERE email = ?");
                $resultado2 = $sentencia2->execute([$lastLoginFullDate, $token, $emailFromLogInForm]);

                // Si la actualización de lastLoginFullDate y del token (que cambia cada vez que se inicia sesión) ha ido bien, recupero los datos del usuario con email $emailFromLogInForm
                if ( $resultado2 ) {

                    // Los datos del usuario a recuperar y devolver son: firstName, lastName y active orders
                    $sentencia3 = $bd->prepare("SELECT firstName, lastName FROM users WHERE email = ?");
                    $sentencia3->execute([$emailFromLogInForm]);
                    $resultado3 = $sentencia3->fetchObject();

                    // Si recuperar los datos del usuario ha ido bien
                    if ( $resultado3 ) {

                        echo json_encode([
                            "resultado" => true,
                            "firstName" => $resultado3->firstName,
                            "lastName"  => $resultado3->lastName,
                        ]);

                        // echo json_encode([
                        //     "resultado" => $resultado3, // Esto ya incluye si es true o false, firstName, lastName
                        // ]);

                    } else {

                        echo json_encode([
                            "resultado" => 'LOGIN_ERROR_GET_USER_DATA_FAILED',
                        ]);

                    }
                    
                } else {

                    echo json_encode([
                        "resultado" => 'LOGIN_ERROR_LASTLOGINFULLDATE_UPDATE_FAILED',
                    ]);
                    
                }
                
            } else {
                
                echo json_encode([
                    "resultado" => 'LOGIN_ERROR_PASSWORD_IS_NOT_CORRECT',
                ]);

            }

        }
        
        // - Si estoy haciendo auto log in (con los datos de las cookies authEmail y authToken que recibo aquí como Payload de la API en $emailFromLogInForm y en $token)
        else if ( $token != '' ) {
            
            // /////
            // Compruebo si el token de la cookie authToken coincide con el token del correo $emailFromLogInForm en la Base de Datos
            if ( $token == $tokenFromDataBase) {

                echo json_encode([
                    "resultado" => true,
                ]);
                
            } else {
                
                echo json_encode([
                    "resultado" => 'LOGIN_ERROR_TOKEN_IS_NOT_CORRECT',
                ]);

            }
            // /////
            
        }

        else {

            echo json_encode([
                "resultado" => 'LOGIN_ERROR_API_DID_NOT_RECIEVE_THE_EMAIL_OR_THE_TOKEN_IN_THE_PAYLOAD',
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