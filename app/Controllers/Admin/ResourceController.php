<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Csrf;
use Core\HttpException;
use Core\Database;
use Core\Model;
use Core\ModuleRegistry;
use Core\Paginator;
use Core\Response;
use Core\Sanitizer;
use Core\Security;
use Core\Session;
use Core\Str;
use Core\Uploader;
use Models\{Project, Service, News, Page, GalleryItem, Video, Settings, ContentModel};
use Core\Translator;

/**
 * JENERİK İÇERİK YÖNETİCİSİ
 *
 * Tüm içerik modüllerinin CRUD'u bu sınıftan gelir. Alt sınıflar yalnızca
 * $model, $module ve $form alan tanımlarını verir. Böylece:
 *   · her modülde aynı davranış (sayfalama, arama, aktif/pasif, silme, sıra)
 *   · yeni modül eklemek = 15 satırlık alt sınıf + config/modules.php girdisi
 *   · tek bir yerde güvenlik kontrolü
 */
abstract class ResourceController extends Controller
{
    /** @var class-string<ContentModel> */
    protected string $model;

    /** Modül slug'ı — config/modules.php anahtarı. */
    protected string $moduleSlug = '';

    /** Görünüm öneki — admin/{module}/index.php ve form.php. */
    protected string $viewPrefix = 'admin';

    /** Liste sayfasında gösterilecek sütunlar. */
    protected array $columns = [];

    /** Varsayılan sıralama sütunu. */
    protected string $defaultSort = 'created_at';

    protected string $defaultDir = 'DESC';

    /** Form alanları. */
    protected array $form = [];

    /** Ekleme/düzenleme başlığı çeviri anahtarı. */
    protected string $singular = 'item';

    protected string $plural = 'items';

    /** Liste URL'si. */
    public function indexRoute(): string
    {
        return '/admin/' . $this->moduleSlug;
    }

    /**
     * Modül slug'ı → somut controller sınıfı.
     * Yeni bir içerik modülü eklemek = config/modules.php girdisi + bu satır.
     */
    public const CONTROLLERS = [
        'services'     => ServiceController::class,
        'projects'     => ProjectController::class,
        'certificates' => CertificateController::class,
        'gallery'      => GalleryController::class,
        'videos'       => VideoController::class,
        'news'         => NewsController::class,
        'profiles'     => ProfileController::class,
        'testimonials' => TestimonialController::class,
        'faq'          => FaqController::class,
        'social'       => SocialController::class,
        'pages'        => PageController::class,
    ];

    public static function controllerFor(string $module): string
    {
        $class = self::CONTROLLERS[$module] ?? null;
        if ($class === null) {
            throw new HttpException(404, 'Bilinmeyen modül: ' . $module);
        }
        return $class;
    }

    // =========================================================================
    //  LİSTE
    // =========================================================================

    public function index(array $params = []): Response
    {
        $model = $this->model;
        $term  = $this->term();
        $where = [];

        // Durum filtresi
        $filter = (string) $this->request->query('durum', '');
        if ($filter === 'aktif' || $filter === 'pasif') {
            $where['is_active'] = $filter === 'aktif' ? 1 : 0;
        }

        // Modüle özel filtre
        foreach ($this->filters() as $queryKey => [$column, $operator]) {
            $value = (string) $this->request->query($queryKey, '');
            if ($value !== '') {
                if ($operator === 'like') {
                    $where[$column] = $value;
                } else {
                    $where[$column] = $value;
                }
            }
        }

        $search = [];
        if ($term !== '') {
            $search = $model::searchFor($term);
        }

        $order = $this->sortFromRequest($this->sortableColumns(), $this->defaultSort, $this->defaultDir);

        $query = $this->currentQuery();
        unset($query['q']);

        $paginator = $model::paginate(
            $where,
            $search,
            $order,
            $this->page(),
            (int) \Core\Config::get('app.admin_per_page', 20),
            $query + ['q' => $term !== '' ? $term : null]
        );

        return $this->view($this->moduleSlug . '.index', [
            'paginator'  => $paginator,
            'rows'       => $paginator->items,
            'module'     => $this->moduleSlug,
            'moduleName' => ModuleRegistry::name($this->moduleSlug),
            'modelClass' => $this->model,
            'term'       => $term,
            'filter'     => $filter,
            'sortCol'    => array_key_first($order) ?? $this->defaultSort,
            'sortDir'    => $order[array_key_first($order) ?? $this->defaultSort] ?? $this->defaultDir,
            'filters'    => $this->filterOptions(),
            'columns'    => $this->columns,
            'stats'      => $this->stats(),
            'singular'   => $this->singular,
            'plural'     => $this->plural,
        ], 'layouts.admin');
    }

