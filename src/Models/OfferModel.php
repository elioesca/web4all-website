<?php

namespace App\Models;

use PDO;
use Throwable;

/**
 * OfferModel
 *
 * Gère les offres : recherche, filtres, statistiques, création/mise à jour,
 * activation/désactivation et suivi des compétences.
 */
class OfferModel
{
    private PDO $db;

    /**
     * Initialise la connexion à la base de données.
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère une liste d'offres publiques avec filtres et pagination.
     *
     * @param string $keyword Recherche texte sur titre, description et nom de la société
     * @param string $skillId Filtre optionnel sur compétence
     * @param string $duration Filtre optionnel sur durée
     * @param int $limit Nombre maximum d'offres retournées
     * @param int $offset Décalage pour la pagination
     * @return array Offres trouvées
     */
    public function getOffers(
        string $keyword = '',
        string $skillId = '',
        string $duration = '',
        int $limit = 9,
        int $offset = 0
    ): array {
        $sql = "
            SELECT DISTINCT
                o.offer_id,
                o.title,
                o.description,
                o.duration,
                o.salary,
                o.available_places,
                o.publication_date,
                o.is_valid,
                c.name AS company_name
            FROM offer o
            INNER JOIN company c ON c.company_id = o.company_id
            LEFT JOIN offer_skill os ON os.offer_id = o.offer_id
            LEFT JOIN skill s ON s.skill_id = os.skill_id
            WHERE (
                o.title LIKE :keyword
                OR o.description LIKE :keyword
                OR c.name LIKE :keyword
            )
            AND o.is_valid = 1
        ";

        $params = [
            'keyword' => '%' . $keyword . '%'
        ];

        if ($skillId !== '') {
            $sql .= " AND s.skill_id = :skill_id";
            $params['skill_id'] = (int) $skillId;
        }

        if ($duration !== '') {
            $sql .= " AND o.duration = :duration";
            $params['duration'] = (int) $duration;
        }

        $sql .= " ORDER BY o.publication_date DESC, o.title ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Compte le nombre d'offres publiques correspondant à des filtres.
     *
     * @param string $keyword
     * @param string $skillId
     * @param string $duration
     * @return int Nombre total d'offres
     */
    public function countOffers(string $keyword = '', string $skillId = '', string $duration = ''): int
    {
        $sql = "
            SELECT COUNT(DISTINCT o.offer_id) AS total
            FROM offer o
            INNER JOIN company c ON c.company_id = o.company_id
            LEFT JOIN offer_skill os ON os.offer_id = o.offer_id
            LEFT JOIN skill s ON s.skill_id = os.skill_id
            WHERE (
                o.title LIKE :keyword
                OR o.description LIKE :keyword
                OR c.name LIKE :keyword
            )
            AND o.is_valid = 1
        ";

        $params = [
            'keyword' => '%' . $keyword . '%'
        ];

        if ($skillId !== '') {
            $sql .= " AND s.skill_id = :skill_id";
            $params['skill_id'] = (int) $skillId;
        }

        if ($duration !== '') {
            $sql .= " AND o.duration = :duration";
            $params['duration'] = (int) $duration;
        }

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Liste toutes les compétences disponibles.
     *
     * @return array Skills triées par nom
     */
    public function getSkills(): array
    {
        $stmt = $this->db->query("
            SELECT skill_id, name
            FROM skill
            ORDER BY name ASC
        ");

        return $stmt->fetchAll();
    }

    /**
     * Récupère les sociétés actives.
     *
     * @return array Companies valides triées par nom
     */
    public function getCompanies(): array
    {
        $stmt = $this->db->query("
            SELECT company_id, name
            FROM company
            WHERE is_valid = 1
            ORDER BY name ASC
        ");

        return $stmt->fetchAll();
    }

    /**
     * Récupère les types d'offres existants.
     *
     * @return array Types d'offres triés par nom
     */
    public function getOfferTypes(): array
    {
        $stmt = $this->db->query("
            SELECT offer_type_id, name
            FROM offer_type
            ORDER BY name ASC
        ");

        return $stmt->fetchAll();
    }

    /**
     * Récupère les détails d'une offre par ID, incluant le nombre de candidatures.
     *
     * @param int $offerId
     * @return array|false Offre ou false si inexistante
     */
    public function findById(int $offerId): array|false
    {
        $sql = "
            SELECT
            o.offer_id,
            o.title,
            o.description,
            o.content,
            o.duration,
            o.salary,
            o.available_places,
            o.publication_date,
            o.is_valid,
            o.company_id,
            o.offer_type_id,
            c.name AS company_name,
            COUNT(DISTINCT a.application_id) AS application_count
            FROM offer o
            INNER JOIN company c ON c.company_id = o.company_id
            LEFT JOIN application a ON a.offer_id = o.offer_id
            WHERE o.offer_id = :offer_id
            GROUP BY o.offer_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'offer_id' => $offerId
        ]);

        return $stmt->fetch();
    }

    /**
     * Récupère les IDs de compétences associées à une offre.
     *
     * @param int $offerId
     * @return int[] Liste d'IDs skills
     */
    public function getOfferSkillIds(int $offerId): array
    {
        $stmt = $this->db->prepare("
            SELECT skill_id
            FROM offer_skill
            WHERE offer_id = :offer_id
        ");

        $stmt->execute([
            'offer_id' => $offerId
        ]);

        $rows = $stmt->fetchAll();

        return array_map(fn($row) => (int) $row['skill_id'], $rows);
    }

    /**
     * Récupère les compétences détaillées d'une offre.
     *
     * @param int $offerId
     * @return array Skills de l'offre
     */
    public function getOfferSkills(int $offerId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.skill_id, s.name
            FROM offer_skill os
            INNER JOIN skill s ON s.skill_id = os.skill_id
            WHERE os.offer_id = :offer_id
            ORDER BY s.name ASC
        ");

        $stmt->execute([
            'offer_id' => $offerId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Crée une nouvelle offre et ses relations de compétences.
     * Utilise une transaction pour cohérence.
     *
     * @param string $title
     * @param string $description
     * @param string $content
     * @param int $duration
     * @param float $salary
     * @param int $availablePlaces
     * @param int $companyId
     * @param int $offerTypeId
     * @param int $userId ID du créateur
     * @param array $skillIds IDs des compétences liées
     * @return bool
     */
    public function createOffer(
        string $title,
        string $description,
        string $content,
        int $duration,
        float $salary,
        int $availablePlaces,
        int $companyId,
        int $offerTypeId,
        int $userId,
        array $skillIds
    ): bool {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO offer (
                    title, description, content, duration, salary, available_places,
                    publication_date, is_valid, company_id, offer_type_id, created_by_user_id
                )
                VALUES (
                    :title, :description, :content, :duration, :salary, :available_places,
                    NOW(), 1, :company_id, :offer_type_id, :created_by_user_id
                )
            ");

            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'content' => $content,
                'duration' => $duration,
                'salary' => $salary,
                'available_places' => $availablePlaces,
                'company_id' => $companyId,
                'offer_type_id' => $offerTypeId,
                'created_by_user_id' => $userId
            ]);

            $offerId = (int) $this->db->lastInsertId();

            $stmtSkill = $this->db->prepare("
                INSERT INTO offer_skill (offer_id, skill_id)
                VALUES (:offer_id, :skill_id)
            ");

            foreach ($skillIds as $skillId) {
                $stmtSkill->execute([
                    'offer_id' => $offerId,
                    'skill_id' => (int) $skillId
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            die($e->getMessage());
        }
    }

    /**
     * Met à jour une offre et réécrit ses compétences associées.
     * Transactionnel pour rollback en cas d'erreur.
     *
     * @param int $offerId
     * @param string $title
     * @param string $description
     * @param string $content
     * @param int $duration
     * @param float $salary
     * @param int $availablePlaces
     * @param int $companyId
     * @param int $offerTypeId
     * @param array $skillIds
     * @return bool
     */
    public function updateOffer(
        int $offerId,
        string $title,
        string $description,
        string $content,
        int $duration,
        float $salary,
        int $availablePlaces,
        int $companyId,
        int $offerTypeId,
        array $skillIds
    ): bool {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE offer
                SET title = :title,
                    description = :description,
                    content = :content,
                    duration = :duration,
                    salary = :salary,
                    available_places = :available_places,
                    company_id = :company_id,
                    offer_type_id = :offer_type_id
                WHERE offer_id = :offer_id
            ");

            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'content' => $content,
                'duration' => $duration,
                'salary' => $salary,
                'available_places' => $availablePlaces,
                'company_id' => $companyId,
                'offer_type_id' => $offerTypeId,
                'offer_id' => $offerId
            ]);

            $stmtDelete = $this->db->prepare("
                DELETE FROM offer_skill
                WHERE offer_id = :offer_id
            ");
            $stmtDelete->execute([
                'offer_id' => $offerId
            ]);

            $stmtSkill = $this->db->prepare("
                INSERT INTO offer_skill (offer_id, skill_id)
                VALUES (:offer_id, :skill_id)
            ");

            foreach ($skillIds as $skillId) {
                $stmtSkill->execute([
                    'offer_id' => $offerId,
                    'skill_id' => (int) $skillId
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            die($e->getMessage());
        }
    }

    /**
     * Désactive une offre sans la supprimer physiquement.
     *
     * @param int $offerId
     * @return bool
     */
    public function deactivateOffer(int $offerId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE offer
            SET is_valid = 0
            WHERE offer_id = :offer_id
        ");

        return $stmt->execute([
            'offer_id' => $offerId
        ]);
    }

    /**
     * Retourne le nombre total d'offres actives.
     *
     * @return int
     */
    public function getTotalActiveOffers(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM offer
            WHERE is_valid = 1
        ");

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Calcule le nombre moyen de candidatures par offre active.
     *
     * @return float
     */
    public function getAverageApplicationsPerOffer(): float
    {
        $stmt = $this->db->query("
            SELECT AVG(app_count) AS average_applications
            FROM (
                SELECT o.offer_id, COUNT(a.application_id) AS app_count
                FROM offer o
                LEFT JOIN application a ON a.offer_id = o.offer_id
                WHERE o.is_valid = 1
                GROUP BY o.offer_id
            ) AS stats
        ");

        $result = $stmt->fetch();

        return (float) ($result['average_applications'] ?? 0);
    }

    /**
     * Retourne la distribution des offres actives par durée.
     *
     * @return array Durée => nombre d'offres
     */
    public function getOfferDistributionByDuration(): array
    {
        $stmt = $this->db->query("
            SELECT duration, COUNT(*) AS total
            FROM offer
            WHERE is_valid = 1
            GROUP BY duration
            ORDER BY duration ASC
        ");

        return $stmt->fetchAll();
    }

    /**
     * Récupère les offres les plus ajoutées en wishlist.
     *
     * @param int $limit Nombre d'offres à retourner (défaut 5)
     * @return array
     */
    public function getTopWishlistedOffers(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.offer_id,
                o.title,
                COUNT(w.offer_id) AS wishlist_count
            FROM offer o
            LEFT JOIN wishlist w ON w.offer_id = o.offer_id
            WHERE o.is_valid = 1
            GROUP BY o.offer_id
            ORDER BY wishlist_count DESC, o.title ASC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}