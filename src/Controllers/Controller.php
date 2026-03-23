<?php

namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Controller
 *
 * Classe de base pour tous les contrôleurs de l'application.
 * Fournit des utilitaires communs : initialisation Twig, rendu de vues,
 * redirection et vérification de l'authentification.
 */
class Controller
{
    // Instance de l'environnement Twig pour l'affichage des templates
    protected Environment $twig;

    public function __construct()
    {
        // Configure le loader Twig pour utiliser le dossier views
        $loader = new FilesystemLoader(__DIR__ . '/../../views');

        // Crée l'environnement Twig sans cache pour le développement
        $this->twig = new Environment($loader, [
            'cache' => false,
        ]);
    }

    /**
     * render()
     *
     * Rend une vue Twig en y injectant les données passées ainsi que l'utilisateur connecté.
     *
     * @param string $view Le chemin du template Twig
     * @param array $data Données à injecter dans le template
     */
    protected function render(string $view, array $data = []): void
    {
        // Ajoute l'utilisateur en session aux données de rendu (si connecté)
        $data['user'] = $_SESSION['user'] ?? null;

        // Affiche le HTML généré par Twig
        echo $this->twig->render($view, $data);
    }

    /**
     * redirect()
     *
     * Redirige l'utilisateur vers une URL donnée avec un en-tête HTTP.
     *
     * @param string $url URL de destination
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * requireLogin()
     *
     * Vérifie si l'utilisateur est connecté, sinon redirige vers la page de connexion.
     */
    protected function requireLogin(): void
    {
        if (empty($_SESSION['user'])) {
            $this->redirect('/login');
        }
    }
}