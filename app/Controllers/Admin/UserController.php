<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Auth;
use Core\Csrf;
use Core\Response;
use Core\Session;
use Core\Validator;
use Models\User;

/**
 * Hesap yönetimi.
 */
final class UserController extends Controller
{
    protected string $viewPrefix = 'admin.user';
    protected string $indexRoute = '/admin/hesap';

    public function edit(): Response
    {
        return $this->view('edit', [
            'user'   => Auth::user(),
            'score'  => null,
        ], 'layouts.admin');
    }

    public function update(): Response
    {
        Csrf::verifyOrFail($this->request);

        $id    = (int) Auth::id();
        $name  = mb_substr(trim((string) $this->request->post('name', '')), 0, 160);
        $email = mb_strtolower(trim((string) $this->request->post('email', '')), 0, 190);
        // Kullanıcı adı isteğe bağlı: boş bırakılırsa yalnızca e-posta ile
        // girilir. Sadece harf, rakam, alt çizgi ve tire kabul edilir.
        $rawUser = trim((string) $this->request->post('username', ''));
        $username = mb_substr($rawUser, 0, 40);

        $v = Validator::make(
            ['name' => $name, 'email' => $email, 'username' => $username],
            [
                'name'     => 'required|string|max:160',
                'email'    => 'required|email|max:190',
                'username' => 'nullable|string|max:40|regex:/^[A-Za-z0-9._-]+$/',
            ],
            ['name' => t('user.name'), 'email' => t('user.email'), 'username' => t('user.username')]
        );

        if ($v->fails()) {
            return $this->withErrors($this->request->all(), ['genel' => $v->firstError()]);
        }

        if (User::emailExists($email, $id)) {
            return $this->withErrors($this->request->all(), ['email' => t('user.email_taken')]);
        }

        if ($username !== '' && User::usernameExists($username, $id)) {
            return $this->withErrors($this->request->all(), ['username' => t('user.username_taken')]);
        }

        User::updateProfile($id, [
            'name'     => $name,
            'username' => $username === '' ? null : $username,
            'email'    => $email,
        ]);
        Auth::reset();

        return $this->ok(t('user.profile_saved'));
    }

    public function password(): Response
    {
        Csrf::verifyOrFail($this->request);

        $current = (string) $this->request->post('current_password', '');
        $new     = (string) $this->request->post('password', '');
        $confirm = (string) $this->request->post('password_confirmation', '');

        $user = Auth::user();
        if ($user === null) {
            return $this->fail(t('auth.session_expired'));
        }

        if (!password_verify($current, (string) $user['password_hash'])) {
            return $this->withErrors($this->request->all(), ['current_password' => t('user.wrong_password')]);
        }

        $v = Validator::make(
            ['password' => $new, 'password_confirmation' => $confirm],
            ['password' => 'required|password', 'password_confirmation' => 'required|same:password'],
            ['password' => t('user.new_password'), 'password_confirmation' => t('user.confirm_password')]
        );

        if ($v->fails()) {
            return $this->withErrors($this->request->all(), $v->flatErrors());
        }

        User::updatePassword((int) $user['id'], $new);
        Session::flash('success', t('user.password_saved'));

        return $this->redirect('/admin/hesap');
    }
}
