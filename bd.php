<?php

$contraseña = "AW%@YR$#KEefFVez)4g#pfy*4hZ";
$usuario = "dbu2528413";
$nombre_base_de_datos = "dbs10571145";
try {
    return new PDO('mysql:host=db5012575226.hosting-data.io;dbname=' . $nombre_base_de_datos, $usuario, $contraseña);
} catch (Exception $e) {
    echo "Ocurrió algo con la base de datos: " . $e->getMessage();
}
