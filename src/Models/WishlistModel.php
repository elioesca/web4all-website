<?php

namespace App\Models;

use PDO;
use Throwable;

/**
 * WishlistModel
 *
 * Gère la table wishlist et ses opérations :
 * - récupération d'IDs d'offres sauvegardées
 * - vérification d'existence en wishlist
 * - ajout/suppression/bascule
 * - pagination des offres sauvegardées
 */
class WishlistModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère toutes les IDs des offres de la wishlist d'un étudiant.
     *
     * @param int $studentUserId ID de l'étudiant
     * @return int[] Liste des ID d'offres
     */
    public function getWishlistOfferIdsForStudent(int $studentUserId): array
    {
        $stmt = $this->db->prepare("
            SELECT offer_id
            FROM wishlist
            WHERE student_user_id = :student_user_id
        ");

        $stmt->execute([
            'student_user_id' => $studentUserId
        ]);

        $rows = $stmt->fetchAll();

        return array_map(fn($row) => (int) $row['offer_id'], $rows);
    }

    /**
     * Vérifie si une offre est dans la wishlist d'un étudiant.
     *
     * @param int $studentUserId ID de l'étudiant
     * @param int $offerId ID de l'offre
     * @return bool true si l'offre est en wishlist, false sinon
     */
    public function isOfferInWishlist(int $studentUserId, int $offerId): bool
    {
        $stmt = $this->db->prepare("
            SELECT offer_id
            FROM wishlist
            WHERE student_user_id = :student_user_id
              AND offer_id = :offer_id
            LIMIT 1
        ");

        $stmt->execute([
            'student_user_id' => $studentUserId,
            'offer_id' => $offerId
        ]);

        return (bool) $stmt->fetch();
    }

    /**
     * Ajoute une offre à la wishlist d'un étudiant.
     *
     * @param int $studentUserId ID de l'étudiant
     * @param int $offerId ID de l'offre
     * @return bool true si l'ajout a réussi, false en cas d'erreur (doublon, clé étrangère invalide, etc.)
     */
    public function addToWishlist(int $studentUserId, int $offerId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO wishlist (student_user_id, offer_id)
                VALUES (:student_user_id, :offer_id)
            ");

            return $stmt->execute([
                'student_user_id' => $studentUserId,
                'offer_id' => $offerId
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Supprime une offre de la wishlist d'un étudiant.
     *
     * @param int $studentUserId ID de l'étudiant
     * @param int $offerId ID de l'offre
     * @return bool true si la suppression a réussi
     */
    public function removeFromWishlist(int $studentUserId, int $offerId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM wishlist
            WHERE student_user_id = :student_user_id
              AND offer_id = :offer_id
        ");

        return $stmt->execute([
            'student_user_id' => $studentUserId,
            'offer_id' => $offerId
        ]);
    }

    /**
     * Bascule l'état de wishlist pour une offre afin de gérer l'interface d'ajout/suppression.
     * Si l'offre est déjà dans la wishlist, elle est retirée, sinon elle est ajoutée.
     *
     * @param int $studentUserId ID de l'étudiant
     * @param int $offerId ID de l'offre
     * @return array ['success' => bool, 'inWishlist' => bool]
     */
    public function toggleWishlist(int $studentUserId, int $offerId): array
    {
        if ($this->isOfferInWishlist($studentUserId, $offerId)) {
            $success = $this->removeFromWishlist($studentUserId, $offerId);

            return [
                'success' => $success,
                'inWishlist' => false
            ];
        }

        $success = $this->addToWishlist($studentUserId, $offerId);

        return [
            'success' => $success,
            'inWishlist' => true
        ];
    }

    /**
     * Récupère le nombre total d'offres en wishlist pour un étudiant.
     *
     * @param int $studentUserId ID de l'étudiant
     * @return int nombre d'offres dans la wishlist
     */
    public function countWishlistForStudent(int $studentUserId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM wishlist
            WHERE student_user_id = :student_user_id
        " );

        $stmt->execute([
            'student_user_id' => $studentUserId
        ]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function getWishlistOffersForStudent(
        int $studentUserId,
        int $limit = 10,
        int $offset = 0
    ): array {
        $sql = "
            SELECT
                o.offer_id,
                o.title,
                o.duration,
                COALESCE(GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', '), 'Aucune') AS skills
            FROM wishlist w
            INNER JOIN offer o ON o.offer_id = w.offer_id
            LEFT JOIN offer_skill os ON os.offer_id = o.offer_id
            LEFT JOIN skill s ON s.skill_id = os.skill_id
            WHERE w.student_user_id = :student_user_id
            GROUP BY o.offer_id
            ORDER BY o.publication_date DESC, o.title ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':student_user_id', $studentUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}