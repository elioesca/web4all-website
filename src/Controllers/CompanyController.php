<?php

namespace App\Controllers;

use App\Models\CompanyModel;
use App\Models\Paginator;

/**
 * CompanyController
 *
 * Contrôleur gérant les entreprises : recherche, détails, création, modification, désactivation et avis.
 * La plupart des actions nécessitent une connexion et des droits admin/pilot.
 */
class CompanyController extends Controller
{
    /**
     * index()
     *
     * Affiche la liste paginée d'entreprises avec filtres par nom, secteur et note.
     * Accessible à tous les utilisateurs, aucune authentification nécessaire.
     */
    public function index(): void
    {
        // Récupère les paramètres de filtre depuis la query string
        $name = trim($_GET['name'] ?? '');
        $sector = trim($_GET['sector'] ?? '');
        $rating = trim($_GET['rating'] ?? '');

        // Crée une instance du modèle CompanyModel
        $companyModel = new CompanyModel();

        $totalCompanies = $companyModel->countCompanies($name, $sector, $rating);
        $paginator = new Paginator($totalCompanies, 9);

        $companies = $companyModel->getCompanies(
            $name,
            $sector,
            $rating,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        $this->render('company/index.html.twig', [
            'pageTitle' => 'Rechercher une entreprise',
            'companies' => $companies,
            'sectors' => $companyModel->getSectors(),
            'name' => $name,
            'sector' => $sector,
            'rating' => $rating,
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
            'basePath' => '/companies'
        ]);
    }

    /**
     * show()
     *
     * Affiche la page de détails d'une entreprise avec ses offres et avis.
     * Redirige vers la page des entreprises si l'ID est invalide ou introuvable.
     */
    public function show(): void
    {
        // Récupère l'ID de l'entreprise depuis l'URL
        $companyId = (int) ($_GET['id'] ?? 0);

        // Si l'ID est invalide, redirige vers la liste des entreprises
        if ($companyId <= 0) {
            $this->redirect('/companies');
        }

        // Crée une instance du modèle CompanyModel
        $companyModel = new CompanyModel();
        $company = $companyModel->findById($companyId);

        if (!$company) {
            $this->redirect('/companies');
        }

        $offers = $companyModel->getCompanyOffers($companyId);
        $reviews = $companyModel->getCompanyReviews($companyId);

        $userReview = null;
        if (!empty($_SESSION['user']) && in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $userReview = $companyModel->getUserReviewForCompany($companyId, (int) $_SESSION['user']['id']);
        }

        $this->render('company/show.html.twig', [
            'company' => $company,
            'offers' => $offers,
            'reviews' => $reviews,
            'userReview' => $userReview
        ]);
    }

    /**
     * create()
     *
     * Affiche le formulaire de création d'une entreprise.
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function create(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle approprié
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

        // Affiche le formulaire (mode création)
        $this->render('company/form.html.twig', [
            'pageTitle' => 'CREATION ENTREPRISE',
            'formAction' => '/companies/create',
            'submitLabel' => 'CREER',
            'company' => null,
            'showDeactivate' => false
        ]);
    }

    /**
     * store()
     *
     * Traite le formulaire de création d'une entreprise.
     * Vérifie les champs obligatoires et appelle le modèle pour l'insertion.
     */
    public function store(): void
    {
        // Nécessite une connexion
        $this->requireLogin();

        // Autorisation: admin ou pilot
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

        // Récupération et nettoyage des données du formulaire
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sector = trim($_POST['activity_sector'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');

        if ($name === '' || $description === '' || $sector === '' || $email === '' || $phoneNumber === '') {
            $this->render('company/form.html.twig', [
                'pageTitle' => 'CREATION ENTREPRISE',
                'formAction' => '/companies/create',
                'submitLabel' => 'CREER',
                'error' => 'Veuillez remplir tous les champs.',
                'company' => [
                    'name' => $name,
                    'description' => $description,
                    'activity_sector' => $sector,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ],
                'showDeactivate' => false
            ]);
            return;
        }

        $companyModel = new CompanyModel();
        $created = $companyModel->createCompany($name, $description, $sector, $email, $phoneNumber);

        if (!$created) {
            $this->render('company/form.html.twig', [
                'pageTitle' => 'CREATION ENTREPRISE',
                'formAction' => '/companies/create',
                'submitLabel' => 'CREER',
                'error' => 'Une erreur est survenue lors de la création.',
                'company' => [
                    'name' => $name,
                    'description' => $description,
                    'activity_sector' => $sector,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ],
                'showDeactivate' => false
            ]);
            return;
        }

        $this->redirect('/companies');
    }

    public function edit(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

        $companyId = (int) ($_GET['id'] ?? 0);

        if ($companyId <= 0) {
            $this->redirect('/companies');
        }

        $companyModel = new CompanyModel();
        $company = $companyModel->findById($companyId);

        if (!$company) {
            $this->redirect('/companies');
        }

        $this->render('company/form.html.twig', [
            'pageTitle' => 'MODIFICATION ENTREPRISE',
            'formAction' => '/companies/edit',
            'submitLabel' => 'MODIFIER',
            'company' => $company,
            'showDeactivate' => true
        ]);
    }

    /**
     * update()
     *
     * Traite le formulaire de modification d'une entreprise.
     * Vérifie les champs et met à jour la base de données.
     */
    public function update(): void
    {
        // Nécessite une connexion
        $this->requireLogin();

        // Autorisation: admin ou pilot
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

        // Récupération des données du formulaire
        $companyId = (int) ($_POST['company_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sector = trim($_POST['activity_sector'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');

        if ($companyId <= 0) {
            $this->redirect('/companies');
        }

        if ($name === '' || $description === '' || $sector === '' || $email === '' || $phoneNumber === '') {
            $this->render('company/form.html.twig', [
                'pageTitle' => 'MODIFICATION ENTREPRISE',
                'formAction' => '/companies/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Veuillez remplir tous les champs.',
                'company' => [
                    'company_id' => $companyId,
                    'name' => $name,
                    'description' => $description,
                    'activity_sector' => $sector,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ],
                'showDeactivate' => true
            ]);
            return;
        }

        $companyModel = new CompanyModel();
        $updated = $companyModel->updateCompany($companyId, $name, $description, $sector, $email, $phoneNumber);

        if (!$updated) {
            $this->render('company/form.html.twig', [
                'pageTitle' => 'MODIFICATION ENTREPRISE',
                'formAction' => '/companies/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Une erreur est survenue lors de la modification.',
                'company' => $companyModel->findById($companyId),
                'showDeactivate' => true
            ]);
            return;
        }

        $this->redirect('/companies/show?id=' . $companyId);
    }

    /**
     * deactivate()
     *
     * Désactive une entreprise (marque comme inactive dans la base).
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function deactivate(): void
    {
        // Vérifie la connexion
        $this->requireLogin();

        // Vérifie le rôle
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

        // Récupère l'ID de l'entreprise à désactiver
        $companyId = (int) ($_POST['company_id'] ?? 0);

        if ($companyId <= 0) {
            $this->redirect('/companies');
        }

        // Exécute la désactivation via le modèle
        $companyModel = new CompanyModel();
        $companyModel->deactivateCompany($companyId);

        // Retourne à la liste
        $this->redirect('/companies');
    }

    /**
     * review()
     *
     * Enregistre un avis sur une entreprise.
     * Seuls admin/pilot peuvent écrire un avis (contrôlé en front et ici).
     */
    public function review(): void
    {
        // Vérifie la connexion
        $this->requireLogin();

        // Vérifie le rôle
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

        // Récupère les données du POST
        $companyId = (int) ($_POST['company_id'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $review = trim($_POST['review'] ?? '');

        // Validation basique
        if ($companyId <= 0 || $rating < 1 || $rating > 5 || $review === '') {
            $this->redirect('/companies/show?id=' . $companyId);
        }

        // Enregistre l'avis
        $companyModel = new CompanyModel();
        $companyModel->saveReview($companyId, (int) $_SESSION['user']['id'], $rating, $review);

        // Retourne à la page de l'entreprise
        $this->redirect('/companies/show?id=' . $companyId);
    }
}