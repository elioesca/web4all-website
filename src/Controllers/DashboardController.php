<?php

namespace App\Controllers;

/**
 * DashboardController
 *
 * Contrôleur qui génère les cartes du tableau de bord (dashboard) selon le rôle de l'utilisateur.
 * Chaque rôle (student/pilot/admin) a un ensemble de liens accessibles.
 */
class DashboardController extends Controller
{
    /**
     * index()
     *
     * Affiche le tableau de bord adapté à l'utilisateur connecté.
     * Vérifie d'abord la connexion, puis en fonction du rôle construit un tableau de cartes.
     */
    public function index(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Récupère les infos de l'utilisateur depuis la session
        $user = $_SESSION['user'];
        $role = $user['role'];

        // Initialisation du jeu de cartes à afficher sur le dashboard
        $cards = [];

        // Dashboard pour étudiant
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
                ]
            ];
        }

        // Dashboard pour pilot
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
                ]
            ];
        }

        // Dashboard pour admin
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

        // Passe les cartes à la vue pour affichage
        $this->render('dashboard.html.twig', [
            'cards' => $cards
        ]);
    }
}