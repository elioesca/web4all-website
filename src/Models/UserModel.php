<?php

namespace App\Models;

use PDO;

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM user WHERE email = :email LIMIT 1');
        $stmt->execute([
            'email' => $email
        ]);

        return $stmt->fetch();
    }

    public function getRoleByUserId(int $userId): ?string
    {
        $stmt = $this->db->prepare('SELECT user_id FROM administrator WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);

        if ($stmt->fetch()) {
            return 'admin';
        }

        $stmt = $this->db->prepare('SELECT user_id FROM pilot WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);

        if ($stmt->fetch()) {
            return 'pilot';
        }

        $stmt = $this->db->prepare('SELECT user_id FROM student WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);

        if ($stmt->fetch()) {
            return 'student';
        }

        return null;
    }
}