<?php

namespace App\Controllers;

use App\Models\OfferModel;
use App\Models\Paginator;
use App\Models\ApplicationModel;
use App\Models\WishlistModel;

/**
 * OfferController
 * 
 * Contrôleur gérant l'affichage, la création, la modification et la suppression des offres de stage.
 * Permet aux étudiants de consulter les offres et aux administrateurs/pilots de les gérer.
 */
class OfferController extends Controller
{
    /**
     * index()
     * 
     * Affiche la liste paginée de toutes les offres avec filtrage par mot-clé, compétence et durée.
     * Pour les étudiants connectés, affiche également leur liste de souhaits.
     * Accessible par tous les utilisateurs.
     */
    public function index(): void
    {
        // Récupère les paramètres de filtrage depuis l'URL
        $keyword = trim($_GET['keyword'] ?? '');
        $skillId = trim($_GET['skill_id'] ?? '');
        $duration = trim($_GET['duration'] ?? '');

        // Crée une instance du modèle OfferModel
        $offerModel = new OfferModel();

        // Compte le nombre total d'offres correspondant à la recherche / filtrage
        $totalOffers = $offerModel->countOffers($keyword, $skillId, $duration);
        // Crée un objet Paginator pour gérer la pagination (9 offres par page)
        $paginator = new Paginator($totalOffers, 9);

        // Récupère les offres pour la page actuelle avec les filtres appliqués
        $offers = $offerModel->getOffers(
            $keyword,
            $skillId,
            $duration,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        // Initialise un tableau vide pour stocker les IDs des offres dans la wish-list
        $wishlistOfferIds = [];

        // Si l'utilisateur est un étudiant connecté, récupère sa liste de souhaits
        if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'student') {
            // Crée une instance du modèle WishlistModel
            $wishlistModel = new WishlistModel();
            // Récupère les IDs des offres dans la wish-list de l'étudiant
            $wishlistOfferIds = $wishlistModel->getWishlistOfferIdsForStudent((int) $_SESSION['user']['id']);
        }

        // Affiche la vue avec les offres paginées et les données de filtrage
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

    /**
     * create()
     * 
     * Affiche le formulaire de création d'une nouvelle offre de stage.
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function create(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        // Sinon, le redirige vers la liste des offres
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/offers');
        }

        // Crée une instance du modèle OfferModel
        $offerModel = new OfferModel();

        // Affiche le formulaire vierge avec les données nécessaires
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

    /**
     * store()
     * 
     * Traite la soumission du formulaire de création d'une offre de stage.
     * Valide les données puis crée l'offre en base de données.
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function store(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/offers');
        }

        // Récupère toutes les données du formulaire en nettoyant et convertissant les valeurs
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $duration = (int) ($_POST['duration'] ?? 0);
        $salary = (float) ($_POST['salary'] ?? 0);
        $availablePlaces = (int) ($_POST['available_places'] ?? 0);
        $companyId = (int) ($_POST['company_id'] ?? 0);
        $offerTypeId = (int) ($_POST['offer_type_id'] ?? 0);
        // Récupère les IDs des compétences sélectionnées
        $skillIds = $_POST['skill_ids'] ?? [];

        // Crée une instance du modèle OfferModel
        $offerModel = new OfferModel();

        // VALIDATION: Vérifie que tous les champs sont valides et qu'au moins une compétence est sélectionnée
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

        // Crée l'offre de stage avec les données validées
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

        // Si la création a échoué, réaffiche le formulaire avec un message d'erreur
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

        // Redirige vers la liste des offres après la création réussie
        $this->redirect('/offers');
    }

    /**
     * edit()
     * 
     * Affiche le formulaire de modification d'une offre de stage.
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function edit(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/offers');
        }

        // Récupère l'ID de l'offre depuis l'URL
        $offerId = (int) ($_GET['id'] ?? 0);

        // Vérifie que l'ID de l'offre est valide
        if ($offerId <= 0) {
            $this->redirect('/offers');
        }

        // Crée une instance du modèle et récupère l'offre
        $offerModel = new OfferModel();
        $offer = $offerModel->findById($offerId);

        // Si l'offre n'existe pas, redirige vers la liste
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

    /**
     * update()
     * 
     * Traite la soumission du formulaire de modification d'une offre de stage.
     * Valide les données et met à jour l'offre en base de données.
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function update(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/offers');
        }

        // Récupère toutes les données du formulaire
        $offerId = (int) ($_POST['offer_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $duration = (int) ($_POST['duration'] ?? 0);
        $salary = (float) ($_POST['salary'] ?? 0);
        $availablePlaces = (int) ($_POST['available_places'] ?? 0);
        $companyId = (int) ($_POST['company_id'] ?? 0);
        $offerTypeId = (int) ($_POST['offer_type_id'] ?? 0);
        // Récupère les IDs des compétences sélectionnées
        $skillIds = $_POST['skill_ids'] ?? [];

        // Crée une instance du modèle OfferModel
        $offerModel = new OfferModel();

        // VALIDATION: Vérifie que tous les champs sont valides et qu'au moins une compétence est sélectionnée
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

        // Met à jour l'offre avec les données validées
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

        // Si la mise à jour a échoué, réaffiche le formulaire avec les données actuelles
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

        // Redirige vers la page de visualisation de l'offre après modification réussie
        $this->redirect('/offers/show?id=' . $offerId);
    }

    /**
     * deactivate()
     * 
     * Désactive une offre de stage.
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function deactivate(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/offers');
        }

        // Récupère l'ID de l'offre à désactiver
        $offerId = (int) ($_POST['offer_id'] ?? 0);

        // Vérifie que l'ID de l'offre est valide
        if ($offerId <= 0) {
            $this->redirect('/offers');
        }

        // Désactive l'offre
        $offerModel = new OfferModel();
        $offerModel->deactivateOffer($offerId);

        // Redirige vers la liste des offres
        $this->redirect('/offers');
    }

    /**
     * show()
     * 
     * Affiche les détails complets d'une offre de stage avec ses compétences.
     * Affiche également si l'étudiant a candidaté et si l'offre est dans sa wish-list.
     * Accessible par tous les utilisateurs.
     */
    public function show(): void
    {
        // Récupère l'ID de l'offre depuis l'URL
        $offerId = (int) ($_GET['id'] ?? 0);

        // Vérifie que l'ID de l'offre est valide
        if ($offerId <= 0) {
            $this->redirect('/offers');
        }

        // Crée une instance du modèle et récupère l'offre
        $offerModel = new OfferModel();
        $offer = $offerModel->findById($offerId);

        // Si l'offre n'existe pas, redirige vers la liste
        if (!$offer) {
            $this->redirect('/offers');
        }

        // Récupère les compétences associées à cette offre
        $skills = $offerModel->getOfferSkills($offerId);

        // Initialise les drapeaux pour l'étudiant connecté
        $hasAlreadyApplied = false;
        $isInWishlist = false;

        // Si l'utilisateur est un étudiant connecté
        if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'student') {
            // Vérifie si l'étudiant a déjà candidaté à cette offre
            $applicationModel = new \App\Models\ApplicationModel();
            $hasAlreadyApplied = $applicationModel->hasAlreadyApplied(
                (int) $_SESSION['user']['id'],
                $offerId
            );

            // Vérifie si l'offre est dans la wish-list de l'étudiant
            $wishlistModel = new WishlistModel();
            $isInWishlist = $wishlistModel->isOfferInWishlist(
                (int) $_SESSION['user']['id'],
                $offerId
            );
        }

        // Affiche la vue détaillée de l'offre
        $this->render('offer/show.html.twig', [
            'offer' => $offer,
            'skills' => $skills,
            'hasAlreadyApplied' => $hasAlreadyApplied,
            'isInWishlist' => $isInWishlist
        ]);
    }

