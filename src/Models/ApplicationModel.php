<?php

namespace App\Models;

use PDO;
use Throwable;

/**
 * ApplicationModel
 *
 * Gestion des candidatures : vérification d'existence, sélection du statut par défaut
 * et insertion de nouvelles candidatures.
 */
class ApplicationModel
{
    private PDO $db;

    /**
     * Initialise la connexion à la base de données via singleton.
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Vérifie si un étudiant a déjà postulé pour une offre donnée.
     *
     * @param int $studentUserId
     * @param int $offerId
     * @return bool true si une candidature existe, false sinon
     */
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

    /**
     * Récupère le statut d'application par défaut (le plus petit ID).
     *
     * @return int ID du statut par défaut
     */
    public function getDefaultApplicationStatusId(): int
    {
        $stmt = $this->db->query("
            SELECT MIN(application_status_id) AS first_status_id
            FROM application_status
        ");

        $result = $stmt->fetch();

        return (int) ($result['first_status_id'] ?? 1);
    }

    /**
     * Crée une candidature pour une offre.
     *
     * @param int $studentUserId
     * @param int $offerId
     * @param string $cvPath Chemin du CV téléversé
     * @param string $coverLetterPath Chemin de la lettre de motivation téléversée
     * @return bool true si création réussie
     */
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