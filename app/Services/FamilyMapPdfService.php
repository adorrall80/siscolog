<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;

final class FamilyMapPdfService
{
    private const PAGE_WIDTH = 842.0;
    private const PAGE_HEIGHT = 595.0;

    public function build(Patient $patient, array $graph): string
    {
        $nodes = $graph['nodes'] ?? [];
        $relationships = $graph['relationships'] ?? [];
        $commands = [];

        $commands[] = $this->rect(0, 535, self::PAGE_WIDTH, 60, [0.05, 0.22, 0.42], null);
        $commands[] = $this->text('SisColog', 30, 568, 18, [1, 1, 1], true);
        $commands[] = $this->text('Mapa de relaciones', 30, 547, 11, [0.78, 0.88, 1]);
        $commands[] = $this->text($patient->fullName, 810, 567, 16, [1, 1, 1], true, true);
        $commands[] = $this->text('Ficha ' . $patient->code . ' | Generado ' . date('d/m/Y H:i'), 810, 548, 9, [0.82, 0.9, 1], false, true);

        $plotX = 28.0;
        $plotY = 108.0;
        $plotWidth = 786.0;
        $plotHeight = 408.0;
        $commands[] = $this->rect($plotX, $plotY, $plotWidth, $plotHeight, [0.97, 0.985, 1], [0.72, 0.82, 0.92], 1);

        $positions = [];
        $nodeByKey = [];
        $radius = count($nodes) >= 8 ? 32.0 : 38.0;

        foreach ($nodes as $node) {
            $x = $plotX + 18 + ((float) ($node['x'] ?? 50) / 100) * ($plotWidth - 36);
            $y = $plotY + 18 + ((100 - (float) ($node['y'] ?? 50)) / 100) * ($plotHeight - 36);
            $positions[(string) $node['key']] = ['x' => $x, 'y' => $y];
            $nodeByKey[(string) $node['key']] = $node;
        }

        foreach ($relationships as $relationship) {
            $from = $positions[(string) ($relationship['from'] ?? '')] ?? null;
            $to = $positions[(string) ($relationship['to'] ?? '')] ?? null;

            if ($from === null || $to === null) {
                continue;
            }

            $dx = $to['x'] - $from['x'];
            $dy = $to['y'] - $from['y'];
            $distance = max(1.0, sqrt(($dx * $dx) + ($dy * $dy)));
            $ux = $dx / $distance;
            $uy = $dy / $distance;
            $startX = $from['x'] + ($ux * ($radius + 4));
            $startY = $from['y'] + ($uy * ($radius + 4));
            $endX = $to['x'] - ($ux * ($radius + 7));
            $endY = $to['y'] - ($uy * ($radius + 7));

            $commands[] = $this->line($startX, $startY, $endX, $endY, [0.08, 0.35, 0.64], 1.8);
            $commands[] = $this->arrow($endX, $endY, $ux, $uy, [0.08, 0.35, 0.64]);

            if (!empty($relationship['is_bidirectional'])) {
                $commands[] = $this->arrow($startX, $startY, -$ux, -$uy, [0.08, 0.35, 0.64]);
            }

            $label = $this->truncate((string) ($relationship['label'] ?? ''), 22);
            $middleX = ($startX + $endX) / 2;
            $middleY = ($startY + $endY) / 2;
            $badgeWidth = min(118.0, max(42.0, strlen($label) * 5.2 + 16));
            $commands[] = $this->rect($middleX - ($badgeWidth / 2), $middleY - 8, $badgeWidth, 17, [0.91, 0.95, 1], [0.42, 0.62, 0.84], 0.7);
            $commands[] = $this->text($label, $middleX, $middleY - 2.5, 7.5, [0.04, 0.24, 0.5], true, false, true);
        }

        foreach ($nodes as $node) {
            $position = $positions[(string) $node['key']];
            $isPatient = ($node['kind'] ?? '') === 'patient';
            $fill = $isPatient ? [0.9, 0.98, 0.93] : $this->participantColor((string) $node['role']);
            $stroke = $isPatient ? [0.08, 0.55, 0.34] : [0.34, 0.48, 0.68];

            $commands[] = $this->circle($position['x'], $position['y'], $radius, $fill, $stroke, 1.2);
            $commands[] = $this->person(
                $position['x'],
                $position['y'] + 2,
                $isPatient ? [0.1, 0.44, 0.36] : [0.18, 0.35, 0.58]
            );

            $name = $this->truncate((string) ($node['label'] ?? ''), count($nodes) >= 8 ? 15 : 19);
            $role = strtoupper($this->truncate((string) ($node['role'] ?? ''), 16));
            $commands[] = $this->text($name, $position['x'], $position['y'] - $radius + 14, 6.8, [0.02, 0.1, 0.2], true, false, true);
            $commands[] = $this->text($role, $position['x'], $position['y'] - $radius + 5.5, 5.4, [0.28, 0.35, 0.45], true, false, true);

            $status = (string) ($node['participation_status'] ?? 'NP');
            $commands[] = $this->circle($position['x'] + $radius - 5, $position['y'] + $radius - 5, 8, $status === 'SP' ? [0.84, 0.96, 0.88] : [1, 0.94, 0.72], $status === 'SP' ? [0.2, 0.65, 0.38] : [0.86, 0.63, 0.12], 0.6);
            $commands[] = $this->text($status, $position['x'] + $radius - 5, $position['y'] + $radius - 7, 5.8, [0.08, 0.32, 0.2], true, false, true);
        }

        $commands[] = $this->text('Relaciones registradas', 30, 88, 9, [0.05, 0.22, 0.42], true);
        $maxListed = 6;

        foreach (array_slice($relationships, 0, $maxListed) as $index => $relationship) {
            $column = $index % 3;
            $row = intdiv($index, 3);
            $x = 30 + ($column * 265);
            $y = 70 - ($row * 17);
            $symbol = !empty($relationship['is_bidirectional']) ? '<->' : '->';
            $summary = $this->truncate(
                (string) $relationship['from_label'] . ' ' . $symbol . ' '
                . (string) $relationship['label'] . ' ' . $symbol . ' '
                . (string) $relationship['to_label'],
                43
            );
            $commands[] = $this->text($summary, $x, $y, 7.3, [0.18, 0.24, 0.33]);
        }

        if (count($relationships) > $maxListed) {
            $commands[] = $this->text(
                '+ ' . (count($relationships) - $maxListed) . ' relaciones adicionales',
                30,
                34,
                7,
                [0.35, 0.42, 0.52]
            );
        }

        $commands[] = $this->line(28, 24, 814, 24, [0.78, 0.82, 0.88], 0.5);
        $commands[] = $this->text('Documento de apoyo clinico - SisColog', 30, 12, 6.5, [0.42, 0.47, 0.55]);
        $commands[] = $this->text('Pagina 1 de 1', 812, 12, 6.5, [0.42, 0.47, 0.55], false, true);

        return $this->pdf(implode("\n", $commands));
    }

