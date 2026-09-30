<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Core\HttpException;
use Core\Response;

/**
 * İÇERİK VEKİLİ
 *
 * Modül CRUD rotaları tek bir noktaya bağlıdır; istek geldiğinde yolun
 * ilk segmentindeki modül slug'ına karşılık gelen SOMUT controller'a
 * devredilir. Böylece routes/admin.php modül listesini bilmek zorunda
 * kalmaz ve yeni bir içerik modülü eklemek yalnızca iki satır gerektirir.
 */
final class ContentController extends ResourceController
{
    /** Bu vekil kendi modülüne bağlı değildir — kapı uygulanmaz. */
    public static function moduleSlug(): string
    {
        return '';
    }

    public function index(array $params = []): Response
    {
        return $this->delegate('index', $params);
    }

    public function create(array $params = []): Response
    {
        return $this->delegate('create', $params);
    }

    public function store(array $params = []): Response
    {
        return $this->delegate('store', $params);
    }

    public function edit(array $params = []): Response
    {
        return $this->delegate('edit', $params);
    }

    public function update(array $params = []): Response
    {
        return $this->delegate('update', $params);
    }

    public function action(array $params = []): Response
    {
        return $this->delegate('action', $params);
    }

    private function delegate(string $method, array $params): Response
    {
        $slug   = $this->moduleFromPath();
        $class  = self::controllerFor($slug);

        /** @var ResourceController $controller */
        $controller = new $class();
        $controller->setRouter($this->router);
        $controller->setRequest($this->request);

        return $controller->{$method}($params);
    }

    /**
     * /admin/services → 'services'
     *
     * Dil öneği (/en/admin/...) olsa bile ikinci segment modül slug'ıdır;
     * bu yüzden 'admin' geçilene kadar aranır.
     */
    private function moduleFromPath(): string
    {
        $parts = array_values(array_filter(
            explode('/', trim($this->currentRoutePath(), '/')),
            'strlen'
        ));

        $adminAt = array_search('admin', $parts, true);
        if ($adminAt === false) {
            return '';
        }
        return $parts[$adminAt + 1] ?? '';
    }

    private function currentRoutePath(): string
    {
        $uri = $this->request->path();
        return $uri === '' ? '/' : $uri;
    }
}
