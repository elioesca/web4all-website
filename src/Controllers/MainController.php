<?php

namespace App\Controllers;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class MainController
{
    private $twig;

    public function __construct()
    {
        // 1. On indique à Twig où se trouvent nos fichiers .html.twig
        $loader = new FilesystemLoader(__DIR__ . '/../../views');
        
        // 2. On initialise l'environnement Twig
        $this->twig = new Environment($loader, [
            // 'cache' => __DIR__ . '/../../var/cache', // (Désactivé pour le moment pour voir les modifs en direct)
            'cache' => false,
        ]);
    }

    public function home()
    {
        // On demande à Twig de générer le fichier et on l'affiche avec echo
        echo $this->twig->render('home.html.twig');
    }
}