    private function participantColor(string $role): array
    {
        $palette = [
            [0.9, 0.95, 1],
            [0.96, 0.91, 1],
            [1, 0.94, 0.86],
            [0.88, 0.97, 0.96],
            [1, 0.91, 0.95],
        ];

        return $palette[abs(crc32($role)) % count($palette)];
    }

    private function rect(float $x, float $y, float $width, float $height, ?array $fill, ?array $stroke, float $lineWidth = 1): string
    {
        $commands = ['q'];

        if ($fill !== null) {
            $commands[] = $this->color($fill, 'rg');
        }

        if ($stroke !== null) {
            $commands[] = $this->color($stroke, 'RG');
            $commands[] = $this->number($lineWidth) . ' w';
        }

        $operator = $fill !== null && $stroke !== null ? 'B' : ($fill !== null ? 'f' : 'S');
        $commands[] = implode(' ', array_map([$this, 'number'], [$x, $y, $width, $height])) . " re {$operator}";
        $commands[] = 'Q';

        return implode("\n", $commands);
    }

    private function line(float $x1, float $y1, float $x2, float $y2, array $color, float $width): string
    {
        return 'q ' . $this->color($color, 'RG') . ' ' . $this->number($width) . ' w '
            . $this->number($x1) . ' ' . $this->number($y1) . ' m '
            . $this->number($x2) . ' ' . $this->number($y2) . " l S Q";
    }

    private function arrow(float $x, float $y, float $ux, float $uy, array $color): string
    {
        $size = 8.0;
        $baseX = $x - ($ux * $size);
        $baseY = $y - ($uy * $size);
        $perpX = -$uy * ($size * 0.48);
        $perpY = $ux * ($size * 0.48);

        return 'q ' . $this->color($color, 'rg') . ' '
            . $this->number($x) . ' ' . $this->number($y) . ' m '
            . $this->number($baseX + $perpX) . ' ' . $this->number($baseY + $perpY) . ' l '
            . $this->number($baseX - $perpX) . ' ' . $this->number($baseY - $perpY) . ' l h f Q';
    }

