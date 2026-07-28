<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\MaintainerService;
use App\Services\SessionTopicSubtypeService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class MaintainerController
{
    private MaintainerService $maintainers;
    private SessionTopicSubtypeService $topicSubtypes;

    public function __construct()
    {
        $this->maintainers = new MaintainerService();
        $this->topicSubtypes = new SessionTopicSubtypeService();
    }

    public function index(Request $request): Response
    {
        return Response::view('maintainers.index', [
            'tables' => $this->maintainers->tables(),
            'tableLabels' => $this->maintainers->labels(),
            'tableSlugs' => $this->maintainers->slugs(),
            'tableSummaries' => $this->maintainers->summaries(),
        ]);
    }

    public function table(Request $request): Response
    {
        $requestedTable = (string) $request->param('table');
        $table = $this->maintainers->resolveTable($requestedTable);
        $tableSlug = $this->maintainers->slugFor($table);
        $filter = $this->maintainers->normalizeFilter((string) $request->input('status', 'all'));
        $search = $this->maintainers->normalizeSearch((string) $request->input('q', ''));
        $contextQuery = $this->contextQuery($filter, $search);

        if ($requestedTable !== $tableSlug) {
            return Response::redirect("/maintainers/{$tableSlug}{$contextQuery}");
        }

        return Response::view('maintainers.table', [
            'table' => $table,
            'tableSlug' => $tableSlug,
            'tableLabel' => $this->maintainers->labelFor($table),
            'items' => $this->maintainers->filtered($table, $filter, $search),
            'activeFilter' => $filter,
            'activeSearch' => $search,
            'contextQuery' => $contextQuery,
            'summary' => $this->maintainers->summaryFor($table),
            'tables' => $this->maintainers->tables(),
            'tableLabels' => $this->maintainers->labels(),
            'tableSlugs' => $this->maintainers->slugs(),
        ]);
    }

    public function create(Request $request): Response
    {
        $requestedTable = (string) $request->param('table');
        $table = $this->maintainers->resolveTable($requestedTable);
        $tableSlug = $this->maintainers->slugFor($table);

        if ($requestedTable !== $tableSlug) {
            return Response::redirect("/maintainers/{$tableSlug}/create");
        }

        return Response::view('maintainers.create', [
            'table' => $table,
            'tableSlug' => $tableSlug,
            'tableLabel' => $this->maintainers->labelFor($table),
            'tables' => $this->maintainers->tables(),
            'tableLabels' => $this->maintainers->labels(),
            'tableSlugs' => $this->maintainers->slugs(),
        ]);
    }

    public function store(Request $request): Response
    {
        $table = $this->maintainers->resolveTable((string) $request->param('table'));
        $tableSlug = $this->maintainers->slugFor($table);

        try {
            $data = $this->data($request);

            if ($table === MaintainerService::SESSION_TOPIC_TYPES) {
                $data['created_by_user_id'] = (int) ($this->currentUser()['id'] ?? 0);
                $data['is_public'] = (int) $request->input('is_public', 1);
            }

            $this->maintainers->create($table, $data);
            Session::flash('success', 'Valor mantenedor creado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/maintainers/{$tableSlug}/create");
        }

        return Response::redirect("/maintainers/{$tableSlug}");
    }

    public function edit(Request $request): Response
    {
        $requestedTable = (string) $request->param('table');
        $table = $this->maintainers->resolveTable($requestedTable);
        $tableSlug = $this->maintainers->slugFor($table);
        $id = (int) $request->param('id');
        $filter = $this->maintainers->normalizeFilter((string) $request->input('status', 'all'));
        $search = $this->maintainers->normalizeSearch((string) $request->input('q', ''));
        $contextQuery = $this->contextQuery($filter, $search);

        if ($requestedTable !== $tableSlug) {
            return Response::redirect("/maintainers/{$tableSlug}/{$id}/edit{$contextQuery}");
        }

        try {
            return Response::view('maintainers.edit', [
                'table' => $table,
                'tableSlug' => $tableSlug,
                'tableLabel' => $this->maintainers->labelFor($table),
                'contextQuery' => $contextQuery,
                'tables' => $this->maintainers->tables(),
                'tableLabels' => $this->maintainers->labels(),
                'tableSlugs' => $this->maintainers->slugs(),
                'item' => $this->maintainers->find($table, $id),
                'topicSubtypes' => $table === MaintainerService::SESSION_TOPIC_TYPES ? $this->topicSubtypes->byType($id, $this->currentUser()) : [],
                'availableTopicSubtypes' => $table === MaintainerService::SESSION_TOPIC_TYPES ? $this->topicSubtypes->availableForType($id, $this->currentUser()) : [],
            ]);
        } catch (RuntimeException|InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/maintainers');
        }
    }

    public function update(Request $request): Response
    {
        $table = $this->maintainers->resolveTable((string) $request->param('table'));
        $tableSlug = $this->maintainers->slugFor($table);
        $id = (int) $request->param('id');
        $filter = $this->maintainers->normalizeFilter((string) $request->input('status', 'all'));
        $search = $this->maintainers->normalizeSearch((string) $request->input('q', ''));
        $contextQuery = $this->contextQuery($filter, $search);

        try {
            $this->maintainers->update($table, $id, $this->data($request));
            Session::flash('success', 'Valor mantenedor actualizado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/maintainers/{$tableSlug}/{$id}/edit{$contextQuery}");
        }

        return Response::redirect("/maintainers/{$tableSlug}{$contextQuery}");
    }

    public function storeTopicSubtype(Request $request): Response
    {
        $topicTypeId = (int) $request->param('id');
        $isAsync = (string) $request->input('async', '0') === '1';

        try {
            $this->maintainers->find(MaintainerService::SESSION_TOPIC_TYPES, $topicTypeId);
            $existingSubtypeId = (int) $request->input('existing_subtype_id', 0);
            $subtypeName = '';
            $subtypeId = 0;
            $relationId = 0;

            if ($existingSubtypeId > 0) {
                $relationId = $this->topicSubtypes->relateExisting($topicTypeId, $existingSubtypeId, $this->currentUser());
                $subtypeId = $existingSubtypeId;
                $subtypeName = trim((string) $request->input('subtype_search'));
                Session::flash('success', 'Subtipo existente asociado correctamente.');
            } else {
                $subtypeName = trim((string) $request->input('subtype_name')) ?: trim((string) $request->input('subtype_search'));
                $created = $this->topicSubtypes->createAndRelateWithRelation($topicTypeId, [
                    'code' => trim((string) $request->input('subtype_code')),
                    'name' => $subtypeName,
                    'description' => trim((string) $request->input('subtype_description')),
                    'color' => trim((string) $request->input('subtype_color')),
                    'sort_order' => (int) $request->input('subtype_sort_order', 0),
                ], $this->currentUser());
                $subtypeId = $created['subtype_id'];
                $relationId = $created['relation_id'];
                Session::flash('success', 'Subtipo nuevo creado y asociado correctamente.');
            }

            if ($isAsync) {
                return Response::json([
                    'ok' => true,
                    'message' => 'Subtipo asociado correctamente.',
                    'subtype' => [
                        'id' => $subtypeId,
                        'name' => $subtypeName,
                    ],
                    'relation' => [
                        'id' => $relationId,
                    ],
                ]);
            }
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());

            if ($isAsync) {
                return Response::json([
                    'ok' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }
        }

        return Response::redirect("/maintainers/tipos-tema-sesion/{$topicTypeId}/edit");
    }

    public function activateTopicSubtype(Request $request): Response
    {
        return $this->setTopicSubtypeActive($request, true);
    }

    public function deactivateTopicSubtype(Request $request): Response
    {
        return $this->setTopicSubtypeActive($request, false);
    }

    public function deleteTopicSubtype(Request $request): Response
    {
        $topicTypeId = (int) $request->param('id');
        $relationId = (int) $request->param('subtypeId');
        $isAsync = (string) $request->input('async', '0') === '1';

        try {
            $this->maintainers->find(MaintainerService::SESSION_TOPIC_TYPES, $topicTypeId);
            $this->topicSubtypes->deleteRelation($topicTypeId, $relationId, $this->currentUser());
            Session::flash('success', 'Relacion quitada correctamente.');

            if ($isAsync) {
                return Response::json([
                    'ok' => true,
                    'message' => 'Relacion quitada correctamente.',
                ]);
            }
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());

            if ($isAsync) {
                return Response::json([
                    'ok' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }
        }

        return Response::redirect("/maintainers/tipos-tema-sesion/{$topicTypeId}/edit");
    }

    private function setTopicSubtypeActive(Request $request, bool $active): Response
    {
        $topicTypeId = (int) $request->param('id');
        $subtypeId = (int) $request->param('subtypeId');

        try {
            $this->maintainers->find(MaintainerService::SESSION_TOPIC_TYPES, $topicTypeId);
            $this->topicSubtypes->setActive($topicTypeId, $subtypeId, $active, $this->currentUser());
            Session::flash('success', $active ? 'Relacion activada correctamente.' : 'Relacion desactivada correctamente.');
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/maintainers/tipos-tema-sesion/{$topicTypeId}/edit");
    }

    public function publishTopicSubtype(Request $request): Response
    {
        return $this->setTopicSubtypePublic($request, true);
    }

    public function privatizeTopicSubtype(Request $request): Response
    {
        return $this->setTopicSubtypePublic($request, false);
    }

    private function setTopicSubtypePublic(Request $request, bool $isPublic): Response
    {
        $topicTypeId = (int) $request->param('id');
        $relationId = (int) $request->param('subtypeId');

        try {
            $topic = $this->maintainers->find(MaintainerService::SESSION_TOPIC_TYPES, $topicTypeId);

            if ($isPublic && !$topic->isPublic) {
                throw new RuntimeException('Primero debes publicar el tema antes de publicar una de sus relaciones.');
            }

            $this->topicSubtypes->setPublic($topicTypeId, $relationId, $isPublic, $this->currentUser());
            Session::flash('success', $isPublic ? 'Relacion publicada correctamente.' : 'Relacion marcada como privada.');
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/maintainers/tipos-tema-sesion/{$topicTypeId}/edit");
    }

    public function publishTopicType(Request $request): Response
    {
        return $this->setTopicTypePublic($request, true);
    }

    public function privatizeTopicType(Request $request): Response
    {
        return $this->setTopicTypePublic($request, false);
    }

    private function setTopicTypePublic(Request $request, bool $isPublic): Response
    {
        $id = (int) $request->param('id');

        try {
            $this->maintainers->setTopicTypePublic($id, $isPublic, $this->currentUser());
            Session::flash('success', $isPublic ? 'Tema publicado correctamente.' : 'Tema marcado como privado.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect('/maintainers/tipos-tema-sesion');
    }

    public function activate(Request $request): Response
    {
        return $this->setActive($request, true);
    }

    public function deactivate(Request $request): Response
    {
        return $this->setActive($request, false);
    }

    private function setActive(Request $request, bool $active): Response
    {
        $table = $this->maintainers->resolveTable((string) $request->param('table'));
        $tableSlug = $this->maintainers->slugFor($table);
        $id = (int) $request->param('id');
        $filter = $this->maintainers->normalizeFilter((string) $request->input('status', 'all'));
        $search = $this->maintainers->normalizeSearch((string) $request->input('q', ''));

        try {
            $this->maintainers->setActive($table, $id, $active);
            Session::flash('success', $active ? 'Valor activado correctamente.' : 'Valor desactivado correctamente.');
        } catch (RuntimeException|InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect("/maintainers/{$tableSlug}" . $this->contextQuery($filter, $search));
    }

    private function contextQuery(string $filter, string $search): string
    {
        $query = [];

        if ($filter !== 'all') {
            $query['status'] = $this->publicFilterValue($filter);
        }

        if ($search !== '') {
            $query['q'] = $search;
        }

        return $query === [] ? '' : '?' . http_build_query($query);
    }

    private function publicFilterValue(string $filter): string
    {
        return [
            'active' => 'activos',
            'inactive' => 'inactivos',
        ][$filter] ?? 'todos';
    }

    private function data(Request $request): array
    {
        $name = trim((string) $request->input('name'));
        $code = trim((string) $request->input('code'));

        return [
            'code' => $code === '' ? $this->slug($name) : $code,
            'name' => $name,
            'description' => trim((string) $request->input('description')),
            'color' => trim((string) $request->input('color')),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => (int) $request->input('is_active', 1),
        ];
    }

    private function currentUser(): array
    {
        $user = Session::get('user', []);

        return is_array($user) ? $user : [];
    }

    private function slug(string $value): string
    {
        $value = strtolower($value);
        $value = str_replace(
            [' ', '/', '(', ')', '.', ',', 'á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['_', '_', '', '', '', '', 'a', 'e', 'i', 'o', 'u', 'n'],
            $value
        );

        return preg_replace('/[^a-z0-9_]+/', '', $value) ?: 'valor';
    }
}
