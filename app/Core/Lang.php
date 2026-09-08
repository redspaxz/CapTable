<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal bilingual UI (English default, French optional).
 *
 * English strings are the dictionary keys, so untranslated strings
 * degrade gracefully to English. The active locale comes from the
 * session (set by the /lang/{code} switcher), falling back to the
 * administrator's default in the settings table.
 */
final class Lang
{
    public const LOCALES = ['en', 'fr'];
    public const DEFAULT = 'en';

    private static string $locale = self::DEFAULT;
    private static ?array $dictionary = null;
    private static string $dictionaryLocale = '';
    private static bool $fromSession = false;

    public static function init(): void
    {
        // Never force a session here: arbitrary requests (e.g. 404s) must not
        // receive a session cookie. If a session is already active, honor the
        // user's saved choice; otherwise use the configured default. A session
        // started later in dispatch is picked up lazily by locale().
        $sessionLocale = self::sessionLocale();
        if ($sessionLocale !== null) {
            self::$locale = $sessionLocale;
            self::$fromSession = true;
            return;
        }
        self::$locale = self::defaultLocale();
    }

    private static function sessionLocale(): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $locale = $_SESSION['lang'] ?? null;
        return is_string($locale) && in_array($locale, self::LOCALES, true) ? $locale : null;
    }

    /** Administrator-configured default (settings.default_language). */
    public static function defaultLocale(): string
    {
        try {
            $row = Database::one('SELECT default_language FROM settings ORDER BY id LIMIT 1');
            $locale = is_array($row) ? (string) ($row['default_language'] ?? '') : '';
            return in_array($locale, self::LOCALES, true) ? $locale : self::DEFAULT;
        } catch (\Throwable) {
            return self::DEFAULT;
        }
    }

    public static function locale(): string
    {
        if (!self::$fromSession) {
            $sessionLocale = self::sessionLocale();
            if ($sessionLocale !== null) {
                self::$locale = $sessionLocale;
                self::$fromSession = true;
            }
        }
        return self::$locale;
    }

    public static function set(string $locale): void
    {
        Session::start();
        self::$locale = in_array($locale, self::LOCALES, true) ? $locale : self::DEFAULT;
        self::$fromSession = true;
        $_SESSION['lang'] = self::$locale;
    }

    /** Translate $key (English source text) with :placeholder replacement. */
    public static function t(string $key, array $replace = []): string
    {
        if (self::locale() !== self::DEFAULT) {
            $dictionary = self::dictionary();
            if (isset($dictionary[$key]) && $dictionary[$key] !== '') {
                $key = $dictionary[$key];
            }
        }
        if ($replace !== []) {
            foreach ($replace as $name => $value) {
                $key = str_replace(':' . $name, (string) $value, $key);
            }
        }
        return $key;
    }

    private static function dictionary(): array
    {
        $locale = self::locale();
        if (self::$dictionary === null || self::$dictionaryLocale !== $locale) {
            $file = BASE_PATH . '/app/lang/' . $locale . '.php';
            self::$dictionary = is_file($file) ? (array) require $file : [];
            self::$dictionaryLocale = $locale;
        }
        return self::$dictionary;
    }
}
