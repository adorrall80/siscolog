<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClinicalSession;
use App\Repositories\ClinicalSessionRepository;
use InvalidArgumentException;
use RuntimeException;

final class ClinicalSessionService
{
    private ClinicalSessionRepository $sessions;
    private AssociatedPersonService $associatedPeople;
    private MaintainerService $maintainers;
    private SessionTopicSubtypeService $topicSubtypes;

    public function __construct()
    {
        $this->sessions = new ClinicalSessionRepository();
        $this->associatedPeople = new AssociatedPersonService();
        $this->maintainers = new MaintainerService();
        $this->topicSubtypes = new SessionTopicSubtypeService();
    }

    /**
     * @return ClinicalSession[]
     */
    public function byPatient(int $patientId): array
    {
        return $this->sessions->byPatient($patientId);
    }

    /**
     * @param array{id?: mixed, role?: mixed}|null $user
     * @return ClinicalSession[]
     */
    public function byPatientForUser(int $patientId, ?array $user): array
    {
        if ((string) ($user['role'] ?? '') === 'administrador') {
            return $this->byPatient($patientId);
        }

        $userId = (int) ($user['id'] ?? 0);

        return $userId > 0 ? $this->sessions->byPatientForProfessional($patientId, $userId) : [];
    }

    public function create(int $patientId, array $data): void
    {
        $this->validate($data);
        $data['participants'] = $this->normalizeParticipants($patientId, $data['participants'] ?? [], $this->userFromData($data));
        $data['topics'] = array_merge(
            $this->normalizeTopics($data['topics'] ?? []),
            $this->normalizeTopicPairs($data['topic_pairs'] ?? []),
            $this->normalizeFreeTopic($data)
        );
        $data['topics'] = $this->uniqueTopics($data['topics']);
        $this->sessions->create($patientId, $data);
    }

    public function update(int $patientId, int $sessionId, array $data): void
    {
        $session = $this->sessions->findForPatient($patientId, $sessionId);

        if ($session === null) {
            throw new RuntimeException('Sesion clinica no encontrada para este paciente.');
        }

        $this->validateCore($data);
        $data['participants'] = $this->uniqueParticipants(array_merge(
            $this->normalizeParticipantRows($patientId, $data['participant_rows'] ?? [], $this->userFromData($data)),
            $this->normalizeOptionalParticipants($patientId, $data['participants'] ?? [], $this->userFromData($data))
        ));
        $data['topics'] = array_merge(
            $this->normalizeTopics($data['topics'] ?? []),
            $this->normalizeTopicPairs($data['topic_pairs'] ?? []),
            $this->normalizeFreeTopic($data)
        );
        $data['topics'] = $this->uniqueTopics($data['topics']);
        $data['replace_participants'] = (string) ($data['replace_participants'] ?? '') === '1';
        $data['replace_topics'] = (string) ($data['replace_topics'] ?? '') === '1';

        $this->sessions->update($patientId, $sessionId, $data);
    }

    public function setActive(int $patientId, int $sessionId, bool $active): void
    {
        $session = $this->sessions->findForPatient($patientId, $sessionId);

        if ($session === null) {
            throw new RuntimeException('Sesion clinica no encontrada para este paciente.');
        }

        $this->sessions->setActive($sessionId, $active);
    }

    private function validate(array $data): void
    {
        $this->validateCore($data);

        $hasTopicPairs = is_array($data['topic_pairs'] ?? null) && array_filter(
            $data['topic_pairs'],
            fn (mixed $pair): bool => is_array($pair)
                && trim((string) ($pair['type'] ?? '')) !== ''
                && trim((string) ($pair['subtype'] ?? '')) !== ''
        ) !== [];

        if (!$hasTopicPairs && trim((string) ($data['topic_type_text'] ?? '')) === '') {
            throw new InvalidArgumentException('Debes elegir o escribir un tipo de tema para la sesion.');
        }

        if (!$hasTopicPairs && trim((string) ($data['topic_subtype_text'] ?? '')) === '') {
            throw new InvalidArgumentException('Debes elegir o escribir un subtipo de tema para la sesion.');
        }

        foreach (($data['participants'] ?? []) as $participantType => $participant) {
            if (!is_array($participant) || !isset($participant['selected'])) {
                continue;
            }

            if (!$this->maintainers->isValid(MaintainerService::SESSION_PARTICIPANT_TYPES, (string) $participantType)) {
                throw new InvalidArgumentException('Uno de los participantes de la sesion no es valido.');
            }
        }
    }

