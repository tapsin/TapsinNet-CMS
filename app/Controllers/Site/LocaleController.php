<?php
declare(strict_types=1);

namespace Controllers\Site;

use Controllers\Controller;
use Core\Config;
use Core\Response;
use Core\Security;
use Core\Session;
use Core\Translator;

/**
 * Dil değiştirme.
 *
 * Dil bir çerez + oturum ile taşınır. Geri dönüş yolu yalnızca site
 * içi mutlak yol olarak kabul edilir (açık yönlendirme koruması).
 */
final class LocaleController extends Controller
{
    protected string $viewPrefix = 'site';

    public function show(): Response
    {
        return $this->view('search', [
            'term' => $this->term(), 'groups' => [], 'total' => 0,
            'metaTitle' => '', 'metaDesc' => '',
        ], 'layouts.site');
    }

    public function set(array $params): Response
    {
        $code = (string) $params['code'];

        if (!in_array($code, Translator::available(), true)) {
            $this->notFound();
        }

        Translator::setLang($code);
        Session::put((string) Config::get('i18n.session_key'), $code);

        $back = $this->request->cookie((string) Config::get('i18n.cookie')) ?? '/';
        $back = Security::safeRedirect($back, '/');

        // Gerçek çerez — JS dil bağlantılarında da okunabilsin diye HttpOnly=false
        // (yalnızca dil tercihi, oturum verisi DEĞİL)
        return Response::redirect($back)
            ->cookie((string) Config::get('i18n.cookie'), $code, [
                'expires'  => time() + 31536000,
                'path'     => '/',
                'secure'   => $this->request->isSecure(),
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
    }
}
