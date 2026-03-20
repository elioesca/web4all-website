<?php

namespace App\Models;

use PDO;
use Throwable;

class CompanyModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getCompanies(
        string $name = '',
        string $sector = '',
        string $rating = '',
        int $limit = 9,
        int $offset = 0
    ): array {
        $sql = "
            SELECT
                c.company_id,
                c.name,
                c.description,
                c.activity_sector,
                c.email,
                c.phone_number,
                c.is_valid,
                COALESCE(AVG(cr.rating), 0) AS average_rating
            FROM company c
            LEFT JOIN company_review cr ON cr.company_id = c.company_id
            WHERE c.name LIKE :name AND c.is_valid = 1
        ";

        $params = [
            'name' => '%' . $name . '%'
        ];

        if ($sector !== '') {
            $sql .= " AND c.activity_sector = :sector";
            $params['sector'] = $sector;
        }

        $sql .= " GROUP BY c.company_id";

        if ($rating !== '') {
            $sql .= " HAVING COALESCE(AVG(cr.rating), 0) >= :rating";
            $params['rating'] = (int) $rating;
        }

        $sql .= " ORDER BY c.name ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            if ($key === 'rating') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countCompanies(string $name = '', string $sector = '', string $rating = ''): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM (
                SELECT c.company_id
                FROM company c
                LEFT JOIN company_review cr ON cr.company_id = c.company_id
                WHERE c.name LIKE :name AND c.is_valid = 1
        ";

        $params = [
            'name' => '%' . $name . '%'
        ];

        if ($sector !== '') {
            $sql .= " AND c.activity_sector = :sector";
            $params['sector'] = $sector;
        }

        $sql .= " GROUP BY c.company_id";

        if ($rating !== '') {
            $sql .= " HAVING COALESCE(AVG(cr.rating), 0) >= :rating";
            $params['rating'] = (int) $rating;
        }

        $sql .= ") AS company_count";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            if ($key === 'rating') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function getSectors(): array
    {
        $stmt = $this->db->query("
            SELECT DISTINCT activity_sector
            FROM company
            WHERE activity_sector IS NOT NULL AND activity_sector != ''
            ORDER BY activity_sector ASC
        ");

        return $stmt->fetchAll();
    }

    public function findById(int $companyId): array|false
    {
        $sql = "
            SELECT
                c.company_id,
                c.name,
                c.description,
                c.activity_sector,
                c.email,
                c.phone_number,
                c.is_valid,
                COALESCE(AVG(cr.rating), 0) AS average_rating,
                COUNT(DISTINCT a.application_id) AS application_count
            FROM company c
            LEFT JOIN company_review cr ON cr.company_id = c.company_id
            LEFT JOIN offer o ON o.company_id = c.company_id
            LEFT JOIN application a ON a.offer_id = o.offer_id
            WHERE c.company_id = :company_id
            GROUP BY c.company_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'company_id' => $companyId
        ]);

        return $stmt->fetch();
    }

    public function getCompanyOffers(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT offer_id, title, duration
            FROM offer
            WHERE company_id = :company_id
            ORDER BY publication_date DESC
        ");

        $stmt->execute([
            'company_id' => $companyId
        ]);

        return $stmt->fetchAll();
    }

    public function getCompanyReviews(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                cr.review,
                cr.rating,
                cr.created_at,
                u.first_name,
                u.last_name
            FROM company_review cr
            INNER JOIN user u ON u.user_id = cr.user_id
            WHERE cr.company_id = :company_id
            ORDER BY cr.created_at DESC
        ");

        $stmt->execute([
            'company_id' => $companyId
        ]);

        return $stmt->fetchAll();
    }

    public function getUserReviewForCompany(int $companyId, int $userId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM company_review
            WHERE company_id = :company_id AND user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([
            'company_id' => $companyId,
            'user_id' => $userId
        ]);

        return $stmt->fetch();
    }

    public function createCompany(
        string $name,
        string $description,
        string $sector,
        string $email,
        string $phoneNumber
    ): bool {
        $stmt = $this->db->prepare("
            INSERT INTO company (name, description, activity_sector, email, phone_number, is_valid)
            VALUES (:name, :description, :activity_sector, :email, :phone_number, 1)
        ");

        return $stmt->execute([
            'name' => $name,
            'description' => $description,
            'activity_sector' => $sector,
            'email' => $email,
            'phone_number' => $phoneNumber
        ]);
    }

    public function updateCompany(
        int $companyId,
        string $name,
        string $description,
        string $sector,
        string $email,
        string $phoneNumber
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE company
            SET name = :name,
                description = :description,
                activity_sector = :activity_sector,
                email = :email,
                phone_number = :phone_number
            WHERE company_id = :company_id
        ");

        return $stmt->execute([
            'name' => $name,
            'description' => $description,
            'activity_sector' => $sector,
            'email' => $email,
            'phone_number' => $phoneNumber,
            'company_id' => $companyId
        ]);
    }

    public function deactivateCompany(int $companyId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE company
            SET is_valid = 0
            WHERE company_id = :company_id
        ");

        return $stmt->execute([
            'company_id' => $companyId
        ]);
    }

    public function saveReview(int $companyId, int $userId, int $rating, string $review): bool
    {
        try {
            $existing = $this->getUserReviewForCompany($companyId, $userId);

            if ($existing) {
                $stmt = $this->db->prepare("
                    UPDATE company_review
                    SET rating = :rating,
                        review = :review,
                        created_at = NOW()
                    WHERE company_id = :company_id
                      AND user_id = :user_id
                ");

                return $stmt->execute([
                    'rating' => $rating,
                    'review' => $review,
                    'company_id' => $companyId,
                    'user_id' => $userId
                ]);
            }

            $stmt = $this->db->prepare("
                INSERT INTO company_review (company_id, user_id, rating, review, created_at)
                VALUES (:company_id, :user_id, :rating, :review, NOW())
            ");

            return $stmt->execute([
                'company_id' => $companyId,
                'user_id' => $userId,
                'rating' => $rating,
                'review' => $review
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}