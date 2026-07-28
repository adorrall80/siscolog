<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;
use App\Repositories\PatientRepository;
use InvalidArgumentException;
use RuntimeException;

final class PatientService
{
    private PatientRepository $patients;
    private MaintainerService $maintainers;

    public function __construct()
    {
        $this->patients = new PatientRepository();
        $this->maintainers = new MaintainerService();
    }

    /**
     * @return Patient[]
     */
    public function list(): array
    {
        return $this->patients->all();
    }

    /**
     * @param array{id?: mixed, role?: mixed}|null $user
     * @return Patient[]
     */
    public function listForUser(?array $user): array
    {
        if ($this->isGlobalRole($user)) {
            return $this->patients->all();
        }

        $userId = (int) ($user['id'] ?? 0);

        if ($userId <= 0) {
            return [];
        }

        return $this->patients->allForProfessional($userId);
    }

    /**
     * @return Patient[]
     */
    public function filtered(string $filter, string $search = ''): array
    {
        $patients = $this->list();

        return $this->filterPatients($patients, $filter, $search);
    }

    /**
     * @param array{id?: mixed, role?: mixed}|null $user
     * @return Patient[]
     */
    public function filteredForUser(?array $user, string $filter, string $search = ''): array
    {
        return $this->filterPatients($this->listForUser($user), $filter, $search);
    }

    /**
     * @param Patient[] $patients
     * @return Patient[]
     */
    private function filterPatients(array $patients, string $filter, string $search = ''): array
    {

        $filtered = match ($filter) {
            'active' => array_values(array_filter($patients, fn (Patient $patient): bool => $patient->isActive)),
            'inactive' => array_values(array_filter($patients, fn (Patient $patient): bool => !$patient->isActive)),
            default => $patients,
        };

        $needle = $this->normalizeSearch($search);

        if ($needle === '') {
            return $filtered;
        }

        return array_values(array_filter($filtered, function (Patient $patient) use ($needle): bool {
            $haystack = strtolower($patient->code . ' ' . $patient->fullName . ' ' . (string) $patient->email . ' ' . (string) $patient->phone . ' ' . $patient->status);

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
        $this->validate($data);

        $this->patients->create($data);
    }

    public function find(int $id): Patient
    {
        $patient = $this->patients->find($id);

        if ($patient === null) {
            throw new RuntimeException('Paciente no encontrado.');
        }

        return $patient;
    }

    /**
     * @param array{id?: mixed, role?: mixed}|null $user
     */
    public function findForUser(int $id, ?array $user): Patient
    {
        $patient = $this->find($id);

        if (!$this->canAccess($id, $user)) {
            throw new RuntimeException('No tienes acceso a esta ficha de paciente.');
        }

        return $patient;
    }

    /**
     * @param array{id?: mixed, role?: mixed}|null $user
     */
    public function canAccess(int $patientId, ?array $user): bool
    {
        if ($this->isGlobalRole($user)) {
            return true;
        }

        $userId = (int) ($user['id'] ?? 0);

        return $userId > 0 && $this->patients->professionalCanAccess($patientId, $userId);
    }

    public function update(int $id, array $data): void
    {
        $this->validate($data, true);
        $this->patients->update($id, $data);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->find($id);
        $this->patients->setActive($id, $active);
    }

    /**
     * @param Patient[] $patients
     * @return array{total: int, active: int, inactive: int}
     */
    public function summary(array $patients): array
    {
        $active = 0;

        foreach ($patients as $patient) {
            if ($patient->isActive) {
                $active++;
            }
        }

        $total = count($patients);

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
        ];
    }

    private function validate(array $data, bool $updating = false): void
    {
        if (trim((string) ($data['code'] ?? '')) === '') {
            throw new InvalidArgumentException('El codigo del paciente es obligatorio.');
        }

        if (trim((string) ($data['full_name'] ?? '')) === '') {
            throw new InvalidArgumentException('El nombre del paciente es obligatorio.');
        }

        if (($data['email'] ?? '') !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El email del paciente no tiene un formato valido.');
        }

        if (($data['emergency_email'] ?? '') !== '' && !filter_var($data['emergency_email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El email del contacto de emergencia no tiene un formato valido.');
        }

        if ($updating && !$this->maintainers->isValid(MaintainerService::PATIENT_STATUSES, (string) ($data['status'] ?? ''))) {
            throw new InvalidArgumentException('El estado del paciente no es valido.');
        }
    }

    /**
     * @param array{id?: mixed, role?: mixed}|null $user
     */
    private function isGlobalRole(?array $user): bool
    {
        return (string) ($user['role'] ?? '') === 'administrador';
    }
}
