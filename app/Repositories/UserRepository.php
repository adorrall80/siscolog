<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Core\Database;
use PDO;

final class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return User[]
     */
    public function all(): array
    {
        $statement = $this->db->query('SELECT * FROM users ORDER BY name ASC');

        return array_map(
            fn (array $row): User => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO users (
                name,
                email,
                password_hash,
                role,
                status,
                profession,
                specialty,
                professional_license,
                phone,
                clinic_name,
                clinic_address,
                professional_bio,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :name,
                :email,
                :password_hash,
                :role,
                :status,
                :profession,
                :specialty,
                :professional_license,
                :phone,
                :clinic_name,
                :clinic_address,
                :professional_bio,
                :is_active,
                NOW(),
                NOW()
             )'
        );

        $statement->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'],
            'status' => $data['status'],
            'profession' => $data['profession'] ?? null,
            'specialty' => $data['specialty'] ?? null,
            'professional_license' => $data['professional_license'] ?? null,
            'phone' => $data['phone'] ?? null,
            'clinic_name' => $data['clinic_name'] ?? null,
            'clinic_address' => $data['clinic_address'] ?? null,
            'professional_bio' => $data['professional_bio'] ?? null,
            'is_active' => (int) $data['is_active'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?User
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->map($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();

        return $row ? $this->map($row) : null;
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE users
                SET name = :name,
                    email = :email,
                    role = :role,
                    status = :status,
                    profession = :profession,
                    specialty = :specialty,
                    professional_license = :professional_license,
                    phone = :phone,
                    clinic_name = :clinic_name,
                    clinic_address = :clinic_address,
                    professional_bio = :professional_bio,
                    is_active = :is_active,
                    updated_at = NOW()';
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status'],
            'profession' => $data['profession'] ?? null,
            'specialty' => $data['specialty'] ?? null,
            'professional_license' => $data['professional_license'] ?? null,
            'phone' => $data['phone'] ?? null,
            'clinic_name' => $data['clinic_name'] ?? null,
            'clinic_address' => $data['clinic_address'] ?? null,
            'professional_bio' => $data['professional_bio'] ?? null,
            'is_active' => (int) $data['is_active'],
        ];

        if (($data['password_hash'] ?? '') !== '') {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }

        $sql .= ' WHERE id = :id';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    /**
     * @return string[]
     */
    public function attentionModalitiesForUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT sm.code
             FROM user_attention_modalities uam
             INNER JOIN session_modalities sm ON sm.id = uam.session_modality_id
             WHERE uam.user_id = :user_id
               AND uam.is_active = 1
               AND sm.is_active = 1
             ORDER BY sm.sort_order ASC, sm.name ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @param string[] $codes
     */
    public function syncAttentionModalities(int $userId, array $codes): void
    {
        $codes = array_values(array_unique(array_filter(array_map('strval', $codes))));

        $this->db->beginTransaction();

        $this->db->prepare(
            'UPDATE user_attention_modalities
             SET is_active = 0, updated_at = NOW()
             WHERE user_id = :user_id'
        )->execute(['user_id' => $userId]);

        if ($codes !== []) {
            $placeholders = implode(',', array_fill(0, count($codes), '?'));
            $modalityStatement = $this->db->prepare(
                "SELECT id FROM session_modalities WHERE code IN ({$placeholders}) AND is_active = 1"
            );
            $modalityStatement->execute($codes);
            $modalityIds = array_map('intval', $modalityStatement->fetchAll(PDO::FETCH_COLUMN));

            $relationStatement = $this->db->prepare(
                'INSERT INTO user_attention_modalities (
                    user_id,
                    session_modality_id,
                    is_active,
                    created_at,
                    updated_at
                 )
                 VALUES (
                    :user_id,
                    :session_modality_id,
                    1,
                    NOW(),
                    NOW()
                 )
                 ON DUPLICATE KEY UPDATE
                    is_active = 1,
                    updated_at = NOW()'
            );

            foreach ($modalityIds as $modalityId) {
                $relationStatement->execute([
                    'user_id' => $userId,
                    'session_modality_id' => $modalityId,
                ]);
            }
        }

        $this->db->commit();
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->db->prepare(
            'UPDATE users SET is_active = :is_active, updated_at = NOW() WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'is_active' => $active ? 1 : 0,
        ]);
    }

    public function existsEmail(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    private function map(array $row): User
    {
        return new User(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            (string) $row['role'],
            (string) $row['status'],
            (bool) $row['is_active'],
            (string) ($row['password_hash'] ?? ''),
            (string) ($row['profession'] ?? ''),
            (string) ($row['specialty'] ?? ''),
            (string) ($row['professional_license'] ?? ''),
            (string) ($row['phone'] ?? ''),
            (string) ($row['clinic_name'] ?? ''),
            (string) ($row['clinic_address'] ?? ''),
            (string) ($row['professional_bio'] ?? '')
        );
    }
}
