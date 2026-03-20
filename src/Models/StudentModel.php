<?php

namespace App\Models;

use PDO;
use Throwable;

class StudentModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getStudents(string $search = '', int $limit = 10, int $offset = 0): array
    {
        $searchValue = '%' . $search . '%';

        $sql = "
            SELECT
                u.user_id,
                u.last_name,
                u.first_name,
                u.email,
                u.phone_number,
                u.is_valid,
                s.promotion_id,
                s.search_status_id,
                p.name AS promotion_name,
                ss.status AS search_status
            FROM student s
            INNER JOIN user u ON u.user_id = s.user_id
            INNER JOIN promotion p ON p.promotion_id = s.promotion_id
            INNER JOIN search_status ss ON ss.search_status_id = s.search_status_id
            WHERE (
                u.last_name LIKE :search
                OR u.first_name LIKE :search
                OR u.email LIKE :search
                OR p.name LIKE :search
                OR ss.status LIKE :search
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

    public function countStudents(string $search = ''): int
    {
        $searchValue = '%' . $search . '%';

        $sql = "
            SELECT COUNT(*) AS total
            FROM student s
            INNER JOIN user u ON u.user_id = s.user_id
            INNER JOIN promotion p ON p.promotion_id = s.promotion_id
            INNER JOIN search_status ss ON ss.search_status_id = s.search_status_id
            WHERE (
                u.last_name LIKE :search
                OR u.first_name LIKE :search
                OR u.email LIKE :search
                OR p.name LIKE :search
                OR ss.status LIKE :search
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'search' => $searchValue
        ]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function findStudentById(int $userId): array|false
    {
        $sql = "
            SELECT
                u.user_id,
                u.last_name,
                u.first_name,
                u.email,
                u.phone_number,
                u.is_valid,
                s.promotion_id,
                s.search_status_id
            FROM student s
            INNER JOIN user u ON u.user_id = s.user_id
            WHERE s.user_id = :user_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetch();
    }

    public function getPromotions(): array
    {
        $stmt = $this->db->query("
            SELECT promotion_id, name
            FROM promotion
            ORDER BY name ASC
        ");

        return $stmt->fetchAll();
    }

    public function getSearchStatuses(): array
    {
        $stmt = $this->db->query("
            SELECT search_status_id, status
            FROM search_status
            ORDER BY search_status_id ASC
        ");

        return $stmt->fetchAll();
    }

    public function createStudent(
        string $lastName,
        string $firstName,
        string $email,
        ?string $phoneNumber,
        string $password,
        int $promotionId,
        int $searchStatusId
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

            $stmtStudent = $this->db->prepare("
                INSERT INTO student (user_id, search_status_id, promotion_id)
                VALUES (:user_id, :search_status_id, :promotion_id)
            ");

            $stmtStudent->execute([
                'user_id' => $userId,
                'search_status_id' => $searchStatusId,
                'promotion_id' => $promotionId
            ]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateStudent(
        int $userId,
        string $lastName,
        string $firstName,
        string $email,
        ?string $phoneNumber,
        ?string $password,
        int $promotionId,
        int $searchStatusId
    ): bool {
        try {
            $this->db->beginTransaction();

            if ($password !== null && $password !== '') {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmtUser = $this->db->prepare("
                    UPDATE user
                    SET last_name = :last_name,
                        first_name = :first_name,
                        email = :email,
                        phone_number = :phone_number,
                        password = :password
                    WHERE user_id = :user_id
                ");

                $stmtUser->execute([
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'password' => $hashedPassword,
                    'user_id' => $userId
                ]);
            } else {
                $stmtUser = $this->db->prepare("
                    UPDATE user
                    SET last_name = :last_name,
                        first_name = :first_name,
                        email = :email,
                        phone_number = :phone_number
                    WHERE user_id = :user_id
                ");

                $stmtUser->execute([
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'user_id' => $userId
                ]);
            }

            $stmtStudent = $this->db->prepare("
                UPDATE student
                SET promotion_id = :promotion_id,
                    search_status_id = :search_status_id
                WHERE user_id = :user_id
            ");

            $stmtStudent->execute([
                'promotion_id' => $promotionId,
                'search_status_id' => $searchStatusId,
                'user_id' => $userId
            ]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deactivateStudent(int $userId): bool
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

    public function getPilotStudentIds(int $pilotUserId): array
    {
        $sql = "
            SELECT s.user_id
            FROM student s
            INNER JOIN pilot_promotion pp ON pp.promotion_id = s.promotion_id
            WHERE pp.pilot_user_id = :pilot_user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'pilot_user_id' => $pilotUserId
        ]);

        $rows = $stmt->fetchAll();

        return array_map(fn($row) => (int) $row['user_id'], $rows);
    }

    public function pilotCanAccessStudent(int $pilotUserId, int $studentUserId): bool
    {
        $sql = "
            SELECT s.user_id
            FROM student s
            INNER JOIN pilot_promotion pp ON pp.promotion_id = s.promotion_id
            WHERE pp.pilot_user_id = :pilot_user_id
              AND s.user_id = :student_user_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'pilot_user_id' => $pilotUserId,
            'student_user_id' => $studentUserId
        ]);

        return (bool) $stmt->fetch();
    }

    public function getApplicationsForPilotStudent(
        int $pilotUserId,
        int $studentUserId,
        int $limit = 10,
        int $offset = 0
    ): array {
        $sql = "
            SELECT
                o.title,
                a.application_date,
                aps.status AS application_status,
                a.cover_letter_path,
                a.cv_path
            FROM application a
            INNER JOIN offer o ON o.offer_id = a.offer_id
            INNER JOIN application_status aps ON aps.application_status_id = a.application_status_id
            INNER JOIN student s ON s.user_id = a.student_user_id
            INNER JOIN pilot_promotion pp ON pp.promotion_id = s.promotion_id
            WHERE a.student_user_id = :student_user_id
            AND pp.pilot_user_id = :pilot_user_id
            ORDER BY a.application_date DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pilot_user_id', $pilotUserId, PDO::PARAM_INT);
        $stmt->bindValue(':student_user_id', $studentUserId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countApplicationsForPilotStudent(int $pilotUserId, int $studentUserId): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM application a
            INNER JOIN student s ON s.user_id = a.student_user_id
            INNER JOIN pilot_promotion pp ON pp.promotion_id = s.promotion_id
            WHERE a.student_user_id = :student_user_id
            AND pp.pilot_user_id = :pilot_user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'pilot_user_id' => $pilotUserId,
            'student_user_id' => $studentUserId
        ]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function reactivateStudent(int $userId): bool
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

