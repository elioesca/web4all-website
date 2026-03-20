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

    public function findById(int $userId): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM user WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetch();
    }

    public function emailExistsForAnotherUser(string $email, int $userId): bool
    {
        $stmt = $this->db->prepare('
            SELECT user_id
            FROM user
            WHERE email = :email AND user_id != :user_id
            LIMIT 1
        ');

        $stmt->execute([
            'email' => $email,
            'user_id' => $userId
        ]);

        return (bool) $stmt->fetch();
    }

    public function updateProfile(
        int $userId,
        string $lastName,
        string $firstName,
        string $email,
        ?string $phoneNumber,
        ?string $password = null
    ): bool {
        if ($password !== null && $password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare('
                UPDATE user
                SET last_name = :last_name,
                    first_name = :first_name,
                    email = :email,
                    phone_number = :phone_number,
                    password = :password
                WHERE user_id = :user_id
            ');

            return $stmt->execute([
                'last_name' => $lastName,
                'first_name' => $firstName,
                'email' => $email,
                'phone_number' => $phoneNumber,
                'password' => $hashedPassword,
                'user_id' => $userId
            ]);
        }

        $stmt = $this->db->prepare('
            UPDATE user
            SET last_name = :last_name,
                first_name = :first_name,
                email = :email,
                phone_number = :phone_number
            WHERE user_id = :user_id
        ');

        return $stmt->execute([
            'last_name' => $lastName,
            'first_name' => $firstName,
            'email' => $email,
            'phone_number' => $phoneNumber,
            'user_id' => $userId
        ]);
    }

    public function deleteAccount(int $userId): bool {
        $stmt = $this->db->prepare('
            UPDATE user
            SET is_valid = 0
            WHERE user_id = :user_id
        ');

        return $stmt->execute([
            'user_id' => $userId
        ]);
    }
}