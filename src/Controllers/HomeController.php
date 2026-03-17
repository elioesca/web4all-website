<?php

namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class HomeController
{
    private Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../views');

        $this->twig = new Environment($loader, [
            'cache' => false,
        ]);
    }

    public function index(): void
    {
        echo $this->twig->render('home.html.twig', [
            'user' => $_SESSION['user'] ?? null
        ]);
    }
}