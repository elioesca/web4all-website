<?php

namespace App\Controllers;

use App\Models\Paginator;
use App\Models\PilotModel;
use App\Models\UserModel;

class PilotController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        $search = trim($_GET['search'] ?? '');
        $pilotModel = new PilotModel();

        $totalPilots = $pilotModel->countPilots($search);
        $paginator = new Paginator($totalPilots, 8);

        $pilots = $pilotModel->getPilots(
            $search,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        $this->render('pilot/index.html.twig', [
            'pageTitle' => 'GESTION DES PILOTES',
            'pilots' => $pilots,
            'search' => $search,
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
            'basePath' => '/pilots'
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        $pilotModel = new PilotModel();

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

    public function store(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $promotionIds = $_POST['promotion_ids'] ?? [];

        $pilotModel = new PilotModel();
        $userModel = new UserModel();

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

        $created = $pilotModel->createPilot(
            $lastName,
            $firstName,
            $email,
            $phoneNumber,
            $password,
            $promotionIds
        );

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

        $this->redirect('/pilots');
    }

    public function edit(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        $userId = (int) ($_GET['id'] ?? 0);

        if ($userId <= 0) {
            $this->redirect('/pilots');
        }

        $pilotModel = new PilotModel();
        $pilot = $pilotModel->findPilotById($userId);

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

    public function update(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $promotionIds = $_POST['promotion_ids'] ?? [];

        if ($userId <= 0) {
            $this->redirect('/pilots');
        }

        $pilotModel = new PilotModel();
        $userModel = new UserModel();

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

        $updated = $pilotModel->updatePilot(
            $userId,
            $lastName,
            $firstName,
            $email,
            $phoneNumber,
            $password,
            $promotionIds
        );

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

        $this->redirect('/pilots');
    }

    public function deactivate(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'admin') {
            $this->redirect('/dashboard');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            $this->redirect('/pilots');
        }

        $pilotModel = new PilotModel();
        $pilotModel->deactivatePilot($userId);

        $this->redirect('/pilots');
    }
}