    // =========================================================================
    //  EKLEME
    // =========================================================================

    public function create(array $params = []): Response
    {
        return $this->view($this->moduleSlug . '.form', [
            'module'     => $this->moduleSlug,
            'moduleName' => ModuleRegistry::name($this->moduleSlug),
            'row'        => $this->blankRow(),
            'form'       => $this->form,
            'action'     => url($this->indexRoute() . '/ekle'),
            'method'     => 'POST',
            'title'      => t('admin.create', ['item' => strtolower(ModuleRegistry::name($this->moduleSlug))]),
            'options'    => $this->filterOptions(),
            'singular'   => $this->singular,
        ], 'layouts.admin');
    }

    public function store(array $params = []): Response
    {
        Csrf::verifyOrFail($this->request);

        $data = $this->buildData();
        [$clean, $error] = $this->validateData($data);

        if ($error !== null) {
            return $this->withErrors($this->request->all(), ['genel' => $error]);
        }

        $clean = $this->transform($clean, null);

        $id = ($this->model)::save($clean, null);
        $this->afterSave($id, $clean, true);

        return $this->ok(t('admin.created', ['item' => strtolower(ModuleRegistry::name($this->moduleSlug))]));
    }

    // =========================================================================
    //  DÜZENLEME
    // =========================================================================

    public function edit(array $params = []): Response
    {
        $row = ($this->model)::findAny((int) $params['id']);
        if ($row === null) {
            $this->notFound();
        }

        return $this->view($this->moduleSlug . '.form', [
            'module'     => $this->moduleSlug,
            'moduleName' => ModuleRegistry::name($this->moduleSlug),
            'row'        => $row,
            'form'       => $this->form,
            'action'     => url($this->indexRoute() . '/duzenle/' . (int) $row['id']),
            'method'     => 'POST',
            'title'      => t('admin.edit', ['item' => strtolower(ModuleRegistry::name($this->moduleSlug))]),
            'options'    => $this->filterOptions(),
            'singular'   => $this->singular,
        ], 'layouts.admin');
    }

    public function update(array $params = []): Response
    {
        Csrf::verifyOrFail($this->request);

        $id  = (int) $params['id'];
        $row = ($this->model)::findAny($id);
        if ($row === null) {
            $this->notFound();
        }

        $data = $this->buildData($row);
        [$clean, $error] = $this->validateData($data, $row);

        if ($error !== null) {
            return $this->withErrors($this->request->all(), ['genel' => $error]);
        }

        $clean = $this->transform($clean, $row);
        ($this->model)::save($clean, $id);
        $this->afterSave($id, $clean, false);

        return $this->ok(t('admin.updated', ['item' => strtolower(ModuleRegistry::name($this->moduleSlug))]));
    }

    // =========================================================================
    //  EYLEMLER
    // =========================================================================

