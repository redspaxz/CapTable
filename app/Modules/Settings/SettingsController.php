<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Lang;
use App\Core\Request;

class SettingsController extends Controller
{
    public function index(): string
    {
        return $this->view('settings/index', [
            'title' => 'Settings',
            'company' => \App\company(),
            'defaultLanguage' => Lang::defaultLocale(),
        ]);
    }

    public function update(): void
    {
        Csrf::verify();
        $language = Request::str('default_language', Lang::DEFAULT);
        if (!in_array($language, Lang::LOCALES, true)) {
            $language = Lang::DEFAULT;
        }
        Database::execute(
            'UPDATE settings SET default_language = ? WHERE id = (SELECT MIN(id) FROM settings)',
            [$language]
        );
        \App\flash('success', __('Default language updated.'));
        \App\redirect('/settings');
    }
}