    private function person(float $x, float $y, array $bodyColor): string
    {
        $body = $this->color($bodyColor, 'RG');
        $commands = [
            $this->circle($x, $y + 11, 5.8, [1, 0.86, 0.7], [0.72, 0.58, 0.45], 0.45),
            'q ' . $body . ' 3.8 w 1 J '
                . $this->number($x) . ' ' . $this->number($y + 4) . ' m '
                . $this->number($x) . ' ' . $this->number($y - 8) . ' l S Q',
            'q ' . $body . ' 3.2 w 1 J '
                . $this->number($x) . ' ' . $this->number($y + 1) . ' m '
                . $this->number($x - 10) . ' ' . $this->number($y - 4) . ' l S '
                . $this->number($x) . ' ' . $this->number($y + 1) . ' m '
                . $this->number($x + 10) . ' ' . $this->number($y - 4) . ' l S Q',
            'q ' . $body . ' 3.4 w 1 J '
                . $this->number($x) . ' ' . $this->number($y - 7) . ' m '
                . $this->number($x - 7) . ' ' . $this->number($y - 16) . ' l S '
                . $this->number($x) . ' ' . $this->number($y - 7) . ' m '
                . $this->number($x + 7) . ' ' . $this->number($y - 16) . ' l S Q',
        ];

        return implode("\n", $commands);
    }

    private function circle(float $x, float $y, float $radius, ?array $fill, ?array $stroke, float $lineWidth = 1): string
    {
        $k = $radius * 0.5522848;
        $path = $this->number($x + $radius) . ' ' . $this->number($y) . ' m '
            . $this->number($x + $radius) . ' ' . $this->number($y + $k) . ' '
            . $this->number($x + $k) . ' ' . $this->number($y + $radius) . ' '
            . $this->number($x) . ' ' . $this->number($y + $radius) . ' c '
            . $this->number($x - $k) . ' ' . $this->number($y + $radius) . ' '
            . $this->number($x - $radius) . ' ' . $this->number($y + $k) . ' '
            . $this->number($x - $radius) . ' ' . $this->number($y) . ' c '
            . $this->number($x - $radius) . ' ' . $this->number($y - $k) . ' '
            . $this->number($x - $k) . ' ' . $this->number($y - $radius) . ' '
            . $this->number($x) . ' ' . $this->number($y - $radius) . ' c '
            . $this->number($x + $k) . ' ' . $this->number($y - $radius) . ' '
            . $this->number($x + $radius) . ' ' . $this->number($y - $k) . ' '
            . $this->number($x + $radius) . ' ' . $this->number($y) . ' c';
        $operator = $fill !== null && $stroke !== null ? 'B' : ($fill !== null ? 'f' : 'S');

        return 'q '
            . ($fill !== null ? $this->color($fill, 'rg') . ' ' : '')
            . ($stroke !== null ? $this->color($stroke, 'RG') . ' ' . $this->number($lineWidth) . ' w ' : '')
            . $path . " {$operator} Q";
    }

    private function text(
        string $text,
        float $x,
        float $y,
        float $size,
        array $color,
        bool $bold = false,
        bool $alignRight = false,
        bool $center = false
    ): string
    {
        $encoded = $this->encode($text);
        $estimatedWidth = strlen($encoded) * $size * 0.5;
        $textX = $center ? $x - ($estimatedWidth / 2) : ($alignRight ? $x - $estimatedWidth : $x);

        return 'BT /' . ($bold ? 'F2' : 'F1') . ' ' . $this->number($size) . ' Tf '
            . $this->color($color, 'rg') . ' '
            . $this->number($textX) . ' ' . $this->number($y) . ' Td ('
            . $this->escape($encoded) . ') Tj ET';
    }

    private function pdf(string $stream): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>',
            4 => '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream",
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            6 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 7\n0000000000 65535 f \n";

        for ($number = 1; $number <= 6; $number++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$number]) . "\n";
        }

        return $pdf . "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function encode(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);

        return $encoded === false ? preg_replace('/[^\x20-\x7E]/', '', $text) ?? '' : $encoded;
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function color(array $color, string $operator): string
    {
        return implode(' ', array_map([$this, 'number'], $color)) . ' ' . $operator;
    }

    private function number(float|int $number): string
    {
        return rtrim(rtrim(number_format((float) $number, 3, '.', ''), '0'), '.');
    }

    private function truncate(string $text, int $length): string
    {
        return mb_strlen($text) <= $length ? $text : mb_substr($text, 0, $length - 3) . '...';
    }
}