    public function action(array $params = []): Response
    {
        Csrf::verifyOrFail($this->request);

        $id      = (int) $params['id'];
        $op      = (string) $this->request->post('islem', '');
        $row     = ($this->model)::findAny($id);

        if ($row === null) {
            return $this->fail(t('admin.not_found'));
        }

        switch ($op) {
            case 'aktif':
            case 'pasif':
                ($this->model)::setActive($id, $op === 'aktif');
                return $this->ok($op === 'aktif'
                    ? t('admin.activated', ['item' => $row['title_tr'] ?? ('#' . $id)])
                    : t('admin.deactivated', ['item' => $row['title_tr'] ?? ('#' . $id)]));

            case 'ozellik':
                $next = !((int) ($row['is_featured'] ?? 0) === 1);
                ($this->model)::setFeatured($id, $next);
                return $this->ok(t($next ? 'admin.featured_on' : 'admin.featured_off', ['item' => $row['title_tr'] ?? ('#' . $id)]));

            case 'yukari':
            case 'asagi':
                $this->reorder($id, $op);
                return $this->back($this->indexRoute());

            case 'sil':
                if ($this->isProtected($row)) {
                    return $this->fail(t('admin.protected_record'));
                }
                $this->beforeDelete($row);
                ($this->model)::delete($id);
                $this->afterDelete($id, $row);
                return $this->ok(t('admin.deleted', ['item' => $row['title_tr'] ?? ('#' . $id)]));

            case 'sil-toplu':
                $ids = $this->request->arr('ids');
                $ids = array_values(array_filter(array_map('intval', $ids)));
                if ($ids !== []) {
                    // korumalı kayıtları ele
                    $safe = [];
                    foreach ($ids as $cid) {
                        $crow = ($this->model)::findAny($cid);
                        if ($crow !== null && !$this->isProtected($crow)) {
                            $this->beforeDelete($crow);
                            $safe[] = $cid;
                        }
                    }
                    if ($safe !== []) {
                        ($this->model)::deleteMany($safe);
                    }
                    return $this->ok(t('admin.deleted_bulk', ['n' => count($ids)]));
                }
                return $this->fail(t('admin.nothing_selected'));

            case 'yukle-sonrasi':
                return $this->back($this->indexRoute());
        }

        return $this->fail(t('admin.unknown_action'));
    }

    // =========================================================================
    //  YARDIMCILAR — alt sınıflar geçersiz kılayabilir
    // =========================================================================

    /** Liste sütunları. */
    protected function columns(): array
    {
        return $this->columns;
    }

    /** Sıralanabilir sütunlar. */
    protected function sortableColumns(): array
    {
        return array_keys($this->columns);
    }

    /**
     * Modüle özel sorgu filtreleri.
     * @return array<string,array{0:string,1:string}> sorguAnahtarı => [sütun, operatör]
     */
    protected function filters(): array
    {
        return [];
    }

    /**
     * Filtre açılır listeleri.
     * @return array<string,array<int,array{0:string,1:string}>>
     */
    protected function filterOptions(): array
    {
        return [];
    }

    /** Üst kısımdaki sayaçlar. */
    protected function stats(): array
    {
        $m = $this->model;
        $table = $m::table();
        try {
            $total  = (int) Database::value("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL", [], 0);
            $active = (int) Database::value("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND is_active = 1", [], 0);
            $new    = (int) Database::value("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND created_at >= :d",
                ['d' => date('Y-m-d H:i:s', strtotime('-7 days'))], 0);
            return ['total' => $total, 'active' => $active, 'passive' => $total - $active, 'week' => $new];
        } catch (\Throwable) {
            return ['total' => 0, 'active' => 0, 'passive' => 0, 'week' => 0];
        }
    }

    /** Formun varsayılan değerleri. */
    protected function blankRow(): array
    {
        $row = [];
        foreach ($this->form as $name => $field) {
            $row[$name] = match ($field['type'] ?? 'text') {
                'checkbox' => 0,
                default    => '',
            };
        }
        $row['is_active']   = 1;
        $row['is_featured'] = 0;
        return $row;
    }

