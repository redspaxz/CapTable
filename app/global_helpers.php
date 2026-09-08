<?php

declare(strict_types=1);

/**
 * Global-namespace aliases for the App\helpers so templates and controllers
 * can call e(), url(), redirect()… unqualified from any namespace.
 */

function e(mixed $value): string
{
    return \App\e($value);
}

function __(string $key, array $replace = []): string
{
    return \App\__($key, $replace);
}

function url(string $path = '/'): string
{
    return \App\url($path);
}

function redirect(string $path): void
{
    \App\redirect($path);
}

function money(int|string|null $amount): string
{
    return \App\money($amount);
}

function shares(int|string|null $qty): string
{
    return \App\shares($qty);
}

function pct(float $value): string
{
    return \App\pct($value);
}

function flash(?string $key = null, ?string $value = null): ?array
{
    return \App\flash($key, $value);
}
