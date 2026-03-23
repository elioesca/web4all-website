<?php

namespace App\Controllers;

/**
 * LegalController
 *
 * Contrôleur minimaliste pour afficher la page des mentions légales.
 * Pas de logique métier complexe ici : juste une vue statique.
 */
class LegalController extends Controller
{
    /**
     * index()
     *
     * Affiche la page des mentions légales.
     * Utilise la méthode render héritée pour charger la vue Twig.
     */
    public function index(): void
    {
        $this->render('mentions-legales.html.twig');
    }
}