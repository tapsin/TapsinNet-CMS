<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Csrf;
use Core\ModuleRegistry;
use Core\Response;
use Models\Module;

/**
 * Modül yönetimi — aktif/pasif anahtarı ve sıralama.
 */
final class ModuleController extends Controller
{
    protected string $viewPrefix = 'admin';
    protected string $indexRoute = '/admin/moduller';

    public function index(): Response
    {
        $catalog = Module::catalog();

        // Her modülün içerik sayısı
        foreach ($catalog as &$m) {
            $m['table'] = $m['table'] ?? $m['adminTable'] ?? \Core\ModuleRegistry::table($m['slug']);
            $m['count'] = $m['table'] !== null ? Module::contentCount($m['slug']) : null;
        }
        unset($m);

        return $this->view('modules', [
            'catalog' => $catalog,
            'groups'  => [
                'content' => t('admin.modules.content'),
                'social'  => t('admin.modules.social'),
                'system'  => t('admin.modules.system'),
            ],
        ], 'layouts.admin');
    }

    public function action(): Response
    {
        Csrf::verifyOrFail($this->request);

        $op  = (string) $this->request->post('islem', '');
        $slug = (string) $this->request->post('slug', '');

        if (!ModuleRegistry::exists($slug)) {
            return $this->fail(t('admin.unknown_module'));
        }

        switch ($op) {
            case 'aktif':
            case 'pasif':
                Module::setModuleActive($slug, $op === 'aktif');
                return $this->ok(t($op === 'aktif' ? 'admin.module_on' : 'admin.module_off', [
                    'module' => ModuleRegistry::name($slug),
                ]));
            case 'sira':
                $order = (int) $this->request->post('sirala', 0);
                Module::setOrder($slug, $order);
                return $this->ok(t('admin.module_reordered'));
        }

        return $this->fail(t('admin.unknown_action'));
    }
}
