<?php

namespace App\Controllers;

/**
 * HomeController
 *
 * Contrôleur principal de l'application pour la page d'accueil.
 * Pour l'instant, il n’y a pas de logique métier, juste l'affichage de la vue.
 */
class HomeController extends Controller
{
    /**
     * index()
     *
     * Affiche la page d'accueil.
     * Utilise la méthode render héritée du contrôleur de base.
     */
    public function index(): void
    {
        $this->render('home.html.twig');
    }
}