<?php

namespace App\Controllers;

use App\Models\CompanyModel;
use App\Models\Paginator;

class CompanyController extends Controller
{
    public function index(): void
    {
        $name = trim($_GET['name'] ?? '');
        $sector = trim($_GET['sector'] ?? '');
        $rating = trim($_GET['rating'] ?? '');

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

    public function show(): void
    {
        $companyId = (int) ($_GET['id'] ?? 0);

        if ($companyId <= 0) {
            $this->redirect('/companies');
        }

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

    public function create(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

        $this->render('company/form.html.twig', [
            'pageTitle' => 'CREATION ENTREPRISE',
            'formAction' => '/companies/create',
            'submitLabel' => 'CREER',
            'company' => null,
            'showDeactivate' => false
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

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

    public function update(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

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

    public function deactivate(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

        $companyId = (int) ($_POST['company_id'] ?? 0);

        if ($companyId <= 0) {
            $this->redirect('/companies');
        }

        $companyModel = new CompanyModel();
        $companyModel->deactivateCompany($companyId);

        $this->redirect('/companies');
    }

    public function review(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/companies');
        }

        $companyId = (int) ($_POST['company_id'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $review = trim($_POST['review'] ?? '');

        if ($companyId <= 0 || $rating < 1 || $rating > 5 || $review === '') {
            $this->redirect('/companies/show?id=' . $companyId);
        }

        $companyModel = new CompanyModel();
        $companyModel->saveReview($companyId, (int) $_SESSION['user']['id'], $rating, $review);

        $this->redirect('/companies/show?id=' . $companyId);
    }
}