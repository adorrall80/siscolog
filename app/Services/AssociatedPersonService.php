<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssociatedPerson;
use App\Repositories\AssociatedPersonRepository;
use InvalidArgumentException;

final class AssociatedPersonService
{
    private AssociatedPersonRepository $people;
    private MaintainerService $maintainers;

    public function __construct()
    {
        $this->people = new AssociatedPersonRepository();
        $this->maintainers = new MaintainerService();
    }

    /**
     * @return AssociatedPerson[]
     */
    public function byPatient(int $patientId, ?array $user = null): array
    {
        return $this->people->byPatient($patientId, $this->userId($user), $this->canSeeAll($user));
    }

    public function findForPatient(int $patientId, int $id, ?array $user = null): ?AssociatedPerson
    {
        return $this->people->findForPatient($patientId, $id, $this->userId($user), $this->canSeeAll($user));
    }

    public function findOrCreate(int $patientId, string $participantType, string $displayName, ?array $user = null): int
    {
        if (!$this->maintainers->isValid(MaintainerService::SESSION_PARTICIPANT_TYPES, $participantType)) {
            throw new InvalidArgumentException('El tipo de participante no es valido.');
        }

        $displayName = trim($displayName);

        if ($displayName === '') {
            throw new InvalidArgumentException('Debes indicar el nombre visible de la persona.');
        }

        return $this->people->create($patientId, $participantType, $displayName, $this->normalizeName($displayName), $this->userId($user));
    }

    public function deactivate(int $patientId, int $personId, ?array $user = null): void
    {
        if ($personId <= 0) {
            throw new InvalidArgumentException('La persona seleccionada no es valida.');
        }

        $updated = $this->people->setActive(
            $patientId,
            $personId,
            false,
            $this->userId($user),
            $this->canSeeAll($user)
        );

        if (!$updated) {
            throw new InvalidArgumentException('No puedes quitar esta persona o ya no se encuentra activa.');
        }
    }

    private function userId(?array $user): ?int
    {
        $id = (int) ($user['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function canSeeAll(?array $user): bool
    {
        return (string) ($user['role'] ?? '') === 'administrador';
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return $name;
    }
}
