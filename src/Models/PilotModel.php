<?php

namespace App\Models;

use PDO;
use Throwable;

/**
 * PilotModel
 *
 * Gère la logique des pilotes : lecture, création, mise à jour, activation/
 * désactivation et gestion des promotions associées.
 */
class PilotModel
{
    private PDO $db;

    /**
     * Constructeur : initialise la connexion à la base de données.
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère la liste des pilotes filtrés par texte avec pagination.
     *
     * @param string $search Terme de recherche (nom, prénom, email)
     * @param int $limit Nombre max de résultats
     * @param int $offset Décalage pour la pagination
     * @return array Résultats des pilotes
     */
    public function getPilots(string $search = '', int $limit = 10, int $offset = 0): array
    {
        $searchValue = '%' . $search . '%';

        $sql = "
            SELECT
                u.user_id,
                u.last_name,
                u.first_name,
                u.email,
                u.phone_number,
                u.is_valid
            FROM pilot p
            INNER JOIN user u ON u.user_id = p.user_id
            WHERE (
                u.last_name LIKE :search
                OR u.first_name LIKE :search
                OR u.email LIKE :search
            )
            ORDER BY u.last_name ASC, u.first_name ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':search', $searchValue, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Compte le nombre de pilotes correspondant au filtre de recherche.
     *
     * @param string $search Terme de recherche (facultatif)
     * @return int Nombre total de pilotes
     */
    public function countPilots(string $search = ''): int
    {
        $searchValue = '%' . $search . '%';

        $sql = "
            SELECT COUNT(*) AS total
            FROM pilot p
            INNER JOIN user u ON u.user_id = p.user_id
            WHERE (
                u.last_name LIKE :search
                OR u.first_name LIKE :search
                OR u.email LIKE :search
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'search' => $searchValue
        ]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Récupère un pilote par son user_id.
     *
     * @param int $userId
     * @return array|false Détails du pilote ou false si introuvable
     */
    public function findPilotById(int $userId): array|false
    {
        $sql = "
            SELECT
                u.user_id,
                u.last_name,
                u.first_name,
                u.email,
                u.phone_number,
                u.is_valid
            FROM pilot p
            INNER JOIN user u ON u.user_id = p.user_id
            WHERE p.user_id = :user_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetch();
    }

    /**
     * Crée un nouveau pilote (user + pilot + affectations promotions).
     * Transactionnel : rollback si une étape échoue.
     *
     * @param string $lastName
     * @param string $firstName
     * @param string $email
     * @param string|null $phoneNumber
     * @param string $password
     * @param array $promotionIds Liste d'IDs de promotions attribuées
     * @return bool true si la création a réussi
     */
    public function createPilot(
        string $lastName,
        string $firstName,
        string $email,
        ?string $phoneNumber,
        string $password,
        array $promotionIds
    ): bool {
        try {
            $this->db->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmtUser = $this->db->prepare("
                INSERT INTO user (last_name, first_name, email, password, phone_number, is_valid)
                VALUES (:last_name, :first_name, :email, :password, :phone_number, 1)
            ");

            $stmtUser->execute([
                'last_name' => $lastName,
                'first_name' => $firstName,
                'email' => $email,
                'password' => $hashedPassword,
                'phone_number' => $phoneNumber
            ]);

            $userId = (int) $this->db->lastInsertId();

            $stmtPilot = $this->db->prepare("
                INSERT INTO pilot (user_id)
                VALUES (:user_id)
            ");

            $stmtPilot->execute([
                'user_id' => $userId
            ]);

            $stmtPromotion = $this->db->prepare("
                INSERT INTO pilot_promotion (pilot_user_id, promotion_id)
                VALUES (:pilot_user_id, :promotion_id)
            ");

            foreach ($promotionIds as $promotionId) {
                $stmtPromotion->execute([
                    'pilot_user_id' => $userId,
                    'promotion_id' => (int) $promotionId
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Met à jour un pilote et ses informations promotionnelles.
     *
     * @param int $userId
     * @param string $lastName
     * @param string $firstName
     * @param string $email
     * @param string|null $phoneNumber
     * @param string|null $password Nouveau mot de passe (nullable)
     * @param array $promotionIds Liste d'IDs de promotions (remplace l'existant)
     * @return bool true si la mise à jour a réussi
     */
    public function updatePilot(
        int $userId,
        string $lastName,
        string $firstName,
        string $email,
        ?string $phoneNumber,
        ?string $password,
        array $promotionIds
    ): bool {
        try {
            $this->db->beginTransaction();

            if ($password !== null && $password !== '') {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $this->db->prepare("
                    UPDATE user
                    SET last_name = :last_name,
                        first_name = :first_name,
                        email = :email,
                        phone_number = :phone_number,
                        password = :password
                    WHERE user_id = :user_id
                ");

                $stmt->execute([
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'password' => $hashedPassword,
                    'user_id' => $userId
                ]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE user
                    SET last_name = :last_name,
                        first_name = :first_name,
                        email = :email,
                        phone_number = :phone_number
                    WHERE user_id = :user_id
                ");

                $stmt->execute([
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'user_id' => $userId
                ]);
            }

            $stmtDelete = $this->db->prepare("
                DELETE FROM pilot_promotion
                WHERE pilot_user_id = :user_id
            ");

            $stmtDelete->execute([
                'user_id' => $userId
            ]);

            $stmtInsert = $this->db->prepare("
                INSERT INTO pilot_promotion (pilot_user_id, promotion_id)
                VALUES (:pilot_user_id, :promotion_id)
            ");

            foreach ($promotionIds as $promotionId) {
                $stmtInsert->execute([
                    'pilot_user_id' => $userId,
                    'promotion_id' => (int) $promotionId
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Désactive le compte d'un pilote.
     *
     * @param int $userId
     * @return bool true si la désactivation a réussi
     */
    public function deactivatePilot(int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE user
            SET is_valid = 0
            WHERE user_id = :user_id
        ");

        return $stmt->execute([
            'user_id' => $userId
        ]);
    }

    /**
     * Récupère toutes les promotions disponibles (pour listes déroulantes).
     *
     * @return array liste des promotions
     */
    public function getPromotions(): array
    {
        $stmt = $this->db->query("
            SELECT promotion_id, name
            FROM promotion
            ORDER BY name ASC
        ");

        return $stmt->fetchAll();
    }

    /**
     * Récupère les IDs des promotions assignées à un pilote.
     *
     * @param int $userId ID du pilote
     * @return array IDs de promotions
     */
    public function getPilotPromotionIds(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT promotion_id
            FROM pilot_promotion
            WHERE pilot_user_id = :user_id
        ");

        $stmt->execute([
            'user_id' => $userId
        ]);

        $rows = $stmt->fetchAll();

        return array_map(fn($row) => (int) $row['promotion_id'], $rows);
    }

    /**
     * Réactive le compte d'un pilote.
     *
     * @param int $userId
     * @return bool true si la réactivation a réussi
     */
    public function reactivatePilot(int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE user
            SET is_valid = 1
            WHERE user_id = :user_id
        ");

        return $stmt->execute([
            'user_id' => $userId
        ]);
    }
}