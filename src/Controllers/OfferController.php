<?php

namespace App\Controllers;

use App\Models\OfferModel;
use App\Models\Paginator;
use App\Models\ApplicationModel;
use App\Models\WishlistModel;

class OfferController extends Controller
{
    public function index(): void
    {
        $keyword = trim($_GET['keyword'] ?? '');
        $skillId = trim($_GET['skill_id'] ?? '');
        $duration = trim($_GET['duration'] ?? '');

        $offerModel = new OfferModel();

        $totalOffers = $offerModel->countOffers($keyword, $skillId, $duration);
        $paginator = new Paginator($totalOffers, 9);

        $offers = $offerModel->getOffers(
            $keyword,
            $skillId,
            $duration,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        $wishlistOfferIds = [];

        if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'student') {
            $wishlistModel = new WishlistModel();
            $wishlistOfferIds = $wishlistModel->getWishlistOfferIdsForStudent((int) $_SESSION['user']['id']);
        }

        $this->render('offer/index.html.twig', [
            'pageTitle' => 'Rechercher une offre de stage',
            'offers' => $offers,
            'skills' => $offerModel->getSkills(),
            'keyword' => $keyword,
            'skillId' => $skillId,
            'duration' => $duration,
            'wishlistOfferIds' => $wishlistOfferIds,
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
            'basePath' => '/offers'
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/offers');
        }

        $offerModel = new OfferModel();

        $this->render('offer/form.html.twig', [
            'pageTitle' => 'DEPOSER UNE OFFRE',
            'formAction' => '/offers/create',
            'submitLabel' => 'DEPOSER L’OFFRE',
            'offer' => null,
            'companies' => $offerModel->getCompanies(),
            'offerTypes' => $offerModel->getOfferTypes(),
            'skills' => $offerModel->getSkills(),
            'selectedSkills' => [],
            'showDeactivate' => false
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/offers');
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $duration = (int) ($_POST['duration'] ?? 0);
        $salary = (float) ($_POST['salary'] ?? 0);
        $availablePlaces = (int) ($_POST['available_places'] ?? 0);
        $companyId = (int) ($_POST['company_id'] ?? 0);
        $offerTypeId = (int) ($_POST['offer_type_id'] ?? 0);
        $skillIds = $_POST['skill_ids'] ?? [];

        $offerModel = new OfferModel();

        if ($title === '' || $description === '' || $content === '' || $duration <= 0 || $salary < 0
            || $availablePlaces <= 0 || $companyId <= 0 || $offerTypeId <= 0 || empty($skillIds)) {
            $this->render('offer/form.html.twig', [
                'pageTitle' => 'DEPOSER UNE OFFRE',
                'formAction' => '/offers/create',
                'submitLabel' => "DEPOSER L'OFFRE",
                'error' => 'Veuillez remplir tous les champs et sélectionner au moins une compétence.',
                'offer' => [
                    'title' => $title,
                    'description' => $description,
                    'content' => $content,
                    'duration' => $duration,
                    'salary' => $salary,
                    'available_places' => $availablePlaces,
                    'company_id' => $companyId,
                    'offer_type_id' => $offerTypeId
                ],
                'companies' => $offerModel->getCompanies(),
                'offerTypes' => $offerModel->getOfferTypes(),
                'skills' => $offerModel->getSkills(),
                'selectedSkills' => $skillIds,
                'showDeactivate' => false
            ]);
            return;
        }

        $created = $offerModel->createOffer(
                    $title,
                    $description,
                    $content,
                    $duration,
                    $salary,
                    $availablePlaces,
                    $companyId,
                    $offerTypeId,
                    (int) $_SESSION['user']['id'],
                    $skillIds
                );

        if (!$created) {
            $this->render('offer/form.html.twig', [
                'pageTitle' => 'DEPOSER UNE OFFRE',
                'formAction' => '/offers/create',
                'submitLabel' => "DEPOSER L'OFFRE",
                'error' => 'Une erreur est survenue lors de la création.',
                'offer' => [
                    'title' => $title,
                    'description' => $description,
                    'content' => $content,
                    'duration' => $duration,
                    'salary' => $salary,
                    'available_places' => $availablePlaces,
                    'company_id' => $companyId,
                    'offer_type_id' => $offerTypeId
                ],
                'companies' => $offerModel->getCompanies(),
                'offerTypes' => $offerModel->getOfferTypes(),
                'skills' => $offerModel->getSkills(),
                'selectedSkills' => $skillIds,
                'showDeactivate' => false
            ]);
            return;
        }

        $this->redirect('/offers');
    }

    public function edit(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/offers');
        }

        $offerId = (int) ($_GET['id'] ?? 0);

        if ($offerId <= 0) {
            $this->redirect('/offers');
        }

        $offerModel = new OfferModel();
        $offer = $offerModel->findById($offerId);

        if (!$offer) {
            $this->redirect('/offers');
        }

        $this->render('offer/form.html.twig', [
            'pageTitle' => 'MODIFICATION OFFRE',
            'formAction' => '/offers/edit',
            'submitLabel' => 'MODIFIER',
            'offer' => $offer,
            'companies' => $offerModel->getCompanies(),
            'offerTypes' => $offerModel->getOfferTypes(),
            'skills' => $offerModel->getSkills(),
            'selectedSkills' => $offerModel->getOfferSkillIds($offerId),
            'showDeactivate' => true
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/offers');
        }

        $offerId = (int) ($_POST['offer_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $duration = (int) ($_POST['duration'] ?? 0);
        $salary = (float) ($_POST['salary'] ?? 0);
        $availablePlaces = (int) ($_POST['available_places'] ?? 0);
        $companyId = (int) ($_POST['company_id'] ?? 0);
        $offerTypeId = (int) ($_POST['offer_type_id'] ?? 0);
        $skillIds = $_POST['skill_ids'] ?? [];

        $offerModel = new OfferModel();

        if (
            $offerId <= 0 || $title === '' || $description === '' || $content === '' || $duration <= 0 || $salary < 0
            || $availablePlaces <= 0 || $companyId <= 0 || $offerTypeId <= 0 || empty($skillIds)
        ) {
            $this->render('offer/form.html.twig', [
                'pageTitle' => 'MODIFICATION OFFRE',
                'formAction' => '/offers/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Veuillez remplir tous les champs et sélectionner au moins une compétence.',
                'offer' => [
                    'offer_id' => $offerId,
                    'title' => $title,
                    'description' => $description,
                    'content' => $content,
                    'duration' => $duration,
                    'salary' => $salary,
                    'available_places' => $availablePlaces,
                    'company_id' => $companyId,
                    'offer_type_id' => $offerTypeId
                ],
                'companies' => $offerModel->getCompanies(),
                'offerTypes' => $offerModel->getOfferTypes(),
                'skills' => $offerModel->getSkills(),
                'selectedSkills' => $skillIds,
                'showDeactivate' => true
            ]);
            return;
        }

        $updated = $offerModel->updateOffer(
                    $offerId,
                    $title,
                    $description,
                    $content,
                    $duration,
                    $salary,
                    $availablePlaces,
                    $companyId,
                    $offerTypeId,
                    $skillIds
                );

        if (!$updated) {
            $this->render('offer/form.html.twig', [
                'pageTitle' => 'MODIFICATION OFFRE',
                'formAction' => '/offers/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Une erreur est survenue lors de la modification.',
                'offer' => $offerModel->findById($offerId),
                'companies' => $offerModel->getCompanies(),
                'offerTypes' => $offerModel->getOfferTypes(),
                'skills' => $offerModel->getSkills(),
                'selectedSkills' => $offerModel->getOfferSkillIds($offerId),
                'showDeactivate' => true
            ]);
            return;
        }

        $this->redirect('/offers/show?id=' . $offerId);
    }

    public function deactivate(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/offers');
        }

        $offerId = (int) ($_POST['offer_id'] ?? 0);

        if ($offerId <= 0) {
            $this->redirect('/offers');
        }

        $offerModel = new OfferModel();
        $offerModel->deactivateOffer($offerId);

        $this->redirect('/offers');
    }

    public function show(): void
    {
        $offerId = (int) ($_GET['id'] ?? 0);

        if ($offerId <= 0) {
            $this->redirect('/offers');
        }

        $offerModel = new OfferModel();
        $offer = $offerModel->findById($offerId);

        if (!$offer) {
            $this->redirect('/offers');
        }

        $skills = $offerModel->getOfferSkills($offerId);

        $hasAlreadyApplied = false;
        $isInWishlist = false;

        if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'student') {
            $applicationModel = new \App\Models\ApplicationModel();
            $hasAlreadyApplied = $applicationModel->hasAlreadyApplied(
                (int) $_SESSION['user']['id'],
                $offerId
            );

            $wishlistModel = new WishlistModel();
            $isInWishlist = $wishlistModel->isOfferInWishlist(
                (int) $_SESSION['user']['id'],
                $offerId
            );
        }

        $this->render('offer/show.html.twig', [
            'offer' => $offer,
            'skills' => $skills,
            'hasAlreadyApplied' => $hasAlreadyApplied,
            'isInWishlist' => $isInWishlist
        ]);
    }
}