<?php

namespace App\Controllers;

use App\Models\Paginator;
use App\Models\PilotModel;
use App\Models\UserModel;

/**
 * PilotController
 * 
 * Contrôleur gérant l'affichage, la création, la modification et la suppression des pilots (mentors/accompagnateurs).
 * Permet aux administrateurs de gérer les compte des pilots et leurs promotions assignées.
 */
class PilotController extends Controller
{
    /**
     * index()
     * 
     * Affiche la liste paginée de tous les pilots avec une fonction de recherche.
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

        // Récupère le paramètre de recherche depuis l'URL
        $search = trim($_GET['search'] ?? '');
        // Crée une instance du modèle PilotModel
        $pilotModel = new PilotModel();

        // Compte le nombre total de pilots correspondant à la recherche
        $totalPilots = $pilotModel->countPilots($search);
        // Crée un objet Paginator pour gérer la pagination (8 pilots par page)
        $paginator = new Paginator($totalPilots, 8);

        // Récupère les pilots pour la page actuelle
        $pilots = $pilotModel->getPilots(
            $search,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        // Affiche la vue avec les données paginées
        $this->render('pilot/index.html.twig', [
            'pageTitle' => 'GESTION DES PILOTES',
            'pilots' => $pilots,
            'search' => $search,
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
            'basePath' => '/pilots'
        ]);
    }

    /**
     * create()
     * 
     * Affiche le formulaire de création d'un nouvel pilot.
     * Accessible uniquement aux administrateurs.
     */
    public function create(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin'
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        // Crée une instance du modèle PilotModel
        $pilotModel = new PilotModel();

        // Affiche le formulaire vierge avec les données nécessaires
        $this->render('pilot/form.html.twig', [
            'pageTitle' => 'CREATION COMPTE PILOTE',
            'formAction' => '/pilots/create',
            'submitLabel' => 'CREER',
            'pilot' => null,
            'promotions' => $pilotModel->getPromotions(),
            'selectedPromotions' => [],
            'showDeactivate' => false
        ]);
    }