    /**
     * İstek gövdesinden temiz veri toplar + dosya yüklemelerini işler.
     * Yalnızca form tanımındaki alanlar kabul edilir.
     */
    protected function buildData(?array $existing = null): array
    {
        $data = [];
        $uploader = new Uploader();

        foreach ($this->form as $name => $field) {
            $type = (string) ($field['type'] ?? 'text');

            switch ($type) {
                case 'checkbox':
                    $data[$name] = $this->request->bool($name) ? 1 : 0;
                    break;

                case 'richtext':
                    $data[$name] = Sanitizer::html(
                        (string) $this->request->input($name, ''),
                        (bool) ($field['images'] ?? true)
                    );
                    break;

                case 'number':
                    // Boş sayısal alan: anahtarı HİÇ gönderme. Böylece veritabanındaki
                    // NOT NULL DEFAULT değeri uygulanır (sort_order=0, rating=5 …).
                    $raw = trim((string) $this->request->input($name, ''));
                    if ($raw !== '') {
                        $data[$name] = (float) $raw;
                    }
                    break;

                case 'textarea':
                case 'text':
                case 'url':
                case 'email':
                case 'tel':
                case 'color':
                case 'date':
                case 'select':
                case 'slug':
                case 'tags':
                case 'time':
                    $data[$name] = trim((string) $this->request->input($name, ''));
                    break;

                case 'image':
                    $uploaded = $uploader->image($name, 'uploads/' . $this->moduleSlug, $this->request);
                    if ($uploaded !== null) {
                        $data[$name] = $uploaded;
                    } elseif ($this->request->bool('sil_' . $name) && $existing !== null) {
                        Uploader::delete($existing[$name] ?? null);
                        $data[$name] = null;
                    } elseif ($existing !== null) {
                        $data[$name] = $existing[$name] ?? null;
                    } else {
                        $data[$name] = null;
                    }
                    break;

                case 'file':
                    $uploaded = $uploader->document($name, 'uploads/' . $this->moduleSlug, $this->request);
                    if ($uploaded !== null) {
                        $data[$name] = $uploaded;
                    } elseif ($this->request->bool('sil_' . $name) && $existing !== null) {
                        Uploader::delete($existing[$name] ?? null);
                        $data[$name] = null;
                    } elseif ($existing !== null) {
                        $data[$name] = $existing[$name] ?? null;
                    } else {
                        $data[$name] = null;
                    }
                    break;

                case 'multitext':
                    $data[$name] = Sanitizer::textarea((string) $this->request->input($name, ''));
                    break;
            }
        }

        // Yükleme hataları varsa doğrulamaya göm
        $errors = $uploader->errors();
        if ($errors !== []) {
            Session::put('_upload_errors', $errors);
        }

        return $data;
    }

    /** @return array{0:array,1:?string} */
    protected function validateData(array $data, ?array $existing = null): array
    {
        $uploadErrors = Session::get('_upload_errors', []);
        Session::forget('_upload_errors');

        if (is_array($uploadErrors) && $uploadErrors !== []) {
            return [[], (string) reset($uploadErrors)];
        }

        $rules = [];
        $labels = [];
        foreach ($this->form as $name => $field) {
            if (isset($field['rules'])) {
                $rules[$name] = $field['rules'];
            }
            $labels[$name] = (string) ($field['label'] ?? $name);
        }

        $v = \Core\Validator::make($data, $rules, $labels);
        if ($v->fails()) {
            $errors = $v->flatErrors();
            return [[], (string) reset($errors)];
        }

        return [$data, null];
    }

    /** Kaydetmeden önce son dokunuşlar. */
    protected function transform(array $data, ?array $existing): array
    {
        return $data;
    }

    protected function afterSave(int $id, array $data, bool $isNew): void
    {
    }

    protected function beforeDelete(array $row): void
    {
    }

    protected function afterDelete(int $id, array $row): void
    {
    }

    /** Silinemeyecek kayıt (sistem sayfası gibi). */
    protected function isProtected(array $row): bool
    {
        return (int) ($row['is_system'] ?? 0) === 1;
    }

    /** Sıra değiştirme. */
    protected function reorder(int $id, string $direction): void
    {
        $m = $this->model;
        if (!($m::columnExists($m::table(), 'sort_order'))) {
            return;
        }

        $row = $m::findAny($id);
        if ($row === null) {
            return;
        }

        $current = (int) ($row['sort_order'] ?? 0);
        $delta   = $direction === 'yukari' ? 1 : -1;
        $target  = $current + $delta;

        // komşu kaydı bul ve yer değiştir
        $sibling = Database::first(
            "SELECT id FROM {$m::table()}
             WHERE sort_order = :s AND id <> :id AND deleted_at IS NULL
             ORDER BY id ASC LIMIT 1",
            ['s' => $target, 'id' => $id]
        );

        if ($sibling !== null) {
            Database::execute(
                "UPDATE {$m::table()} SET sort_order = :s, updated_at = :u WHERE id = :id",
                ['s' => $current, 'u' => now(), 'id' => (int) $sibling['id']]
            );
        }

        Database::execute(
            "UPDATE {$m::table()} SET sort_order = :s, updated_at = :u WHERE id = :id",
            ['s' => $target, 'u' => now(), 'id' => $id]
        );
    }
}
