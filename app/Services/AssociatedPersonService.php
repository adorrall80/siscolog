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

    /**
     * @return AssociatedPerson[]
     */
    public function archivedByPatient(int $patientId, ?array $user = null): array
    {
        return $this->people->archivedByPatient($patientId, $this->userId($user), $this->canSeeAll($user));
    }

    public function findForPatient(int $patientId, int $id, ?array $user = null): ?AssociatedPerson
    {
        return $this->people->findForPatient($patientId, $id, $this->userId($user), $this->canSeeAll($user));
    }

    public function findOrCreate(int $patientId, string $participantType, string $displayName, ?array $user = null, bool $createAsNew = false): int
    {
        if (!$this->maintainers->isValid(MaintainerService::SESSION_PARTICIPANT_TYPES, $participantType)) {
            throw new InvalidArgumentException('El tipo de participante no es valido.');
        }

        $displayName = trim($displayName);

        if ($displayName === '') {
            throw new InvalidArgumentException('Debes indicar el nombre visible de la persona.');
        }

        return $this->people->create($patientId, $participantType, $displayName, $this->normalizeName($displayName), $this->userId($user), $createAsNew);
    }

    public function createForMap(int $patientId, string $participantType, string $displayName, ?array $user = null, bool $createAsNew = false): int
    {
        if (!$this->maintainers->isValid(MaintainerService::SESSION_PARTICIPANT_TYPES, $participantType)) {
            throw new InvalidArgumentException('El tipo de participante no es válido.');
        }

        $displayName = trim($displayName);
        if ($displayName === '') {
            throw new InvalidArgumentException('Debes indicar el nombre visible de la persona.');
        }

        $normalizedName = $this->normalizeName($displayName);
        $existing = $this->people->findByNormalized($patientId, $participantType, $normalizedName, $this->userId($user));

        if ($existing !== null && !$createAsNew) {
            if ($existing->isActive) {
                throw new InvalidArgumentException(
                    'Esta persona ya está visible en el mapa como ' . $existing->displayName . ' #' . $existing->id
                    . '. Si realmente es otra persona, marca “Es una persona diferente”.'
                );
            }

            throw new InvalidArgumentException(
                'Esta persona existe archivada como ' . $existing->displayName . ' #' . $existing->id
                . '. Puedes restaurarla o marcar “Es una persona diferente”.'
            );
        }

        return $this->people->create(
            $patientId,
            $participantType,
            $displayName,
            $normalizedName,
            $this->userId($user),
            $createAsNew
        );
    }

    public function restore(int $patientId, int $personId, ?array $user = null): void
    {
        if ($personId <= 0) {
            throw new InvalidArgumentException('La persona archivada no es válida.');
        }

        $updated = $this->people->setActive($patientId, $personId, true, $this->userId($user), $this->canSeeAll($user));

        if (!$updated) {
            throw new InvalidArgumentException('No fue posible restaurar la persona seleccionada.');
        }
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
