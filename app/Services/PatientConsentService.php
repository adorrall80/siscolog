<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PatientConsent;
use App\Repositories\PatientConsentRepository;
use InvalidArgumentException;
use RuntimeException;

final class PatientConsentService
{
    private PatientConsentRepository $consents;
    private MaintainerService $maintainers;

    public function __construct()
    {
        $this->consents = new PatientConsentRepository();
        $this->maintainers = new MaintainerService();
    }

    /**
     * @return PatientConsent[]
     */
    public function byPatient(int $patientId, ?array $user = null): array
    {
        return $this->consents->byPatient(
            $patientId,
            $this->userId($user),
            $this->isGlobalRole($user)
        );
    }

    public function create(int $patientId, array $data, ?array $user = null): void
    {
        $this->validate($data);
        $data['created_by_user_id'] = $this->userId($user);
        $this->consents->create($patientId, $data);
    }

    public function hasActiveAccepted(int $patientId, string $consentType, ?array $user = null): bool
    {
        foreach ($this->byPatient($patientId, $user) as $consent) {
            if (
                $consent->consentType === $consentType
                && $consent->accepted
                && $consent->isActive
            ) {
                return true;
            }
        }

        return false;
    }

    public function setActive(int $patientId, int $consentId, bool $active, ?array $user = null): void
    {
        $consent = $this->consents->findForPatient(
            $patientId,
            $consentId,
            $this->userId($user),
            $this->isGlobalRole($user)
        );

        if ($consent === null) {
            throw new RuntimeException('Consentimiento no encontrado para este paciente.');
        }

        $this->consents->setActive($consentId, $active);
    }

    /**
     * @param PatientConsent[] $consents
     * @return array{total: int, accepted: int, pending: int, active: int}
     */
    public function summary(array $consents): array
    {
        $accepted = 0;
        $active = 0;

        foreach ($consents as $consent) {
            if ($consent->accepted) {
                $accepted++;
            }

            if ($consent->isActive) {
                $active++;
            }
        }

        $total = count($consents);

        return [
            'total' => $total,
            'accepted' => $accepted,
            'pending' => $total - $accepted,
            'active' => $active,
        ];
    }

    private function validate(array $data): void
    {
        if (!$this->maintainers->isValid(MaintainerService::CONSENT_TYPES, (string) ($data['consent_type'] ?? ''))) {
            throw new InvalidArgumentException('El tipo de consentimiento no es valido.');
        }

        if (trim((string) ($data['document_version'] ?? '')) === '') {
            throw new InvalidArgumentException('La version del documento es obligatoria.');
        }
    }

    private function userId(?array $user): ?int
    {
        $userId = (int) ($user['id'] ?? 0);

        return $userId > 0 ? $userId : null;
    }

    private function isGlobalRole(?array $user): bool
    {
        return (string) ($user['role'] ?? '') === 'administrador';
    }
}
