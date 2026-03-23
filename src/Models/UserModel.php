<?php

namespace App\Models;

use PDO;

/**
 * UserModel
 *
 * Gère les opérations CRUD et utilitaires sur la table `user`, ainsi que la
 * détermination du rôle de l'utilisateur via les tables `administrator`,
 * `pilot` et `student`.
 */
class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère un utilisateur par email.
     *
     * @param string $email L'adresse email recherchée
     * @return array|false Les données utilisateur ou false si non trouvé
     */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM user WHERE email = :email LIMIT 1');
        $stmt->execute([
            'email' => $email
        ]);

        return $stmt->fetch();
    }

    /**
     * Détermine le rôle d'un utilisateur par son user_id.
     *
     * Vérifie successivement les tables administrator, pilot et student.
     *
     * @param int $userId ID de l'utilisateur
     * @return string|null 'admin', 'pilot', 'student' ou null si aucun rôle
     */
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

    /**
     * Récupère un utilisateur par son ID.
     *
     * @param int $userId ID de l'utilisateur
     * @return array|false Les données utilisateur ou false si non trouvé
     */
    public function findById(int $userId): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM user WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetch();
    }

    /**
     * Vérifie si l'email est déjà utilisé par un autre utilisateur.
     *
     * Utile pour valider la mise à jour de profil sans conflit d'email.
     *
     * @param string $email Email à vérifier
     * @param int $userId ID de l'utilisateur courant (exclu de la recherche)
     * @return bool true si un autre utilisateur utilise l'email, false sinon
     */
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

    /**
     * Met à jour les données du profil utilisateur.
     *
     * Si un nouveau mot de passe est transmis, il est haché et stocké. Sans mot de passe,
     * seules les informations de contact/personalisation sont modifiées.
     *
     * @param int $userId ID de l'utilisateur
     * @param string $lastName Nom de famille
     * @param string $firstName Prénom
     * @param string $email Email
     * @param string|null $phoneNumber Téléphone (nullable)
     * @param string|null $password Nouveau mot de passe (nullable)
     * @return bool true si la mise à jour a réussi
     */
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

    /**
     * Désactive le compte utilisateur en positionnant is_valid à 0.
     *
     * @param int $userId ID de l'utilisateur
     * @return bool true si l'opération a réussi
     */
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