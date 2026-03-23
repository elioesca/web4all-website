<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * ProfileController
 * 
 * Contrôleur gérant le profil utilisateur de l'administrateur.
 * Permet à l'administrateur de consulter, modifier et supprimer son compte.
 */
class ProfileController extends Controller
{
    /**
     * index()
     * 
     * Affiche la page du profil de l'administrateur connecté.
     * Accessible uniquement aux administrateurs.
     */
    public function index(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin'
        // Sinon, le redirige vers le tableau de bord
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        // Crée une instance du modèle UserModel
        $userModel = new UserModel();
        // Récupère les informations du profil de l'administrateur connecté
        $user = $userModel->findById((int) $_SESSION['user']['id']);

        // Si l'utilisateur n'existe pas, redirige vers le tableau de bord
        if (!$user) {
            $this->redirect('/dashboard');
        }

        // Affiche la vue du profil avec les données utilisateur
        $this->render('profile.html.twig', [
            'profileUser' => $user
        ]);
    }

    /**
     * update()
     * 
     * Traite la mise à jour du profil de l'administrateur.
     * Valide les données, vérifie que l'email est unique, puis met à jour le compte.
     * Accessible uniquement aux administrateurs.
     */
    public function update(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin'
        // Sinon, le redirige vers le tableau de bord
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID de l'administrateur connecté depuis la session
        $userId = (int) $_SESSION['user']['id'];

        // Récupère les données du formulaire en utilisant trim pour nettoyer les espaces
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // VALIDATION 1: Vérifie que tous les champs obligatoires sont remplis
        if ($lastName === '' || $firstName === '' || $email === '') {
            // Réaffiche la vue du profil avec un message d'erreur
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

        // VALIDATION 2: Vérifie que l'adresse email est au bon format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Réaffiche la vue du profil avec un message d'erreur
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

        // Crée une instance du modèle UserModel
        $userModel = new UserModel();

        // VALIDATION 3: Vérifie que cet email n'est pas utilisé par un autre utilisateur
        if ($userModel->emailExistsForAnotherUser($email, $userId)) {
            // Réaffiche la vue du profil avec un message d'erreur
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

        // Met à jour le profil de l'administrateur avec les données validées
        $updated = $userModel->updateProfile(
            $userId,
            $lastName,
            $firstName,
            $email,
            $phoneNumber,
            $password
        );

        // Si la mise à jour a échoué, réaffiche le formulaire avec un message d'erreur
        if (!$updated) {
            $this->render('profile.html.twig', [
                'error' => 'Une erreur est survenue lors de la mise à jour.',
                'profileUser' => $userModel->findById($userId)
            ]);
            return;
        }

        // Met à jour la session utilisateur avec les nouvelles données
        $_SESSION['user']['last_name'] = $lastName;
        $_SESSION['user']['first_name'] = $firstName;
        $_SESSION['user']['email'] = $email;

        // Affiche la page du profil avec un message de succès
        $this->render('profile.html.twig', [
            'success' => 'Profil mis à jour avec succès.',
            'profileUser' => $userModel->findById($userId)
        ]);
    }

    /**
     * delete()
     * 
     * Supprime le compte de l'administrateur connecté.
     * Détruit la session et redirige vers la page d'accueil.
     * Accessible uniquement aux administrateurs.
     */
    public function delete(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin'
        // Sinon, le redirige vers le tableau de bord
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID de l'administrateur connecté depuis la session
        $userId = (int) $_SESSION['user']['id'];

        // Crée une instance du modèle UserModel
        $userModel = new UserModel();
        // Supprime le compte utilisateur de la base de données
        $deleted = $userModel->deleteAccount($userId);

        // Si la suppression a échoué, affiche un message d'erreur
        if (!$deleted) {
            $this->render('profile.html.twig', [
                'error' => 'La suppression du compte a échoué.',
                'profileUser' => $userModel->findById($userId)
            ]);
            return;
        }

        // Vide complètement la session
        $_SESSION = [];

        // Détruit la session
        session_destroy();

        // Redirige vers la page d'accueil
        $this->redirect('/');
    }
}