    private function validateCore(array $data): void
    {
        if (trim((string) ($data['session_date'] ?? '')) === '') {
            throw new InvalidArgumentException('La fecha de sesion es obligatoria.');
        }

        if (!$this->maintainers->isValid(MaintainerService::SESSION_MODALITIES, (string) ($data['modality'] ?? ''))) {
            throw new InvalidArgumentException('La modalidad de sesion no es valida.');
        }

        if (!$this->maintainers->isValid(MaintainerService::RISK_LEVELS, (string) ($data['risk_level'] ?? ''))) {
            throw new InvalidArgumentException('El nivel de riesgo no es valido.');
        }
    }

    /**
     * @return array<int, array{type: string, person_id: ?int, text: string, origin: string}>
     */
    private function normalizeParticipants(int $patientId, array $participants, ?array $user = null): array
    {
        $normalized = [];

        if ($participants === []) {
            $participants = ['paciente' => ['selected' => '1']];
        }

        foreach ($participants as $participantType => $data) {
            if (!is_array($data) || !isset($data['selected'])) {
                continue;
            }

            $participantType = (string) $participantType;
            $personId = null;
            $text = $participantType;
            $origin = 'L';
            $selectedPersonId = (int) ($data['person_id'] ?? 0);
            $newName = trim((string) ($data['new_name'] ?? ''));

            if ($selectedPersonId > 0) {
                $person = $this->associatedPeople->findForPatient($patientId, $selectedPersonId, $user);

                if ($person !== null && $person->participantType === $participantType) {
                    $personId = (int) $person->id;
                    $text = $person->displayName;
                    $origin = 'O';
                }
            } elseif ($newName !== '') {
                $personId = $this->associatedPeople->findOrCreate($patientId, $participantType, $newName, $user);
                $text = $newName;
                $origin = 'O';
            }

            $normalized[] = [
                'type' => $participantType,
                'person_id' => $personId,
                'text' => $text,
                'origin' => $origin,
            ];
        }

        if ($normalized === []) {
            $normalized[] = [
                'type' => 'paciente',
                'person_id' => null,
                'text' => 'paciente',
                'origin' => 'L',
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array{type: string, person_id: ?int, text: string, origin: string}>
     */
    private function normalizeOptionalParticipants(int $patientId, array $participants, ?array $user = null): array
    {
        $hasSelected = false;

        foreach ($participants as $participant) {
            if (is_array($participant) && isset($participant['selected'])) {
                $hasSelected = true;
                break;
            }
        }

        return $hasSelected ? $this->normalizeParticipants($patientId, $participants, $user) : [];
    }

    /**
     * @return array<int, array{type: string, person_id: ?int, text: string, origin: string}>
     */
    private function normalizeParticipantRows(int $patientId, array $rows, ?array $user = null): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = (string) ($row['type'] ?? 'otro');
            $text = trim((string) ($row['text'] ?? ''));
            $origin = (string) ($row['origin'] ?? 'L');
            $personId = (int) ($row['person_id'] ?? 0);

            if ($text === '') {
                continue;
            }

            if (!$this->maintainers->isValid(MaintainerService::SESSION_PARTICIPANT_TYPES, $type)) {
                $type = 'otro';
            }

            if ($type === 'paciente') {
                $normalized[] = [
                    'type' => 'paciente',
                    'person_id' => null,
                    'text' => 'Paciente',
                    'origin' => 'L',
                ];
                continue;
            }

            if ($origin === 'O' && $personId <= 0) {
                $personId = $this->associatedPeople->findOrCreate($patientId, $type, $text, $user);
            }

            $normalized[] = [
                'type' => $type,
                'person_id' => $personId > 0 ? $personId : null,
                'text' => $text,
                'origin' => in_array($origin, ['L', 'O'], true) ? $origin : 'L',
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array{type_id: int, subtype_id: int}>
     */
    private function normalizeTopics(array $topics): array
    {
        $normalized = [];

        foreach ($topics as $typeId => $subtypeIds) {
            if (!is_array($subtypeIds)) {
                continue;
            }

            foreach ($subtypeIds as $subtypeId => $selected) {
                if ((string) $selected !== '1') {
                    continue;
                }

                $typeId = (int) $typeId;
                $subtypeId = (int) $subtypeId;

                if ($typeId <= 0 || $subtypeId <= 0) {
                    continue;
                }

                $normalized[] = [
                    'type_id' => $typeId,
                    'subtype_id' => $subtypeId,
                ];
            }
        }

        return $normalized;
    }

    /**
     * @return array<int, array{type_id: int, subtype_id: int}>
     */
    private function normalizeTopicPairs(array $pairs): array
    {
        $normalized = [];

        foreach ($pairs as $pair) {
            if (!is_array($pair)) {
                continue;
            }

            $normalized = array_merge($normalized, $this->normalizeFreeTopic([
                'topic_type_text' => $pair['type'] ?? '',
                'topic_subtype_text' => $pair['subtype'] ?? '',
            ]));
        }

        return $normalized;
    }

    /**
     * @return array<int, array{type_id: int, subtype_id: int}>
     */
    private function normalizeFreeTopic(array $data): array
    {
        $typeName = trim((string) ($data['topic_type_text'] ?? ''));
        $subtypeName = trim((string) ($data['topic_subtype_text'] ?? ''));

        if ($typeName === '' && $subtypeName === '') {
            return [];
        }

        if ($typeName === '') {
            throw new InvalidArgumentException('Debes indicar el tipo de tema para agregar un subtipo.');
        }

        if ($subtypeName === '') {
            return [];
        }

        $topicType = $this->maintainers->findOrCreateSessionTopicType($typeName);
        $subtypeId = $this->topicSubtypes->createAndRelate((int) $topicType->id, [
            'code' => '',
            'name' => $subtypeName,
            'description' => '',
            'color' => '',
            'sort_order' => 0,
        ], [
            'id' => (int) ($data['created_by_user_id'] ?? 0),
        ]);

        return [[
            'type_id' => (int) $topicType->id,
            'subtype_id' => $subtypeId,
        ]];
    }

    /**
     * @param array<int, array{type_id: int, subtype_id: int}> $topics
     * @return array<int, array{type_id: int, subtype_id: int}>
     */
    private function uniqueTopics(array $topics): array
    {
        $unique = [];
        $seen = [];

        foreach ($topics as $topic) {
            $key = ((int) $topic['type_id']) . ':' . ((int) $topic['subtype_id']);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $topic;
        }

        return $unique;
    }

    /**
     * @param array<int, array{type: string, person_id: ?int, text: string, origin: string}> $participants
     * @return array<int, array{type: string, person_id: ?int, text: string, origin: string}>
     */
    private function uniqueParticipants(array $participants): array
    {
        $unique = [];
        $seen = [];

        foreach ($participants as $participant) {
            $key = strtolower(trim($participant['text']));

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $participant;
        }

        return $unique;
    }

    private function userFromData(array $data): ?array
    {
        $userId = (int) ($data['created_by_user_id'] ?? $data['professional_id'] ?? 0);

        return $userId > 0 ? ['id' => $userId] : null;
    }
}
