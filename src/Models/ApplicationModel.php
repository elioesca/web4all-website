<?php

namespace App\Models;

use PDO;
use Throwable;

class ApplicationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function hasAlreadyApplied(int $studentUserId, int $offerId): bool
    {
        $stmt = $this->db->prepare("
            SELECT application_id
            FROM application
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

    public function getDefaultApplicationStatusId(): int
    {
        $stmt = $this->db->query("
            SELECT MIN(application_status_id) AS first_status_id
            FROM application_status
        ");

        $result = $stmt->fetch();

        return (int) ($result['first_status_id'] ?? 1);
    }

    public function createApplication(
        int $studentUserId,
        int $offerId,
        string $cvPath,
        string $coverLetterPath
    ): bool {
        try {
            $statusId = $this->getDefaultApplicationStatusId();

            $stmt = $this->db->prepare("
                INSERT INTO application (
                    application_date,
                    cv_path,
                    cover_letter_path,
                    offer_id,
                    application_status_id,
                    student_user_id
                )
                VALUES (
                    NOW(),
                    :cv_path,
                    :cover_letter_path,
                    :offer_id,
                    :application_status_id,
                    :student_user_id
                )
            ");

            return $stmt->execute([
                'cv_path' => $cvPath,
                'cover_letter_path' => $coverLetterPath,
                'offer_id' => $offerId,
                'application_status_id' => $statusId,
                'student_user_id' => $studentUserId
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}