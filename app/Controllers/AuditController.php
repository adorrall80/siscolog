<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditService;
use App\Services\UserService;
use Core\Request;
use Core\Response;

final class AuditController
{
    private AuditService $audit;
    private UserService $users;

    public function __construct()
    {
        $this->audit = new AuditService();
        $this->users = new UserService();
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'action' => trim((string) $request->input('action', '')),
            'entity_type' => trim((string) $request->input('entity_type', '')),
            'user_id' => (int) $request->input('user_id', 0),
            'date_from' => trim((string) $request->input('date_from', '')),
            'date_to' => trim((string) $request->input('date_to', '')),
        ];

        return Response::view('audit.index', $this->audit->dashboard($filters) + [
            'filters' => $filters,
            'users' => $this->users->all(),
        ]);
    }
}
