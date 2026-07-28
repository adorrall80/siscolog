<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database;

final class UserSeeder
{
    public function run(): void
    {
        $db = Database::connection();
        $statement = $db->prepare(
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
                1,
                NOW(),
                NOW()
             )
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                password_hash = VALUES(password_hash),
                role = VALUES(role),
                status = VALUES(status),
                profession = VALUES(profession),
                specialty = VALUES(specialty),
                professional_license = VALUES(professional_license),
                phone = VALUES(phone),
                clinic_name = VALUES(clinic_name),
                clinic_address = VALUES(clinic_address),
                professional_bio = VALUES(professional_bio),
                is_active = 1,
                updated_at = NOW()'
        );

        $users = [
            [
                'name' => 'Administrador SisColog',
                'email' => 'admin@siscolog.local',
                'password' => '1',
                'role' => 'administrador',
                'status' => 'activo',
                'profession' => '',
                'specialty' => '',
                'professional_license' => '',
                'phone' => '',
                'clinic_name' => '',
                'clinic_address' => '',
                'professional_bio' => '',
                'modalities' => [],
            ],
            [
                'name' => 'Profesional Demo',
                'email' => 'profesional@siscolog.local',
                'password' => '1',
                'role' => 'profesional',
                'status' => 'activo',
                'profession' => 'Profesional de atencion',
                'specialty' => 'Salud mental y acompanamiento terapeutico',
                'professional_license' => 'REG-PRO-001',
                'phone' => '+56911111111',
                'clinic_name' => 'Consulta SisColog',
                'clinic_address' => 'Atencion demo',
                'professional_bio' => 'Usuario profesional base para pruebas funcionales del sistema.',
                'modalities' => ['presencial', 'online'],
            ],
            [
                'name' => 'Supervisor Demo',
                'email' => 'supervisor@siscolog.local',
                'password' => '1',
                'role' => 'supervisor',
                'status' => 'activo',
                'profession' => 'Supervisor clinico',
                'specialty' => 'Revision y acompanamiento de casos',
                'professional_license' => 'REG-SUP-001',
                'phone' => '+56922222222',
                'clinic_name' => 'Consulta SisColog',
                'clinic_address' => 'Atencion demo',
                'professional_bio' => 'Usuario supervisor base para revisar flujos de supervision.',
                'modalities' => ['online'],
            ],
        ];

        foreach ($users as $user) {
            $statement->execute([
                'name' => $user['name'],
                'email' => $user['email'],
                'password_hash' => password_hash($user['password'], PASSWORD_DEFAULT),
                'role' => $user['role'],
                'status' => $user['status'],
                'profession' => $user['profession'] ?: null,
                'specialty' => $user['specialty'] ?: null,
                'professional_license' => $user['professional_license'] ?: null,
                'phone' => $user['phone'] ?: null,
                'clinic_name' => $user['clinic_name'] ?: null,
                'clinic_address' => $user['clinic_address'] ?: null,
                'professional_bio' => $user['professional_bio'] ?: null,
            ]);

            $this->syncModalities($db, $user['email'], $user['modalities']);
        }
    }

    /**
     * @param string[] $modalities
     */
    private function syncModalities(\PDO $db, string $email, array $modalities): void
    {
        if ($modalities === []) {
            return;
        }

        $userIdStatement = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $userIdStatement->execute(['email' => $email]);
        $userId = (int) $userIdStatement->fetchColumn();

        if ($userId === 0) {
            return;
        }

        $modalityStatement = $db->prepare('SELECT id FROM session_modalities WHERE code = :code AND is_active = 1 LIMIT 1');
        $relationStatement = $db->prepare(
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

        foreach ($modalities as $code) {
            $modalityStatement->execute(['code' => $code]);
            $modalityId = (int) $modalityStatement->fetchColumn();

            if ($modalityId === 0) {
                continue;
            }

            $relationStatement->execute([
                'user_id' => $userId,
                'session_modality_id' => $modalityId,
            ]);
        }
    }
}