    /**
     * stats()
     * 
     * Affiche les statistiques sur toutes les offres de stage.
     * Crée des graphiques avec le nombre total d'offres, la moyenne de candidatures,
     * la distribution par durée et les offres les plus "wish-listées".
     * Accessible par tous les utilisateurs.
     */
    public function stats(): void
    {
        // Crée une instance du modèle OfferModel
        $offerModel = new OfferModel();

        // Récupère le nombre total d'offres actives
        $totalOffers = $offerModel->getTotalActiveOffers();
        // Récupère la moyenne de candidatures par offre
        $averageApplications = $offerModel->getAverageApplicationsPerOffer();
        // Récupère la distribution des offres par durée
        $distribution = $offerModel->getOfferDistributionByDuration();
        // Récupère les 5 offres les plus souvent "wish-listées"
        $topWishlisted = $offerModel->getTopWishlistedOffers(5);

        // Calcule le nombre maximum d'offres dans la distribution pour la mise à l'échelle du graphique
        $maxDistribution = 0;
        foreach ($distribution as $item) {
            if ((int) $item['total'] > $maxDistribution) {
                $maxDistribution = (int) $item['total'];
            }
        }

        // Affiche la vue avec les données statistiques
        $this->render('offer/stats.html.twig', [
            'pageTitle' => 'STATISTIQUES DES OFFRES',
            'totalOffers' => $totalOffers,
            'averageApplications' => $averageApplications,
            'distribution' => $distribution,
            'topWishlisted' => $topWishlisted,
            'maxDistribution' => $maxDistribution
        ]);
    }
}