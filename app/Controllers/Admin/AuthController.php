<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\HttpException;
use Core\Logger;
use Core\Response;
use Core\Security;
use Core\Session;
use Models\User;

/**
 * Yönetici oturumu.
 */
final class AuthController extends Controller
{
    protected string $viewPrefix = 'admin.auth';
    protected string $indexRoute = '/admin';

    public function showLogin(): Response
    {
        if (Auth::check()) {
            return $this->redirect('/admin');
        }
        return $this->viewNoLayout('login');
    }

    public function login(): Response
    {
        Csrf::verifyOrFail($this->request);

        // Tek alan: kullanıcı adı ya da e-posta. İkisi de Auth::attempt içinde.
        $login    = mb_substr(trim((string) $this->request->post('login', '')), 0, 190);
        $password = (string) $this->request->post('password', '');
        $remember = $this->request->bool('remember');

        // Boş gönderimlerde gereksiz hash doğrulaması yapma
        if ($login === '' || $password === '') {
            Session::flash('error', t('auth.credentials'));
            return $this->redirect('/admin/giris');
        }

        $ok = Auth::attempt($login, $password, $this->request);

        if (!$ok) {
            // Hesap kilidi kontrolü
            $max = (int) \Core\Config::get('security.throttle.login.max', 6);
            $decay = (int) \Core\Config::get('security.throttle.login.decay', 900);
            $remaining = \Core\RateLimiter::remaining('login:' . $this->request->ip(), $max, $decay);

            if ($remaining === 0) {
                $wait = (int) ceil($decay / 60);
                Session::flash('error', t('auth.too_many', ['minutes' => $wait]));
            } else {
                Session::flash('error', t('auth.credentials'));
            }
            return $this->redirect('/admin/giris');
        }

        $intended = Session::pull('intended');
        $target = is_string($intended) && str_starts_with($intended, '/admin')
            ? $intended
            : '/admin';

        Session::flash('success', t('auth.welcome', ['name' => Auth::user()['name'] ?? '']));

        return $this->redirect($target);
    }

    public function logout(): Response
    {
        if ($this->request->isPost()) {
            Csrf::verifyOrFail($this->request);
        }
        Auth::logout();
        Session::start();
        Session::flash('success', t('auth.logged_out'));
        return $this->redirect('/admin/giris');
    }
}
