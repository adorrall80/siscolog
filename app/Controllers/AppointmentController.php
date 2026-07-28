<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Repositories\ClinicalSessionRepository;
use App\Services\AssociatedPersonService;
use App\Services\ClinicalSessionService;
use App\Services\MaintainerService;
use App\Services\SessionTopicSubtypeService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class AppointmentController
{
    private AppointmentRepository $appointments;
    private ClinicalSessionRepository $sessions;
    private ClinicalSessionService $sessionService;
    private AssociatedPersonService $associatedPeople;
    private MaintainerService $maintainers;
    private SessionTopicSubtypeService $topicSubtypes;

    public function __construct()
    {
        $this->appointments = new AppointmentRepository();
        $this->sessions = new ClinicalSessionRepository();
        $this->sessionService = new ClinicalSessionService();
        $this->associatedPeople = new AssociatedPersonService();
        $this->maintainers = new MaintainerService();
        $this->topicSubtypes = new SessionTopicSubtypeService();
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'estado' => (string) $request->input('estado', 'todos'),
            'periodo' => (string) $request->input('periodo', 'todos'),
            'modality' => (string) $request->input('modality', ''),
            'risk_level' => (string) $request->input('risk_level', ''),
            'date_from' => trim((string) $request->input('date_from', '')),
            'date_to' => trim((string) $request->input('date_to', '')),
            'orden' => (string) $request->input('orden', 'desc'),
            'vista' => (string) $request->input('vista', 'listado'),
            'calendar_date' => trim((string) $request->input('calendar_date', date('Y-m-d'))),
        ];
        $appointments = $this->filterAppointments($this->appointmentsForCurrentUser(), $filters);

        return Response::view('appointments.index', [
            'appointments' => $appointments,
            'appointmentsByDate' => $this->groupByDate($appointments),
            'calendar' => $this->calendarData($appointments, $filters),
            'summary' => $this->appointments->summary($appointments),
            'filters' => $filters,
            'sessionModalities' => $this->maintainers->active(MaintainerService::SESSION_MODALITIES),
            'riskLevels' => $this->maintainers->active(MaintainerService::RISK_LEVELS),
        ]);
    }

    public function show(Request $request): Response
    {
        $appointment = $this->appointments->find((int) $request->param('id'));

        if ($appointment === null) {
            return $this->notFound('Cita no encontrada');
        }

        if (!$this->canAccessAppointment($appointment->id)) {
            Session::flash('error', 'No tienes acceso a esta cita.');
            return Response::redirect('/citas');
        }

        $session = $this->sessions->findForPatient($appointment->patientId, $appointment->id);

        if ($session === null) {
            return $this->notFound('Sesión clínica no encontrada');
        }

        return Response::view('appointments.show', [
            'appointment' => $appointment,
            'session' => $session,
        ]);
    }

    public function edit(Request $request): Response
    {
        $appointment = $this->appointments->find((int) $request->param('id'));

        if ($appointment === null) {
            return $this->notFound('Cita no encontrada');
        }

        if (!$this->canAccessAppointment($appointment->id)) {
            Session::flash('error', 'No tienes acceso a esta cita.');
            return Response::redirect('/citas');
        }

        $session = $this->sessions->findForPatient($appointment->patientId, $appointment->id);

        if ($session === null) {
            return $this->notFound('Sesión clínica no encontrada');
        }

        $topicTypes = $this->maintainers->activeVisible(
            MaintainerService::SESSION_TOPIC_TYPES,
            $this->currentUser()
        );

        return Response::view('appointments.edit', [
            'appointment' => $appointment,
            'session' => $session,
            'currentTopics' => $this->sessions->topicRelationsForSession($appointment->id),
            'currentParticipants' => $this->sessions->participantRelationsForSession($appointment->id),
            'participantTypes' => $this->maintainers->active(MaintainerService::SESSION_PARTICIPANT_TYPES),
            'sessionModalities' => $this->maintainers->active(MaintainerService::SESSION_MODALITIES),
            'riskLevels' => $this->maintainers->active(MaintainerService::RISK_LEVELS),
            'associatedPeople' => $this->associatedPeople->byPatient($appointment->patientId, $this->currentUser()),
            'topicTypes' => $topicTypes,
            'topicSubtypesByType' => $this->topicSubtypes->activeGroupedByType($topicTypes, $this->currentUser()),
            'allTopicSubtypes' => $this->topicSubtypes->allActive(),
        ]);
    }

    public function update(Request $request): Response
    {
        $appointmentId = (int) $request->param('id');
        $appointment = $this->appointments->find($appointmentId);

        if ($appointment === null) {
            return $this->notFound('Cita no encontrada');
        }

        if (!$this->canAccessAppointment($appointmentId)) {
            Session::flash('error', 'No tienes acceso a esta cita.');
            return Response::redirect('/citas');
        }

        try {
            $this->sessionService->update($appointment->patientId, $appointmentId, $this->sessionData($request));
            Session::flash('success', 'Cita actualizada correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/citas/{$appointmentId}/editar");
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/citas');
        }

        return Response::redirect("/citas/{$appointmentId}");
    }

    /**
     * @param Appointment[] $appointments
     * @return array<string, Appointment[]>
     */
    private function groupByDate(array $appointments): array
    {
        $grouped = [];

        foreach ($appointments as $appointment) {
            $date = substr($appointment->sessionDate, 0, 10);
            $grouped[$date] ??= [];
            $grouped[$date][] = $appointment;
        }

        return $grouped;
    }

    /**
     * @param Appointment[] $appointments
     * @return Appointment[]
     */
    private function filterAppointments(array $appointments, array $filters): array
    {
        $today = date('Y-m-d');
        $needle = strtolower(trim((string) ($filters['q'] ?? '')));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        $filtered = array_values(array_filter($appointments, function (Appointment $appointment) use ($filters, $needle, $today, $dateFrom, $dateTo): bool {
            $date = substr($appointment->sessionDate, 0, 10);

            if ($needle !== '') {
                $haystack = strtolower($appointment->patientCode . ' ' . $appointment->patientName . ' ' . (string) $appointment->reason);

                if (!str_contains($haystack, $needle)) {
                    return false;
                }
            }

            if (($filters['estado'] ?? 'todos') === 'vigentes' && !$appointment->isActive) {
                return false;
            }

            if (($filters['estado'] ?? 'todos') === 'no_vigentes' && $appointment->isActive) {
                return false;
            }

            if (($filters['periodo'] ?? 'todos') === 'hoy' && $date !== $today) {
                return false;
            }

            if (($filters['periodo'] ?? 'todos') === 'proximas' && $date < $today) {
                return false;
            }

            if (($filters['periodo'] ?? 'todos') === 'historicas' && $date >= $today) {
                return false;
            }

            if (($filters['modality'] ?? '') !== '' && $appointment->modality !== $filters['modality']) {
                return false;
            }

            if (($filters['risk_level'] ?? '') !== '' && $appointment->riskLevel !== $filters['risk_level']) {
                return false;
            }

            if ($dateFrom !== '' && $date < $dateFrom) {
                return false;
            }

            if ($dateTo !== '' && $date > $dateTo) {
                return false;
            }

            return true;
        }));

        usort($filtered, function (Appointment $left, Appointment $right) use ($filters): int {
            $direction = ($filters['orden'] ?? 'desc') === 'asc' ? 1 : -1;

            if ($left->sessionDate === $right->sessionDate) {
                return $direction * ($left->id <=> $right->id);
            }

            return $direction * strcmp($left->sessionDate, $right->sessionDate);
        });

        return $filtered;
    }

    /**
     * @param Appointment[] $appointments
     * @return array<string, mixed>
     */
    private function calendarData(array $appointments, array $filters): array
    {
        $view = in_array($filters['vista'] ?? 'listado', ['semana', 'mes'], true) ? $filters['vista'] : 'listado';
        $baseDate = $this->validDate((string) ($filters['calendar_date'] ?? '')) ?: date('Y-m-d');
        $byDate = $this->groupByDate($appointments);

        if ($view === 'semana') {
            $start = new \DateTimeImmutable($baseDate);
            $start = $start->modify('monday this week');
            $days = [];

            for ($i = 0; $i < 7; $i++) {
                $date = $start->modify("+{$i} days")->format('Y-m-d');
                $days[] = [
                    'date' => $date,
                    'label' => $this->weekdayLabel($start->modify("+{$i} days")) . ' ' . $start->modify("+{$i} days")->format('d/m'),
                    'appointments' => $byDate[$date] ?? [],
                ];
            }

            return [
                'view' => 'semana',
                'title' => 'Semana del ' . $start->format('d/m/Y'),
                'days' => $days,
            ];
        }

        if ($view === 'mes') {
            $first = (new \DateTimeImmutable($baseDate))->modify('first day of this month');
            $daysInMonth = (int) $first->format('t');
            $offset = ((int) $first->format('N')) - 1;
            $cells = [];

            for ($i = 0; $i < $offset; $i++) {
                $cells[] = ['date' => null, 'label' => '', 'appointments' => []];
            }

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $first->setDate((int) $first->format('Y'), (int) $first->format('m'), $day)->format('Y-m-d');
                $cells[] = [
                    'date' => $date,
                    'label' => (string) $day,
                    'appointments' => $byDate[$date] ?? [],
                ];
            }

            return [
                'view' => 'mes',
                'title' => $this->monthLabel($first) . ' ' . $first->format('Y'),
                'cells' => $cells,
            ];
        }

        return [
            'view' => 'listado',
            'title' => 'Listado',
        ];
    }

    private function validDate(string $date): ?string
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed instanceof \DateTimeImmutable ? $parsed->format('Y-m-d') : null;
    }

    private function weekdayLabel(\DateTimeImmutable $date): string
    {
        return ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'][(int) $date->format('N') - 1] ?? '';
    }

    private function monthLabel(\DateTimeImmutable $date): string
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ][(int) $date->format('n')] ?? '';
    }

    private function sessionData(Request $request): array
    {
        $participants = $request->input('participants', []);
        $participants = is_array($participants) ? $participants : [];

        if (trim((string) ($participants['otro']['new_name'] ?? '')) !== '') {
            $participants['otro']['selected'] = '1';
        }

        return [
            'session_date' => trim((string) $request->input('session_date')),
            'created_by_user_id' => (int) ($this->currentUser()['id'] ?? 0),
            'modality' => (string) $request->input('modality'),
            'reason' => trim((string) $request->input('reason')),
            'subjective_note' => trim((string) $request->input('subjective_note')),
            'objective_note' => trim((string) $request->input('objective_note')),
            'clinical_impression' => trim((string) $request->input('clinical_impression')),
            'risk_level' => (string) $request->input('risk_level'),
            'agreements' => trim((string) $request->input('agreements')),
            'next_steps' => trim((string) $request->input('next_steps')),
            'participants' => $participants,
            'participant_rows' => is_array($request->input('participant_rows', [])) ? $request->input('participant_rows', []) : [],
            'replace_participants' => (string) $request->input('replace_participants', '0'),
            'topics' => is_array($request->input('topics', [])) ? $request->input('topics', []) : [],
            'topic_pairs' => is_array($request->input('topic_pairs', [])) ? $request->input('topic_pairs', []) : [],
            'topic_type_text' => trim((string) $request->input('topic_type_text')),
            'topic_subtype_text' => trim((string) $request->input('topic_subtype_text')),
            'replace_topics' => (string) $request->input('replace_topics', '0'),
        ];
    }

    /**
     * @return Appointment[]
     */
    private function appointmentsForCurrentUser(): array
    {
        $user = $this->currentUser();

        if ((string) ($user['role'] ?? '') === 'administrador') {
            return $this->appointments->all();
        }

        $userId = (int) ($user['id'] ?? 0);

        return $userId > 0 ? $this->appointments->allForProfessional($userId) : [];
    }

    private function canAccessAppointment(int $appointmentId): bool
    {
        $user = $this->currentUser();

        if ((string) ($user['role'] ?? '') === 'administrador') {
            return true;
        }

        $userId = (int) ($user['id'] ?? 0);

        return $userId > 0 && $this->appointments->professionalCanAccess($appointmentId, $userId);
    }

    private function notFound(string $title): Response
    {
        return Response::view('errors.not_found', [
            'errorTitle' => $title,
            'errorMessage' => 'Es posible que el registro haya sido eliminado o que la dirección corresponda a una sesión que ya no está disponible.',
            'backUrl' => '/citas',
            'backLabel' => 'Volver a Sesiones',
        ], 404);
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }
}
