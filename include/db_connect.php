<?php

require_once __DIR__.'/string_vars.php';

$dsn = 'mysql:host='.DB_SERVER.';'
        .'port='.DB_SERVER_PORT.';'
        .'dbname='.DB_DATABASE;

try {
    $db = new PDO($dsn, DB_USER, DB_PASSWORD);
} catch (PDOException $ex) {
    $error_message = $ex->getMessage();
    include('./v1/database_error.php');
    exit();
}


?>