<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\StudentController;
use App\Controllers\DashboardController;
use App\Controllers\ProfileController;

return [
    'GET' => [
        '/' => [HomeController::class, 'index'],
        '/login' => [AuthController::class, 'showLogin'],
        '/logout' => [AuthController::class, 'logout'],
        '/forgot-password' => [AuthController::class, 'showForgotPassword'],
        '/dashboard' => [DashboardController::class, 'index'],
        '/profile' => [ProfileController::class, 'index'],
    ],

    'POST' => [
        '/login' => [AuthController::class, 'login'],
        '/forgot-password' => [AuthController::class, 'forgotPassword'],
        '/profile' => [ProfileController::class, 'update'],
        '/profile/delete' => [ProfileController::class, 'delete']
    ],
];