    /**
     * store()
     * 
     * Traite la soumission du formulaire de création d'un nouvel pilot.
     * Valide les données, vérifie que l'email est unique, puis crée le compte.
     * Accessible uniquement aux administrateurs.
     */
    public function store(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin'
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        // Récupère les données du formulaire en utilisant trim pour nettoyer les espaces
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $password = trim($_POST['password'] ?? '');
        // Récupère les IDs des promotions sélectionnées (peut être un tableau vide)
        $promotionIds = $_POST['promotion_ids'] ?? [];

        // Crée les instances des modèles nécessaires
        $pilotModel = new PilotModel();
        $userModel = new UserModel();

        // VALIDATION 1: Vérifie que tous les champs requis sont remplis et qu'au moins une promotion est sélectionnée
        if ($lastName === '' || $firstName === '' || $email === '' || $password === '' || empty($promotionIds)) {
            $this->render('pilot/form.html.twig', [
                'pageTitle' => 'CREATION COMPTE PILOTE',
                'formAction' => '/pilots/create',
                'submitLabel' => 'CREER',
                'error' => 'Veuillez remplir tous les champs obligatoires et sélectionner au moins une promotion.',
                'pilot' => [
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ],
                'promotions' => $pilotModel->getPromotions(),
                'selectedPromotions' => $promotionIds,
                'showDeactivate' => false
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('pilot/form.html.twig', [
                'pageTitle' => 'CREATION COMPTE PILOTE',
                'formAction' => '/pilots/create',
                'submitLabel' => 'CREER',
                'error' => 'Adresse email invalide.',
                'pilot' => [
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ],
                'promotions' => $pilotModel->getPromotions(),
                'selectedPromotions' => $promotionIds,
                'showDeactivate' => false
            ]);
            return;
        }

        // VALIDATION 3: Vérifie que cet email n'existe pas déjà dans la base de données
        if ($userModel->findByEmail($email)) {
            $this->render('pilot/form.html.twig', [
                'pageTitle' => 'CREATION COMPTE PILOTE',
                'formAction' => '/pilots/create',
                'submitLabel' => 'CREER',
                'error' => 'Cet email est déjà utilisé.',
                'pilot' => [
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ],
                'promotions' => $pilotModel->getPromotions(),
                'selectedPromotions' => $promotionIds,
                'showDeactivate' => false
            ]);
            return;
        }

        // Crée le compte pilot avec les données validées
        $created = $pilotModel->createPilot(
            $lastName,
            $firstName,
            $email,
            $phoneNumber,
            $password,
            $promotionIds
        );

        // Si la création a échoué, réaffiche le formulaire avec un message d'erreur
        if (!$created) {
            $this->render('pilot/form.html.twig', [
                'pageTitle' => 'CREATION COMPTE PILOTE',
                'formAction' => '/pilots/create',
                'submitLabel' => 'CREER',
                'error' => 'Une erreur est survenue lors de la création.',
                'pilot' => [
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ],
                'promotions' => $pilotModel->getPromotions(),
                'selectedPromotions' => $promotionIds,
                'showDeactivate' => false
            ]);
            return;
        }

        // Redirige vers la liste des pilots après la création réussie
        $this->redirect('/pilots');
    }

    /**
     * edit()
     * 
     * Affiche le formulaire de modification du profil d'un pilot.
     * Accessible uniquement aux administrateurs.
     */
    public function edit(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin'
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID du pilot depuis l'URL (paramètre 'id')
        $userId = (int) ($_GET['id'] ?? 0);

        // Vérifie que l'ID est valide
        if ($userId <= 0) {
            $this->redirect('/pilots');
        }

        // Crée une instance du modèle et récupère les données du pilot
        $pilotModel = new PilotModel();
        $pilot = $pilotModel->findPilotById($userId);

        // Si le pilot n'existe pas, redirige vers la liste
        if (!$pilot) {
            $this->redirect('/pilots');
        }

        $this->render('pilot/form.html.twig', [
            'pageTitle' => 'MODIFICATION COMPTE PILOTE',
            'formAction' => '/pilots/edit',
            'submitLabel' => 'MODIFIER',
            'pilot' => $pilot,
            'promotions' => $pilotModel->getPromotions(),
            'selectedPromotions' => $pilotModel->getPilotPromotionIds($userId),
            'showDeactivate' => true
        ]);
    }

    /**
     * update()
     * 
     * Traite la soumission du formulaire de modification du profil d'un pilot.
     * Valide les données et met à jour le compte pilot.
     * Accessible uniquement aux administrateurs.
     */
    public function update(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin'
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        // Récupère les données du formulaire
        $userId = (int) ($_POST['user_id'] ?? 0);
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $password = trim($_POST['password'] ?? '');
        // Récupère les IDs des promotions sélectionnées
        $promotionIds = $_POST['promotion_ids'] ?? [];

        // Vérifie que l'ID du pilot est valide
        if ($userId <= 0) {
            $this->redirect('/pilots');
        }

        // Crée les instances des modèles nécessaires
        $pilotModel = new PilotModel();
        $userModel = new UserModel();

        // VALIDATION 1: Vérifie que tous les champs requis sont remplis et qu'au moins une promotion est sélectionnée
        if ($lastName === '' || $firstName === '' || $email === '' || empty($promotionIds)) {
            $this->render('pilot/form.html.twig', [
                'pageTitle' => 'MODIFICATION COMPTE PILOTE',
                'formAction' => '/pilots/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Veuillez remplir tous les champs obligatoires et sélectionner au moins une promotion.',
                'pilot' => [
                    'user_id' => $userId,
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ],
                'promotions' => $pilotModel->getPromotions(),
                'selectedPromotions' => $promotionIds,
                'showDeactivate' => true
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('pilot/form.html.twig', [
                'pageTitle' => 'MODIFICATION COMPTE PILOTE',
                'formAction' => '/pilots/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Adresse email invalide.',
                'pilot' => [
                    'user_id' => $userId,
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ],
                'promotions' => $pilotModel->getPromotions(),
                'selectedPromotions' => $promotionIds,
                'showDeactivate' => true
            ]);
            return;
        }

        // VALIDATION 3: Vérifie que cet email n'est pas utilisé par un autre pilot
        if ($userModel->emailExistsForAnotherUser($email, $userId)) {
            $this->render('pilot/form.html.twig', [
                'pageTitle' => 'MODIFICATION COMPTE PILOTE',
                'formAction' => '/pilots/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Cet email est déjà utilisé.',
                'pilot' => [
                    'user_id' => $userId,
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ],
                'promotions' => $pilotModel->getPromotions(),
                'selectedPromotions' => $promotionIds,
                'showDeactivate' => true
            ]);
            return;
        }

        // Met à jour le compte pilot avec les données validées
        $updated = $pilotModel->updatePilot(
            $userId,
            $lastName,
            $firstName,
            $email,
            $phoneNumber,
            $password,
            $promotionIds
        );

        // Si la mise à jour a échoué, réaffiche le formulaire avec les données actuelles
        if (!$updated) {
            $this->render('pilot/form.html.twig', [
                'pageTitle' => 'MODIFICATION COMPTE PILOTE',
                'formAction' => '/pilots/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Une erreur est survenue lors de la modification.',
                'pilot' => $pilotModel->findPilotById($userId),
                'promotions' => $pilotModel->getPromotions(),
                'selectedPromotions' => $promotionIds,
                'showDeactivate' => true
            ]);
            return;
        }

        // Redirige vers la liste des pilots après la modification réussie
        $this->redirect('/pilots');
    }

    /**
     * deactivate()
     * 
     * Désactive le compte d'un pilot (le pilot ne peut plus se connecter).
     * Accessible uniquement aux administrateurs.
     */
    public function deactivate(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin'
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID du pilot à désactiver
        $userId = (int) ($_POST['user_id'] ?? 0);

        // Vérifie que l'ID est valide
        if ($userId <= 0) {
            $this->redirect('/pilots');
        }

        // Désactive le pilot
        $pilotModel = new PilotModel();
        $pilotModel->deactivatePilot($userId);

        // Redirige vers la liste des pilots
        $this->redirect('/pilots');
    }

    /**
     * reactivate()
     * 
     * Réactive le compte d'un pilot (le pilot peut à nouveau se connecter).
     * Accessible uniquement aux administrateurs.
     */
    public function reactivate(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin'
        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID du pilot à réactiver
        $userId = (int) ($_POST['user_id'] ?? 0);

        // Vérifie que l'ID est valide
        if ($userId <= 0) {
            $this->redirect('/pilots');
        }

        // Réactive le pilot
        $pilotModel = new PilotModel();
        $pilotModel->reactivatePilot($userId);

        // Redirige vers la page de modification du pilot
        $this->redirect('/pilots/edit?id=' . $userId);
    }
}