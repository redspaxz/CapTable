<?php

declare(strict_types=1);

namespace App;

/** HTML-escape a value. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Translate an English source string into the active UI language. */
function __(string $key, array $replace = []): string
{
    return \App\Core\Lang::t($key, $replace);
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

/**
 * Asset URL with a cache-busting version (file mtime). Assets are served
 * with a long max-age (Cloudflare/browser caches), so an unversioned URL
 * keeps serving the stale copy after a deploy replaces the file. Because
 * each deploy rewrites the file with a fresh mtime, the query string
 * changes and caches fetch the new version automatically.
 */
function asset(string $path): string
{
    $file = BASE_PATH . '/public' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '0';
    return url($path) . '?v=' . $version;
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

/** Current company profile of the ACTIVE tenant (memoized). */
function company(): array
{
    static $company = null;
    if ($company === null) {
        $tenantId = \App\Core\Tenancy::id();
        $company = $tenantId !== null
            ? (\App\Core\Database::one(
                'SELECT company_name AS name, legal_form, rccm, niu, head_office, currency,
                        fmv_per_share, secondary_currency, fx_rate, option_tax_rate, default_language
                 FROM settings WHERE tenant_id = ?',
                [$tenantId]
              ) ?? [])
            : [];
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
