<?php

namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Controller
{
    protected Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../views');

        $this->twig = new Environment($loader, [
            'cache' => false,
        ]);
    }

    protected function render(string $view, array $data = []): void
    {
        $data['user'] = $_SESSION['user'] ?? null;
        echo $this->twig->render($view, $data);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function requireLogin(): void
    {
        if (empty($_SESSION['user'])) {
            $this->redirect('/login');
        }
    }
}