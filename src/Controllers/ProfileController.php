<?php

namespace App\Controllers;

use App\Models\UserModel;

class ProfileController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        $userModel = new UserModel();
        $user = $userModel->findById((int) $_SESSION['user']['id']);

        if (!$user) {
            $this->redirect('/dashboard');
        }

        $this->render('profile.html.twig', [
            'profileUser' => $user
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        $userId = (int) $_SESSION['user']['id'];

        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($lastName === '' || $firstName === '' || $email === '') {
            $this->render('profile.html.twig', [
                'error' => 'Veuillez remplir les champs obligatoires.',
                'profileUser' => [
                    'user_id' => $userId,
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ]
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('profile.html.twig', [
                'error' => 'Adresse email invalide.',
                'profileUser' => [
                    'user_id' => $userId,
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ]
            ]);
            return;
        }

        $userModel = new UserModel();

        if ($userModel->emailExistsForAnotherUser($email, $userId)) {
            $this->render('profile.html.twig', [
                'error' => 'Cet email est déjà utilisé.',
                'profileUser' => [
                    'user_id' => $userId,
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ]
            ]);
            return;
        }

        $updated = $userModel->updateProfile(
            $userId,
            $lastName,
            $firstName,
            $email,
            $phoneNumber,
            $password
        );

        if (!$updated) {
            $this->render('profile.html.twig', [
                'error' => 'Une erreur est survenue lors de la mise à jour.',
                'profileUser' => $userModel->findById($userId)
            ]);
            return;
        }

        $_SESSION['user']['last_name'] = $lastName;
        $_SESSION['user']['first_name'] = $firstName;
        $_SESSION['user']['email'] = $email;

        $this->render('profile.html.twig', [
            'success' => 'Profil mis à jour avec succès.',
            'profileUser' => $userModel->findById($userId)
        ]);
    }

    public function delete(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        $userId = (int) $_SESSION['user']['id'];

        $userModel = new UserModel();
        $deleted = $userModel->deleteAccount($userId);

        if (!$deleted) {
            $this->render('profile.html.twig', [
                'error' => 'La suppression du compte a échoué.',
                'profileUser' => $userModel->findById($userId)
            ]);
            return;
        }

        $_SESSION = [];

        session_destroy();

        $this->redirect('/');
    }
}