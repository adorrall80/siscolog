<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuditLog;
use Core\Database;
use PDO;

final class AuditLogRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO audit_logs (
                user_id,
                action,
                entity_type,
                entity_id,
                ip_address,
                user_agent,
                metadata,
                is_active,
                created_at
             )
             VALUES (
                :user_id,
                :action,
                :entity_type,
                :entity_id,
                :ip_address,
                :user_agent,
                :metadata,
                1,
                NOW()
             )'
        );

        $statement->execute([
            'user_id' => $data['user_id'] ?: null,
            'action' => $data['action'],
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'] ?: null,
            'ip_address' => $data['ip_address'] ?: null,
            'user_agent' => $data['user_agent'] ?: null,
            'metadata' => json_encode($data['metadata'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @return AuditLog[]
     */
    public function latest(array $filters = [], int $limit = 120): array
    {
        $where = ['al.is_active = 1'];
        $params = [];

        if (($filters['action'] ?? '') !== '') {
            $where[] = 'al.action = :action';
            $params['action'] = $filters['action'];
        }

        if (($filters['entity_type'] ?? '') !== '') {
            $where[] = 'al.entity_type = :entity_type';
            $params['entity_type'] = $filters['entity_type'];
        }

        if ((int) ($filters['user_id'] ?? 0) > 0) {
            $where[] = 'al.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 'al.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 'al.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(u.name LIKE :q OR u.email LIKE :q OR al.action LIKE :q OR al.entity_type LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql = 'SELECT al.*, u.name AS user_name
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY al.created_at DESC, al.id DESC
                LIMIT ' . max(1, min(300, $limit));
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return array_map(
            fn (array $row): AuditLog => $this->map($row),
            $statement->fetchAll()
        );
    }

    /**
     * @return string[]
     */
    public function distinctActions(): array
    {
        $statement = $this->db->query('SELECT DISTINCT action FROM audit_logs WHERE is_active = 1 ORDER BY action');

        return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @return string[]
     */
    public function distinctEntityTypes(): array
    {
        $statement = $this->db->query('SELECT DISTINCT entity_type FROM audit_logs WHERE is_active = 1 ORDER BY entity_type');

        return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    private function map(array $row): AuditLog
    {
        $metadata = json_decode((string) ($row['metadata'] ?? '{}'), true);

        return new AuditLog(
            (int) $row['id'],
            $row['user_id'] === null ? null : (int) $row['user_id'],
            $row['user_name'] ?? null,
            (string) $row['action'],
            (string) $row['entity_type'],
            $row['entity_id'] === null ? null : (int) $row['entity_id'],
            $row['ip_address'],
            $row['user_agent'],
            is_array($metadata) ? $metadata : [],
            (bool) $row['is_active'],
            $row['created_at']
        );
    }
}
