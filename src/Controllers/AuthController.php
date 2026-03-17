<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class AuthController
{
    private Environment $twig;
    private PDO $pdo;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../views');

        $this->twig = new Environment($loader, [
            'cache' => false,
        ]);

        $this->pdo = Database::getConnection();
    }

    public function showLogin(): void
    {
        echo $this->twig->render('login.html.twig', [
            'user' => $_SESSION['user'] ?? null,
            'error' => null
        ]);
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            echo $this->twig->render('login.html.twig', [
                'user' => $_SESSION['user'] ?? null,
                'error' => 'Veuillez remplir tous les champs.'
            ]);
            return;
        }

        $sql = "
            SELECT u.user_id, u.first_name, u.last_name, u.email, u.password_hash, r.name AS role
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE u.email = :email AND u.is_valid = 1
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            echo $this->twig->render('login.html.twig', [
                'user' => $_SESSION['user'] ?? null,
                'error' => 'Email ou mot de passe incorrect.'
            ]);
            return;
        }

        $_SESSION['user'] = [
            'id' => $user['user_id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        header('Location: /');
        exit;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();

        header('Location: /');
        exit;
    }

    public function showForgotPassword(): void
    {
        echo $this->twig->render('forgot-password.html.twig', [
            'user' => $_SESSION['user'] ?? null,
            'message' => null
        ]);
    }

    public function forgotPassword(): void
    {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            echo $this->twig->render('forgot-password.html.twig', [
                'user' => $_SESSION['user'] ?? null,
                'message' => 'Veuillez renseigner votre adresse email.'
            ]);
            return;
        }

        echo $this->twig->render('forgot-password.html.twig', [
            'user' => $_SESSION['user'] ?? null,
            'message' => 'Si un compte correspond à cet email, une procédure de réinitialisation vous sera communiquée par l’administrateur.'
        ]);
    }
}