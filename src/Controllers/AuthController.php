<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (!empty($_SESSION['user'])) {
            $this->redirect('/dashboard');
        }

        $this->render('login.html.twig');
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->render('login.html.twig', [
                'error' => 'Veuillez remplir tous les champs.'
            ]);
            return;
        }

        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        if (!$user) {
            $this->render('login.html.twig', [
                'error' => 'Email ou mot de passe incorrect.'
            ]);
            return;
        }

        if (!(bool)$user['is_valid']) {
            $this->render('login.html.twig', [
                'error' => 'Votre compte est désactivé.'
            ]);
            return;
        }

        if (!password_verify($password, $user['password'])) {
            $this->render('login.html.twig', [
                'error' => 'Email ou mot de passe incorrect.'
            ]);
            return;
        }

        $role = $userModel->getRoleByUserId((int)$user['user_id']);

        if ($role === null) {
            $this->render('login.html.twig', [
                'error' => 'Aucun rôle trouvé pour cet utilisateur.'
            ]);
            return;
        }

        $_SESSION['user'] = [
            'id' => (int)$user['user_id'],
            'last_name' => $user['last_name'],
            'first_name' => $user['first_name'],
            'email' => $user['email'],
            'role' => $role
        ];

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirect('/');
    }

    public function showForgotPassword(): void
    {
        $this->render('forgot-password.html.twig');
    }

    public function forgotPassword(): void
    {
        $this->render('forgot-password.html.twig', [
            'message' => 'Nous vous avons envoyé un mail pour pouvoir modifier le mot de passe.'
        ]);
    }
}