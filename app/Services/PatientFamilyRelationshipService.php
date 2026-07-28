<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssociatedPerson;
use App\Models\Patient;
use App\Repositories\PatientFamilyNodePositionRepository;
use App\Repositories\PatientFamilyRelationshipRepository;
use InvalidArgumentException;

final class PatientFamilyRelationshipService
{
    private PatientFamilyRelationshipRepository $relationships;
    private PatientFamilyNodePositionRepository $nodePositions;

    public function __construct()
    {
        $this->relationships = new PatientFamilyRelationshipRepository();
        $this->nodePositions = new PatientFamilyNodePositionRepository();
    }

    /**
     * @param AssociatedPerson[] $associatedPeople
     * @return array{nodes: array<int, array{key: string, label: string, role: string, kind: string}>, relationships: array<int, array{id: int, from: string, from_label: string, label: string, to: string, to_label: string}>}
     */
    public function graph(Patient $patient, array $associatedPeople, ?array $user = null): array
    {
        $includeAll = $this->isGlobalRole($user);
        $userId = (int) ($user['id'] ?? 0);
        $nodes = $this->nodes($patient, $associatedPeople, $userId > 0 ? $userId : null, $includeAll);
        $relationships = array_map(
            fn ($relationship): array => [
                'id' => (int) $relationship->id,
                'from' => $relationship->fromNodeKey,
                'from_label' => $relationship->fromNodeLabel,
                'label' => $relationship->relationshipLabel,
                'to' => $relationship->toNodeKey,
                'to_label' => $relationship->toNodeLabel,
            ],
            $this->relationships->byPatient((int) $patient->id, $userId > 0 ? $userId : null, $includeAll)
        );

        return [
            'nodes' => array_values($nodes),
            'relationships' => $relationships,
        ];
    }

    /**
     * @param AssociatedPerson[] $associatedPeople
     */
    public function create(Patient $patient, array $associatedPeople, array $data): void
    {
        $nodes = $this->nodes($patient, $associatedPeople);
        $fromKey = trim((string) ($data['from_node_key'] ?? ''));
        $toKey = trim((string) ($data['to_node_key'] ?? ''));
        $relationshipLabel = trim((string) ($data['relationship_label'] ?? ''));

        if ($fromKey === '' || $toKey === '') {
            throw new InvalidArgumentException('Debes seleccionar dos participantes para relacionarlos.');
        }

        if ($fromKey === $toKey) {
            throw new InvalidArgumentException('No puedes relacionar un participante consigo mismo.');
        }

        if ($relationshipLabel === '') {
            throw new InvalidArgumentException('Debes indicar que relacion tienen.');
        }

        if (!isset($nodes[$fromKey]) || !isset($nodes[$toKey])) {
            throw new InvalidArgumentException('Uno de los participantes no pertenece a la ficha del paciente.');
        }

        $payload = [
            'created_by_user_id' => (int) ($data['created_by_user_id'] ?? 0) ?: null,
            'from_node_key' => $fromKey,
            'from_node_label' => $nodes[$fromKey]['label'],
            'relationship_label' => $relationshipLabel,
            'to_node_key' => $toKey,
            'to_node_label' => $nodes[$toKey]['label'],
        ];
        $existing = $this->relationships->activeBetween((int) $patient->id, $fromKey, $toKey, $payload['created_by_user_id']);

        if ($existing !== null) {
            $replaceConfirmed = (string) ($data['replace_existing_relationship'] ?? '') === '1';
            $replaceId = (int) ($data['replace_relationship_id'] ?? 0);

            if (!$replaceConfirmed || $replaceId !== (int) $existing->id) {
                throw new InvalidArgumentException(
                    'Ya existe esta relacion: '
                    . $existing->fromNodeLabel . ' '
                    . $existing->relationshipLabel . ' '
                    . $existing->toNodeLabel
                    . '. Confirma si deseas reemplazarla.'
                );
            }

            $this->relationships->update((int) $patient->id, (int) $existing->id, $payload);
            return;
        }

        $this->relationships->create((int) $patient->id, $payload);
    }

    /**
     * @param AssociatedPerson[] $associatedPeople
     */
    public function saveNodePosition(Patient $patient, array $associatedPeople, array $data): void
    {
        $nodes = $this->nodes($patient, $associatedPeople);
        $nodeKey = trim((string) ($data['node_key'] ?? ''));

        if ($nodeKey === '' || !isset($nodes[$nodeKey])) {
            throw new InvalidArgumentException('El nodo seleccionado no pertenece a la ficha del paciente.');
        }

        $this->nodePositions->save(
            (int) $patient->id,
            $nodeKey,
            (float) ($data['x'] ?? 50),
            (float) ($data['y'] ?? 50),
            (int) ($data['created_by_user_id'] ?? 0) ?: null
        );
    }

    public function deactivate(int $patientId, int $relationshipId, ?array $user = null): void
    {
        $includeAll = $this->isGlobalRole($user);
        $userId = (int) ($user['id'] ?? 0);

        $this->relationships->setActive($patientId, $relationshipId, false, $userId > 0 ? $userId : null, $includeAll);
    }

    /**
     * @param AssociatedPerson[] $associatedPeople
     * @return array<string, array{key: string, label: string, role: string, kind: string}>
     */
    private function nodes(Patient $patient, array $associatedPeople, ?int $createdByUserId = null, bool $includeAll = false): array
    {
        $nodes = [
            'patient:' . $patient->id => [
                'key' => 'patient:' . $patient->id,
                'label' => $patient->fullName,
                'role' => 'Paciente',
                'kind' => 'patient',
                'participation_status' => 'SP',
            ],
        ];

        foreach ($associatedPeople as $person) {
            $key = 'person:' . $person->id;
            $nodes[$key] = [
                'key' => $key,
                'label' => $person->displayName,
                'role' => $this->labelFromCode($person->participantType),
                'kind' => 'participant',
                'participation_status' => $person->hasParticipated ? 'SP' : 'NP',
            ];
        }

        $positions = $this->nodePositions->byPatient((int) $patient->id, $createdByUserId, $includeAll);
        $defaults = $this->defaultPositions(array_keys($nodes));

        foreach ($nodes as $key => $node) {
            $position = $positions[$key] ?? $defaults[$key] ?? ['x' => 50.0, 'y' => 50.0];
            $nodes[$key]['x'] = $position['x'];
            $nodes[$key]['y'] = $position['y'];
        }

        return $nodes;
    }

    /**
     * @param string[] $keys
     * @return array<string, array{x: float, y: float}>
     */
    private function defaultPositions(array $keys): array
    {
        $positions = [];
        $count = count($keys);

        foreach ($keys as $index => $key) {
            if ($index === 0) {
                $positions[$key] = ['x' => 50.0, 'y' => 18.0];
                continue;
            }

            $angle = -160 + (($index - 1) * (320 / max(1, $count - 2)));
            $radiusX = 34;
            $radiusY = 30;
            $positions[$key] = [
                'x' => 50 + cos(deg2rad($angle)) * $radiusX,
                'y' => 58 + sin(deg2rad($angle)) * $radiusY,
            ];
        }

        return $positions;
    }

    private function labelFromCode(string $code): string
    {
        $label = str_replace(['_', '-'], ' ', $code);
        $label = trim($label);

        return $label === '' ? 'Participante' : ucfirst($label);
    }

    private function isGlobalRole(?array $user): bool
    {
        return (string) ($user['role'] ?? '') === 'administrador';
    }
}
