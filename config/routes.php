<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\StudentController;
use App\Controllers\DashboardController;

return [
    'GET' => [
        '/' => [HomeController::class, 'index'],
        '/login' => [AuthController::class, 'showLogin'],
        '/logout' => [AuthController::class, 'logout'],
        '/forgot-password' => [AuthController::class, 'showForgotPassword'],
        '/dashboard' => [DashboardController::class, 'index'],
    ],

    'POST' => [
        '/login' => [AuthController::class, 'login'],
        '/forgot-password' => [AuthController::class, 'forgotPassword'],
    ],
];