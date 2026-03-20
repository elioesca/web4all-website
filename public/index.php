<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$routes = require __DIR__ . '/../config/routes.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

if (isset($routes[$method][$uri])) {
    [$controllerClass, $action] = $routes[$method][$uri];

    $controller = new $controllerClass();
    $controller->$action();
    exit;
}

http_response_code(404);

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../views');
$twig = new \Twig\Environment($loader, ['cache' => false]);

echo $twig->render('404.html.twig', [
    'user' => $_SESSION['user'] ?? null
]);