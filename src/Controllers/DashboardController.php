<?php

namespace App\Controllers;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $user = $_SESSION['user'];
        $role = $user['role'];

        $cards = [];

        if ($role === 'student') {
            $cards = [
                [
                    'title' => 'Mes candidatures',
                    'url' => '/applications',
                    'color' => 'red'
                ],
                [
                    'title' => 'Wish-list',
                    'url' => '/wishlist',
                    'color' => 'yellow'
                ],
                [
                    'title' => 'Modifier mes informations',
                    'url' => '/profile',
                    'color' => 'blue'
                ],
            ];
        }

        if ($role === 'pilot') {
            $cards = [
                [
                    'title' => 'Gestion des entreprises',
                    'url' => '/companies',
                    'color' => 'red'
                ],
                [
                    'title' => 'Gestion des offres de stages',
                    'url' => '/offers',
                    'color' => 'yellow'
                ],
                [
                    'title' => 'Gestion des étudiants',
                    'url' => '/students',
                    'color' => 'blue'
                ],
                [
                    'title' => 'Modifier mes informations',
                    'url' => '/profile',
                    'color' => 'purple'
                ],
            ];
        }

        if ($role === 'admin') {
            $cards = [
                [
                    'title' => 'Gestion des entreprises',
                    'url' => '/companies',
                    'color' => 'red'
                ],
                [
                    'title' => 'Gestion des offres de stages',
                    'url' => '/offers',
                    'color' => 'yellow'
                ],
                [
                    'title' => 'Gestion des étudiants',
                    'url' => '/students',
                    'color' => 'blue'
                ],
                [
                    'title' => 'Gestion des pilotes',
                    'url' => '/pilots',
                    'color' => 'pink'
                ],
                [
                    'title' => 'Modifier mes informations',
                    'url' => '/profile',
                    'color' => 'purple'
                ],
            ];
        }

        $this->render('dashboard.html.twig', [
            'cards' => $cards
        ]);
    }
}