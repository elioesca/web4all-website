<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * AuthController
 *
 * Contrôleur gérant l'authentification : connexion, déconnexion et mot de passe oublié.
 */
class AuthController extends Controller
{
    /**
     * showLogin()
     *
     * Affiche le formulaire de connexion.
     * Si l'utilisateur est déjà connecté, redirige vers le dashboard.
     */
    public function showLogin(): void
    {
        if (!empty($_SESSION['user'])) {
            $this->redirect('/dashboard');
        }

        $this->render('login.html.twig');
    }

    /**
     * login()
     *
     * Traite la soumission du formulaire de connexion.
     * Valide les champs, vérifie l'utilisateur, le mot de passe et le rôle.
     */
    public function login(): void
    {
        // Récupère et nettoie l'email et le mot de passe du formulaire
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Vérifie que tous les champs sont remplis
        if ($email === '' || $password === '') {
            $this->render('login.html.twig', [
                'error' => 'Veuillez remplir tous les champs.'
            ]);
            return;
        }

        // Recherche l'utilisateur en base
        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        // Erreur si aucun utilisateur n'a été trouvé
        if (!$user) {
            $this->render('login.html.twig', [
                'error' => 'Email ou mot de passe incorrect.'
            ]);
            return;
        }

        // Vérifie que le compte est actif
        if (!(bool)$user['is_valid']) {
            $this->render('login.html.twig', [
                'error' => 'Votre compte est désactivé.'
            ]);
            return;
        }

        // Vérifie le mot de passe
        if (!password_verify($password, $user['password'])) {
            $this->render('login.html.twig', [
                'error' => 'Email ou mot de passe incorrect.'
            ]);
            return;
        }

        // Récupère le rôle utilisateur
        $role = $userModel->getRoleByUserId((int)$user['user_id']);

        if ($role === null) {
            $this->render('login.html.twig', [
                'error' => 'Aucun rôle trouvé pour cet utilisateur.'
            ]);
            return;
        }

        // Sauvegarde les données utilisateur dans la session
        $_SESSION['user'] = [
            'id' => (int)$user['user_id'],
            'last_name' => $user['last_name'],
            'first_name' => $user['first_name'],
            'email' => $user['email'],
            'role' => $role
        ];

        // Redirige vers le dashboard
        $this->redirect('/dashboard');
    }

    /**
     * logout()
     *
     * Détruit la session et redirige vers la page d'accueil.
     */
    public function logout(): void
    {
        session_destroy();
        $this->redirect('/');
    }

    /**
     * showForgotPassword()
     *
     * Affiche le formulaire de mot de passe oublié.
     */
    public function showForgotPassword(): void
    {
        $this->render('forgot-password.html.twig');
    }

    /**
     * forgotPassword()
     *
     * Affiche le message d'information après envoi de demande de réinitialisation de mot de passe.
     * Dans cette version, l'envoi d'email est simulé avec un message statique.
     */
    public function forgotPassword(): void
    {
        $this->render('forgot-password.html.twig', [
            'message' => 'Si un compte est associé à cette adresse, vous recevrez un e-mail contenant les instructions pour réinitialiser votre mot de passe.'
        ]);
    }
}