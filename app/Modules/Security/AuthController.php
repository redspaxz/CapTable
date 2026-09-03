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
    public function showLogin(): string
    {
        if (Auth::check()) {
            redirect('/');
        }
        return View::render('auth/login', ['title' => 'Connexion']);
    }

    public function login(): void
    {
        Csrf::verify();
        $email = Request::str('email');
        $password = Request::str('password');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && Auth::attempt($email, $password)) {
            \App\flash('success', 'Bienvenue !');
            redirect('/');
        }
        \App\flash('error', 'Identifiants invalides.');
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
