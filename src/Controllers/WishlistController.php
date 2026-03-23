<?php

namespace App\Controllers;

use App\Models\OfferModel;
use App\Models\Paginator;
use App\Models\WishlistModel;

/**
 * WishlistController
 * 
 * Contrôleur gérant la wish-list des étudiants.
 * Permet aux étudiants de consulter, ajouter et retirer des offres de leur wish-list.
 */
class WishlistController extends Controller
{
    /**
     * index()
     * 
     * Affiche la wish-list paginée de l'étudiant connecté avec les offres qu'il a sauvegardées.
     * Accessible uniquement aux étudiants.
     */
    public function index(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'student'
        // Sinon, le redirige vers le tableau de bord
        if ($_SESSION['user']['role'] !== 'student') {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID de l'étudiant connecté depuis la session
        $studentUserId = (int) $_SESSION['user']['id'];

        // Crée une instance du modèle WishlistModel
        $wishlistModel = new WishlistModel();

        // Compte le nombre total d'offres dans la wish-list de l'étudiant
        $totalOffers = $wishlistModel->countWishlistForStudent($studentUserId);
        // Crée un objet Paginator pour gérer la pagination (8 offres par page)
        $paginator = new Paginator($totalOffers, 8);

        // Récupère les offres de la wish-list pour la page actuelle
        $offers = $wishlistModel->getWishlistOffersForStudent(
            $studentUserId,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        // Affiche la vue avec les offres paginées
        $this->render('wishlist/index.html.twig', [
            'pageTitle' => 'MA WISH-LIST',
            'offers' => $offers,
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
            'basePath' => '/wishlist'
        ]);
    }

    /**
     * toggle()
     * 
     * Ajoute ou retire une offre de la wish-list de l'étudiant connecté.
     * Cette fonction est appelée via AJAX et retourne une réponse JSON.
     * Accessible uniquement aux étudiants.
     */
    public function toggle(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'student'
        // Si ce n'est pas le cas, retourne une erreur 403 (Accès refusé) en JSON
        if ($_SESSION['user']['role'] !== 'student') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Accès refusé.'
            ], 403);
        }

        // Récupère l'ID de l'offre depuis les données POST
        $offerId = (int) ($_POST['offer_id'] ?? 0);

        // Vérifie que l'ID de l'offre est valide
        // Si ce n'est pas le cas, retourne une erreur 400 (Requête invalide) en JSON
        if ($offerId <= 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Offre invalide.'
            ], 400);
        }

        // Crée une instance du modèle OfferModel et récupère l'offre
        $offerModel = new OfferModel();
        $offer = $offerModel->findById($offerId);

        // Vérifie que l'offre existe et est valide (is_valid = 1)
        // Si ce n'est pas le cas, retourne une erreur 404 (Non trouvé) en JSON
        if (!$offer || !(bool) $offer['is_valid']) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Offre introuvable.'
            ], 404);
        }

        // Crée une instance du modèle WishlistModel
        $wishlistModel = new WishlistModel();
        // Ajoute ou retire l'offre de la wish-list (bascule)
        $result = $wishlistModel->toggleWishlist((int) $_SESSION['user']['id'], $offerId);

        // Si l'opération a échoué, retourne une erreur 500 (Erreur serveur) en JSON
        if (!$result['success']) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue.'
            ], 500);
        }

        // Retourne une réponse JSON avec le succès et le nouvel état (ajoutée ou retirée)
        $this->jsonResponse([
            'success' => true,
            'inWishlist' => $result['inWishlist'],
            // Message dynamique selon si l'offre est dans la wish-list ou non
            'message' => $result['inWishlist']
                ? 'Offre ajoutée à la wish-list.'
                : 'Offre retirée de la wish-list.'
        ]);
    }

    /**
     * jsonResponse()
     * 
     * Envoie une réponse JSON au client avec un code HTTP spécifique.
     * Méthode privée utilisée pour créer des réponses AJAX.
     * 
     * @param array $data Données à encoder en JSON
     * @param int $statusCode Code HTTP à envoyer (200 par défaut)
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        // Définit le code HTTP de la réponse
        http_response_code($statusCode);
        // Définit le type de contenu comme JSON
        header('Content-Type: application/json; charset=utf-8');
        // Encode et affiche les données en JSON
        echo json_encode($data);
        // Arrête l'exécution du script
        exit;
    }
}