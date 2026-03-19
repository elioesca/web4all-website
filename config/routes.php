<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\ProfileController;
use App\Controllers\StudentController;

return [
    'GET' => [
        '/' => [HomeController::class, 'index'],
        '/login' => [AuthController::class, 'showLogin'],
        '/logout' => [AuthController::class, 'logout'],
        '/forgot-password' => [AuthController::class, 'showForgotPassword'],
        '/dashboard' => [DashboardController::class, 'index'],
        '/profile' => [ProfileController::class, 'index'],

        '/students' => [StudentController::class, 'index'],
        '/students/create' => [StudentController::class, 'create'],
        '/students/edit' => [StudentController::class, 'edit'],
        '/pilot/student-applications' => [StudentController::class, 'pilotStudentApplications'],
    ],

    'POST' => [
        '/login' => [AuthController::class, 'login'],
        '/forgot-password' => [AuthController::class, 'forgotPassword'],
        '/profile' => [ProfileController::class, 'update'],
        '/profile/delete' => [ProfileController::class, 'delete'],

        '/students/create' => [StudentController::class, 'store'],
        '/students/edit' => [StudentController::class, 'update'],
        '/students/deactivate' => [StudentController::class, 'deactivate'],
    ],
];