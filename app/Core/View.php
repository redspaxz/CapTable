<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    /** Render a template inside the main layout. */
    public static function render(string $template, array $data = []): string
    {
        $content = self::partial($template, $data);
        $flash = \App\flash();
        return self::partial('layouts/app', [
            'content' => $content,
            'title' => $data['title'] ?? 'CapTable',
            'flash' => $flash,
        ]);
    }

    /** Render a bare template (no layout). */
    public static function partial(string $template, array $data = []): string
    {
        $file = BASE_PATH . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
