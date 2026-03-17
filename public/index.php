<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
//chargement de l'autoloader de composer
require_once __DIR__.'/../vendor/autoload.php';

//lire l'uri
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

//instancier le contrôleur
$controller = new \App\Controllers\MainController();

//routing
switch ($uri) {

    case '/':
        $controller->home();
        break;

    default:
        http_response_code(404);
        echo "Page not found";
        break;
}

?>