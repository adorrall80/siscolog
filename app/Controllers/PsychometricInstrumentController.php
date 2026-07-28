<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PsychometricService;
use Core\Request;
use Core\Response;
use Core\Session;
use InvalidArgumentException;
use RuntimeException;

final class PsychometricInstrumentController
{
    private PsychometricService $psychometrics;

    public function __construct()
    {
        $this->psychometrics = new PsychometricService();
    }

    public function index(): Response
    {
        return Response::view('psychometrics.instruments', [
            'instruments' => $this->psychometrics->allInstruments(),
        ]);
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->param('id');

        try {
            return Response::view('psychometrics.instrument_edit', [
                'instrument' => $this->psychometrics->findInstrument($id),
                'detail' => $this->psychometrics->instrumentDetail($id),
                'questionGroups' => $this->psychometrics->questionGroups(),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/instrumentos');
        }
    }

    public function preview(Request $request): Response
    {
        $id = (int) $request->param('id');

        try {
            return Response::view('psychometrics.instrument_preview', [
                'detail' => $this->psychometrics->instrumentDetail($id),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect('/instrumentos');
        }
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');

        try {
            $this->psychometrics->updateInstrument($id, [
                'code' => trim((string) $request->input('code')),
                'name' => trim((string) $request->input('name')),
                'description' => trim((string) $request->input('description')),
                'min_score' => (int) $request->input('min_score', 0),
                'max_score' => (int) $request->input('max_score', 0),
                'caution_cutoff' => $this->nullableInt($request->input('caution_cutoff')),
                'critical_cutoff' => $this->nullableInt($request->input('critical_cutoff')),
                'is_active' => $request->input('is_active') === '1',
            ]);
            if ((array) $request->input('items', []) !== []) {
                $this->psychometrics->replaceDefinition(
                    $id,
                    (array) $request->input('items', []),
                    (array) $request->input('levels', [])
                );
            }
            Session::flash('success', 'Instrumento actualizado correctamente.');
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            return Response::redirect("/instrumentos/{$id}/edit");
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect('/instrumentos');
    }

    public function activate(Request $request): Response
    {
        return $this->changeActiveState($request, true);
    }

    public function deactivate(Request $request): Response
    {
        return $this->changeActiveState($request, false);
    }

    private function changeActiveState(Request $request, bool $active): Response
    {
        try {
            $this->psychometrics->setInstrumentActive((int) $request->param('id'), $active);
            Session::flash('success', $active ? 'Instrumento vigente correctamente.' : 'Instrumento no vigente correctamente.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        return Response::redirect('/instrumentos');
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }
}
