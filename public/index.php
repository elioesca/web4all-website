<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$homeController = new \App\Controllers\HomeController();
$authController = new \App\Controllers\AuthController();

switch ($uri) {
    case '/':
        $homeController->index();
        break;

    case '/login':
        if ($method === 'GET') {
            $authController->showLogin();
        } elseif ($method === 'POST') {
            $authController->login();
        }
        break;

    case '/logout':
        $authController->logout();
        break;

    case '/forgot-password':
        if ($method === 'GET') {
            $authController->showForgotPassword();
        } elseif ($method === 'POST') {
            $authController->forgotPassword();
        }
        break;

    default:
        http_response_code(404);
        echo (new \Twig\Environment(
            new \Twig\Loader\FilesystemLoader(__DIR__ . '/../views'),
            ['cache' => false]
        ))->render('404.html.twig', [
            'user' => $_SESSION['user'] ?? null
        ]);
        break;
}