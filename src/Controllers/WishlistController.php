<?php

namespace App\Controllers;

use App\Models\OfferModel;
use App\Models\Paginator;
use App\Models\WishlistModel;

class WishlistController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'student') {
            $this->redirect('/dashboard');
        }

        $studentUserId = (int) $_SESSION['user']['id'];

        $wishlistModel = new WishlistModel();

        $totalOffers = $wishlistModel->countWishlistForStudent($studentUserId);
        $paginator = new Paginator($totalOffers, 8);

        $offers = $wishlistModel->getWishlistOffersForStudent(
            $studentUserId,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        $this->render('wishlist/index.html.twig', [
            'pageTitle' => 'MA WISH-LIST',
            'offers' => $offers,
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
            'basePath' => '/wishlist'
        ]);
    }

    public function toggle(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'student') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Accès refusé.'
            ], 403);
        }

        $offerId = (int) ($_POST['offer_id'] ?? 0);

        if ($offerId <= 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Offre invalide.'
            ], 400);
        }

        $offerModel = new OfferModel();
        $offer = $offerModel->findById($offerId);

        if (!$offer || !(bool) $offer['is_valid']) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Offre introuvable.'
            ], 404);
        }

        $wishlistModel = new WishlistModel();
        $result = $wishlistModel->toggleWishlist((int) $_SESSION['user']['id'], $offerId);

        if (!$result['success']) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue.'
            ], 500);
        }

        $this->jsonResponse([
            'success' => true,
            'inWishlist' => $result['inWishlist'],
            'message' => $result['inWishlist']
                ? 'Offre ajoutée à la wish-list.'
                : 'Offre retirée de la wish-list.'
        ]);
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}