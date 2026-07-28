<?php

declare(strict_types=1);

namespace Core;

final class View
{
    public static function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
