<?php

declare(strict_types=1);

namespace App\Modules\Security;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\View;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;      // failed logins per window…
    private const WINDOW_SECONDS = 600;  // …per source address (10 minutes)

    public function showLogin(): string
    {
        if (Auth::check()) {
            redirect('/');
        }
        return View::render('auth/login', ['title' => 'Sign in']);
    }

    public function login(): void
    {
        Csrf::verify();
        if ($this->tooManyAttempts()) {
            \App\flash('error', __('Too many login attempts. Please try again in a few minutes.'));
            redirect('/login');
        }
        $email = Request::str('email');
        $password = Request::str('password');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && Auth::attempt($email, $password)) {
            $this->clearAttempts();
            \App\flash('success', __('Welcome!'));
            redirect('/');
        }
        $this->recordAttempt();
        \App\flash('error', __('Invalid credentials.'));
        redirect('/login');
    }

    public function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        redirect('/login');
    }

    private function attemptsFile(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        return sys_get_temp_dir() . '/captable_rl_' . md5($ip) . '.json';
    }

    private function recentAttempts(): array
    {
        $file = $this->attemptsFile();
        $attempts = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
        if (!is_array($attempts)) {
            $attempts = [];
        }
        return array_values(array_filter(
            $attempts,
            fn($t) => is_numeric($t) && time() - (int) $t < self::WINDOW_SECONDS
        ));
    }

    private function tooManyAttempts(): bool
    {
        return count($this->recentAttempts()) >= self::MAX_ATTEMPTS;
    }

    private function recordAttempt(): void
    {
        $attempts = $this->recentAttempts();
        $attempts[] = time();
        @file_put_contents($this->attemptsFile(), json_encode($attempts));
    }

    private function clearAttempts(): void
    {
        @unlink($this->attemptsFile());
    }
}
