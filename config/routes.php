<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\ProfileController;
use App\Controllers\StudentController;
use App\Controllers\PilotController;
use App\Controllers\CompanyController;
use App\Controllers\OfferController;
use App\Controllers\ApplicationController;
use App\Controllers\WishlistController;

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

        '/wishlist' => [WishlistController::class, 'index'],

        '/applications' => [StudentController::class, 'applications'],
        '/applications/create' => [ApplicationController::class, 'create'],

        '/pilot/student-applications' => [StudentController::class, 'pilotStudentApplications'],
        '/pilots' => [PilotController::class, 'index'],
        '/pilots/create' => [PilotController::class, 'create'],
        '/pilots/edit' => [PilotController::class, 'edit'],

        '/companies' => [CompanyController::class, 'index'],
        '/companies/show' => [CompanyController::class, 'show'],
        '/companies/create' => [CompanyController::class, 'create'],
        '/companies/edit' => [CompanyController::class, 'edit'],

        '/offers' => [OfferController::class, 'index'],
        '/offers/show' => [OfferController::class, 'show'],
        '/offers/create' => [OfferController::class, 'create'],
        '/offers/edit' => [OfferController::class, 'edit'],
    ],

    'POST' => [
        '/login' => [AuthController::class, 'login'],
        '/forgot-password' => [AuthController::class, 'forgotPassword'],
        '/profile' => [ProfileController::class, 'update'],
        '/profile/delete' => [ProfileController::class, 'delete'],

        '/students/create' => [StudentController::class, 'store'],
        '/students/edit' => [StudentController::class, 'update'],
        '/students/deactivate' => [StudentController::class, 'deactivate'],
        '/students/reactivate' => [StudentController::class, 'reactivate'],

        '/wishlist/toggle' => [WishlistController::class, 'toggle'],

        '/pilots/create' => [PilotController::class, 'store'], 
        '/pilots/edit' => [PilotController::class, 'update'],
        '/pilots/deactivate' => [PilotController::class, 'deactivate'],
        '/pilots/reactivate' => [PilotController::class, 'reactivate'],

        '/companies/create' => [CompanyController::class, 'store'],
        '/companies/edit' => [CompanyController::class, 'update'],
        '/companies/deactivate' => [CompanyController::class, 'deactivate'],
        '/companies/review' => [CompanyController::class, 'review'],

        '/offers/create' => [OfferController::class, 'store'],
        '/offers/edit' => [OfferController::class, 'update'],
        '/offers/deactivate' => [OfferController::class, 'deactivate'],

        '/applications/create' => [ApplicationController::class, 'store'],
    ],
];