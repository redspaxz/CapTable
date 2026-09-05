<?php

declare(strict_types=1);

namespace App;

/** HTML-escape a value. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Build an absolute app URL from a path. */
function url(string $path = '/'): string
{
    $base = rtrim(\App\Core\App::config('app.base_url'), '/');
    if ($base === '') {
        $base = \App\Core\Request::basePath();
    }
    return $base . '/' . ltrim($path, '/');
}

/** Redirect helper. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Format an integer amount of XAF (no decimals). */
function money(int|string|null $amount): string
{
    return number_format((float) $amount, 0, ',', ' ') . ' XAF';
}

/** Format a share count. */
function shares(int|string|null $qty): string
{
    return number_format((float) $qty, 0, ',', ' ');
}

/** Percent with 2 decimals. */
function pct(float $value): string
{
    return number_format($value, 2, ',', ' ') . ' %';
}

/** Current company profile (memoized). */
function company(): array
{
    static $company = null;
    if ($company === null) {
        $company = \App\Core\Database::one(
            'SELECT company_name AS name, legal_form, rccm, niu, head_office, currency
             FROM settings ORDER BY id LIMIT 1'
        ) ?? [];
    }
    return $company;
}

/** Flash message helper. */
function flash(?string $key = null, ?string $value = null): ?array
{
    if ($key !== null && $value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}
