<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use InvalidArgumentException;
use RuntimeException;

final class UserService
{
    private UserRepository $users;
    private MaintainerService $maintainers;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->maintainers = new MaintainerService();
    }

    /**
     * @return User[]
     */
    public function all(): array
    {
        return $this->users->all();
    }

    /**
     * @return User[]
     */
    public function filtered(string $filter, string $search = ''): array
    {
        $users = $this->all();

        $filtered = match ($filter) {
            'active' => array_values(array_filter($users, fn (User $user): bool => $user->isActive)),
            'inactive' => array_values(array_filter($users, fn (User $user): bool => !$user->isActive)),
            default => $users,
        };

        $needle = $this->normalizeSearch($search);

        if ($needle === '') {
            return $filtered;
        }

        return array_values(array_filter($filtered, function (User $user) use ($needle): bool {
            $haystack = strtolower($user->name . ' ' . $user->email . ' ' . $user->role . ' ' . $user->status);

            return str_contains($haystack, $needle);
        }));
    }

    public function normalizeFilter(string $filter): string
    {
        $aliases = [
            'todos' => 'all',
            'activos' => 'active',
            'inactivos' => 'inactive',
        ];

        $filter = $aliases[$filter] ?? $filter;

        return in_array($filter, ['all', 'active', 'inactive'], true) ? $filter : 'all';
    }

    public function normalizeSearch(string $search): string
    {
        return strtolower(trim($search));
    }

    public function create(array $data): void
    {
        $this->validate($data, true);

        $userId = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'],
            'status' => $data['status'],
            'profession' => $data['profession'] ?? '',
            'specialty' => $data['specialty'] ?? '',
            'professional_license' => $data['professional_license'] ?? '',
            'phone' => $data['phone'] ?? '',
            'clinic_name' => $data['clinic_name'] ?? '',
            'clinic_address' => $data['clinic_address'] ?? '',
            'professional_bio' => $data['professional_bio'] ?? '',
            'is_active' => (int) $data['is_active'],
        ]);
        $this->users->syncAttentionModalities($userId, $this->normalizeModalities($data['attention_modalities'] ?? []));
    }

    public function registerProfessional(array $data, bool $fromGoogle = false): User
    {
        $data['role'] = 'profesional';
        $data['status'] = 'activo';
        $data['is_active'] = 1;

        $this->validate($data, !$fromGoogle);
        $this->validateProfessionalData($data);

        $password = $fromGoogle
            ? bin2hex(random_bytes(24))
            : (string) $data['password'];

        $userId = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'profesional',
            'status' => 'activo',
            'profession' => $data['profession'],
            'specialty' => $data['specialty'],
            'professional_license' => $data['professional_license'],
            'phone' => $data['phone'],
            'clinic_name' => $data['clinic_name'],
            'clinic_address' => $data['clinic_address'],
            'professional_bio' => $data['professional_bio'],
            'is_active' => 1,
        ]);
        $this->users->syncAttentionModalities($userId, $this->normalizeModalities($data['attention_modalities'] ?? []));

        return $this->users->findByEmail((string) $data['email'])
            ?? throw new RuntimeException('No se pudo cargar el usuario registrado.');
    }

    public function registerBasicProfessional(array $data, bool $fromGoogle = false): User
    {
        $data['role'] = 'profesional';
        $data['status'] = 'activo';
        $data['is_active'] = 1;

        $this->validate($data, !$fromGoogle);

        $password = $fromGoogle
            ? bin2hex(random_bytes(24))
            : (string) $data['password'];

        $this->users->create([
            'name' => $data['name'],
            'email' => strtolower(trim((string) $data['email'])),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'profesional',
            'status' => 'activo',
            'profession' => '',
            'specialty' => '',
            'professional_license' => '',
            'phone' => '',
            'clinic_name' => '',
            'clinic_address' => '',
            'professional_bio' => '',
            'is_active' => 1,
        ]);

        return $this->users->findByEmail((string) $data['email'])
            ?? throw new RuntimeException('No se pudo cargar el usuario registrado.');
    }

    public function findByEmail(string $email): ?User
    {
        return $this->users->findByEmail($email);
    }

    public function find(int $id): User
    {
        $user = $this->users->find($id);

        if ($user === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        return $user;
    }

    public function update(int $id, array $data): void
    {
        $this->find($id);
        $this->validate($data, false, $id);

        $this->users->update($id, [
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => trim((string) ($data['password'] ?? '')) === ''
                ? ''
                : password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'],
            'status' => $data['status'],
            'profession' => $data['profession'] ?? '',
            'specialty' => $data['specialty'] ?? '',
            'professional_license' => $data['professional_license'] ?? '',
            'phone' => $data['phone'] ?? '',
            'clinic_name' => $data['clinic_name'] ?? '',
            'clinic_address' => $data['clinic_address'] ?? '',
            'professional_bio' => $data['professional_bio'] ?? '',
            'is_active' => (int) $data['is_active'],
        ]);
        $this->users->syncAttentionModalities($id, $this->normalizeModalities($data['attention_modalities'] ?? []));
    }

    public function updateProfessionalProfile(int $id, array $data): User
    {
        $user = $this->find($id);
        $this->validateProfessionalData($data);

        $this->users->update($id, [
            'name' => trim((string) ($data['name'] ?? $user->name)),
            'email' => $user->email,
            'password_hash' => '',
            'role' => $user->role,
            'status' => $user->status,
            'profession' => $data['profession'] ?? '',
            'specialty' => $data['specialty'] ?? '',
            'professional_license' => $data['professional_license'] ?? '',
            'phone' => $data['phone'] ?? '',
            'clinic_name' => $data['clinic_name'] ?? '',
            'clinic_address' => $data['clinic_address'] ?? '',
            'professional_bio' => $data['professional_bio'] ?? '',
            'is_active' => $user->isActive ? 1 : 0,
        ]);
        $this->users->syncAttentionModalities($id, $this->normalizeModalities($data['attention_modalities'] ?? []));

        return $this->find($id);
    }

    public function professionalProfileComplete(User $user): bool
    {
        if ($user->role !== 'profesional') {
            return true;
        }

        return trim($user->profession) !== ''
            && trim($user->specialty) !== ''
            && trim($user->phone) !== ''
            && trim($user->clinicName) !== ''
            && $user->id !== null
            && $this->attentionModalitiesForUser($user->id) !== [];
    }

    /**
     * @return string[]
     */
    public function attentionModalitiesForUser(int $userId): array
    {
        return $this->users->attentionModalitiesForUser($userId);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->find($id);
        $this->users->setActive($id, $active);
    }

    /**
     * @param User[] $users
     * @return array{total: int, active: int, inactive: int}
     */
    public function summary(array $users): array
    {
        $active = 0;

        foreach ($users as $user) {
            if ($user->isActive) {
                $active++;
            }
        }

        $total = count($users);

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
        ];
    }

    private function validate(array $data, bool $requirePassword, ?int $exceptId = null): void
    {
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new InvalidArgumentException('El nombre es obligatorio.');
        }

        if (!filter_var((string) ($data['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El email no es valido.');
        }

        if ($this->users->existsEmail((string) $data['email'], $exceptId)) {
            throw new InvalidArgumentException('Ya existe un usuario con ese email.');
        }

        if (!$this->maintainers->isValid(MaintainerService::USER_ROLES, (string) ($data['role'] ?? ''))) {
            throw new InvalidArgumentException('Debes seleccionar un rol valido.');
        }

        if (!$this->maintainers->isValid(MaintainerService::USER_STATUSES, (string) ($data['status'] ?? ''))) {
            throw new InvalidArgumentException('Debes seleccionar un estado valido.');
        }

        $password = (string) ($data['password'] ?? '');

        if ($requirePassword && strlen($password) < 6) {
            throw new InvalidArgumentException('La contrasena debe tener al menos 6 caracteres.');
        }

        if (!$requirePassword && $password !== '' && strlen($password) < 6) {
            throw new InvalidArgumentException('La nueva contrasena debe tener al menos 6 caracteres.');
        }
    }

    private function validateProfessionalData(array $data): void
    {
        $required = [
            'profession' => 'La profesion es obligatoria.',
            'specialty' => 'La especialidad o area de atencion es obligatoria.',
            'phone' => 'El telefono profesional es obligatorio.',
            'clinic_name' => 'El centro o consulta de atencion es obligatorio.',
        ];

        foreach ($required as $field => $message) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new InvalidArgumentException($message);
            }
        }

        $modalities = $this->normalizeModalities($data['attention_modalities'] ?? []);

        if ($modalities === []) {
            throw new InvalidArgumentException('Debes seleccionar al menos una modalidad de atencion.');
        }

        $this->assertModalities($modalities);
    }

    /**
     * @param mixed $modalities
     * @return string[]
     */
    public function normalizeModalities(mixed $modalities): array
    {
        if (!is_array($modalities)) {
            $modalities = [$modalities];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $modalities
        ))));
    }

    /**
     * @param string[] $modalities
     */
    public function assertModalities(array $modalities): void
    {
        foreach ($modalities as $modality) {
            if (!$this->maintainers->isValid(MaintainerService::SESSION_MODALITIES, $modality)) {
                throw new InvalidArgumentException('Una modalidad de atencion seleccionada no es valida.');
            }
        }
    }
}
