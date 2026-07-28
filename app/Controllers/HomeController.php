<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use PDO;

final class HomeController
{
    public function index(Request $request): Response
    {
        return Response::view('home.menu');
    }

    public function panel(Request $request): Response
    {
        $db = Database::connection();
        $user = $this->currentUser();
        $scope = $this->scope($user);

        return Response::view('home.panel', [
            'metrics' => $this->metrics($db, $scope),
            'recentSessions' => $this->recentSessions($db, $scope),
            'pendingConsents' => $this->pendingConsents($db, $scope),
            'pendingAiReviews' => $this->pendingAiReviews($db, $scope),
            'riskAlerts' => $this->riskAlerts($db, $scope),
            'instrumentAlerts' => $this->instrumentAlerts($db, $scope),
        ]);
    }

    private function metrics(PDO $db, array $scope): array
    {
        return [
            'patients' => $this->countScoped($db, 'patients', 'p', $scope),
            'activePatients' => $this->countScoped($db, 'patients', 'p', $scope, 'p.is_active = 1'),
            'sessions' => $this->countScoped($db, 'clinical_sessions', 'cs', $scope),
            'activeSessions' => $this->countScoped($db, 'clinical_sessions', 'cs', $scope, 'cs.is_active = 1'),
            'pendingConsents' => count($this->pendingConsents($db, $scope, 50)),
            'pendingAiOutputs' => count($this->pendingAiReviews($db, $scope, 50)),
            'riskAlerts' => count($this->riskAlerts($db, $scope, 50)),
            'instrumentAlerts' => count($this->instrumentAlerts($db, $scope, 50)),
        ];
    }

    private function countScoped(PDO $db, string $table, string $alias, array $scope, ?string $extraWhere = null): int
    {
        $params = [];
        $joins = '';
        $where = [];

        if ($table === 'patients') {
            $joins = '';
            $where = $this->patientWhere($alias, $scope, $params);
        } elseif ($table === 'clinical_sessions') {
            $joins = ' INNER JOIN patients p ON p.id = cs.patient_id';
            $where = $this->patientWhere('p', $scope, $params);
        }

        if ($extraWhere !== null) {
            $where[] = $extraWhere;
        }

        $sql = "SELECT COUNT(*) FROM {$table} {$alias}{$joins}";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $statement = $db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function recentSessions(PDO $db, array $scope, int $limit = 6): array
    {
        $params = [];
        $where = $this->patientWhere('p', $scope, $params);
        $where[] = 'cs.is_active = 1';

        $sql = 'SELECT cs.id,
                       cs.patient_id,
                       cs.session_date,
                       cs.risk_level,
                       cs.reason,
                       p.full_name AS patient_name
                FROM clinical_sessions cs
                INNER JOIN patients p ON p.id = cs.patient_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY cs.session_date DESC, cs.id DESC
                LIMIT ' . max(1, min(20, $limit));
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private function pendingConsents(PDO $db, array $scope, int $limit = 6): array
    {
        $params = [];
        $where = $this->patientWhere('p', $scope, $params);
        $where[] = 'p.is_active = 1';
        $where[] = "NOT EXISTS (
            SELECT 1
            FROM patient_consents pc
            WHERE pc.patient_id = p.id
              AND pc.consent_type = 'analisis_ia'
              AND pc.accepted = 1
              AND pc.is_active = 1
        )";

        $sql = 'SELECT p.id, p.code, p.full_name
                FROM patients p
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.created_at DESC
                LIMIT ' . max(1, min(50, $limit));
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private function pendingAiReviews(PDO $db, array $scope, int $limit = 6): array
    {
        $params = [];
        $where = $this->patientWhere('p', $scope, $params);
        $where[] = "a.review_status = 'pendiente'";
        $where[] = 'a.is_active = 1';

        $sql = 'SELECT a.id,
                       a.patient_id,
                       a.created_at,
                       p.full_name AS patient_name
                FROM ai_analysis_outputs a
                INNER JOIN patients p ON p.id = a.patient_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT ' . max(1, min(50, $limit));
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private function riskAlerts(PDO $db, array $scope, int $limit = 6): array
    {
        $params = [];
        $where = $this->patientWhere('p', $scope, $params);
        $where[] = 'cs.is_active = 1';
        $where[] = "cs.risk_level IN ('alto', 'critico')";

        $sql = 'SELECT cs.id,
                       cs.patient_id,
                       cs.session_date,
                       cs.risk_level,
                       p.full_name AS patient_name
                FROM clinical_sessions cs
                INNER JOIN patients p ON p.id = cs.patient_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY cs.session_date DESC, cs.id DESC
                LIMIT ' . max(1, min(50, $limit));
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private function instrumentAlerts(PDO $db, array $scope, int $limit = 6): array
    {
        $params = [];
        $where = $this->patientWhere('p', $scope, $params);
        $where[] = 'pr.is_active = 1';
        $where[] = 'pi.caution_cutoff IS NOT NULL';
        $where[] = 'pr.score >= pi.caution_cutoff';

        $sql = 'SELECT pr.id,
                       pr.patient_id,
                       pr.applied_at,
                       pr.score,
                       pi.name AS instrument_name,
                       pi.max_score,
                       pi.critical_cutoff,
                       p.full_name AS patient_name
                FROM psychometric_results pr
                INNER JOIN psychometric_instruments pi ON pi.id = pr.instrument_id
                INNER JOIN patients p ON p.id = pr.patient_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY pr.applied_at DESC, pr.id DESC
                LIMIT ' . max(1, min(50, $limit));
        $statement = $db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private function patientWhere(string $alias, array $scope, array &$params): array
    {
        if ($scope['all']) {
            return [];
        }

        $params['current_user_id'] = $scope['user_id'];

        return [
            "({$alias}.assigned_professional_id = :current_user_id
              OR EXISTS (
                    SELECT 1
                    FROM clinical_sessions scope_cs
                    WHERE scope_cs.patient_id = {$alias}.id
                      AND scope_cs.professional_id = :current_user_id
              ))",
        ];
    }

    private function scope(array $user): array
    {
        return [
            'all' => (string) ($user['role'] ?? '') === 'administrador',
            'user_id' => (int) ($user['id'] ?? 0),
        ];
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }
}
