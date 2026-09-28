<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Core\Database;
use Models\Project;
use Core\Uploader;

final class ProjectController extends ResourceController
{
    protected string $model = Project::class;
    protected string $moduleSlug = 'projects';
    protected string $defaultSort = 'created_at';
    protected string $defaultDir = 'DESC';
    protected string $singular = 'project';
    protected string $plural = 'projects';

    protected array $columns = [
        'cover_image'  => ['type' => 'image', 'label' => 'admin.col.image', 'width' => 56],
        'title_tr'     => ['type' => 'title', 'label' => 'admin.col.title'],
        'client'       => ['type' => 'text',  'label' => 'admin.col.client'],
        'category'     => ['type' => 'text',  'label' => 'admin.col.category'],
        'views'        => ['type' => 'number', 'label' => 'admin.col.views'],
        'is_active'    => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected function sortableColumns(): array
    {
        return ['title_tr', 'client', 'category', 'views', 'created_at', 'sort_order'];
    }

    protected function filters(): array
    {
        return ['kategori' => ['category', '=']];
    }

    protected function filterOptions(): array
    {
        return ['kategori' => Project::categoriesWithLabels()];
    }

    protected array $form = [
        'title_tr'     => ['type' => 'text', 'label' => 'Proje adı (TR)', 'rules' => 'required|string|max:200', 'col' => 6],
        'title_en'     => ['type' => 'text', 'label' => 'Project title (EN)', 'rules' => 'nullable|string|max:200', 'col' => 6],
        'slug'         => ['type' => 'slug', 'label' => 'URL slug', 'hint' => 'admin.hint.slug', 'col' => 4],
        'client'       => ['type' => 'text', 'label' => 'Müşteri / marka', 'col' => 4],
        'category'     => ['type' => 'text', 'label' => 'Kategori', 'hint' => 'admin.hint.category', 'col' => 4],
        'summary_tr'   => ['type' => 'textarea', 'label' => 'Özet (TR)', 'rules' => 'nullable|string|max:400', 'col' => 6],
        'summary_en'   => ['type' => 'textarea', 'label' => 'Summary (EN)', 'rules' => 'nullable|string|max:400', 'col' => 6],
        'cover_image'  => ['type' => 'image', 'label' => 'Kapak görseli', 'col' => 6],
        'body_tr'      => ['type' => 'richtext', 'label' => 'Proje detayı (TR)', 'col' => 12],
        'body_en'      => ['type' => 'richtext', 'label' => 'Project details (EN)', 'col' => 12],
        'tags'         => ['type' => 'tags', 'label' => 'Etiketler', 'hint' => 'admin.hint.tags', 'col' => 6],
        'tech_stack'   => ['type' => 'tags', 'label' => 'Teknolojiler', 'hint' => 'admin.hint.tags', 'col' => 6],
        'location'     => ['type' => 'text', 'label' => 'Konum', 'col' => 4],
        'duration'     => ['type' => 'text', 'label' => 'Süre', 'col' => 4],
        'project_date' => ['type' => 'text', 'label' => 'Proje tarihi', 'hint' => 'YYYY-AA', 'col' => 4],
        'project_url'  => ['type' => 'url', 'label' => 'Proje bağlantısı', 'rules' => 'nullable|url|max:300', 'col' => 6],
        'client_url'   => ['type' => 'url', 'label' => 'Müşteri bağlantısı', 'rules' => 'nullable|url|max:300', 'col' => 6],
        'gallery'      => ['type' => 'hidden', 'label' => 'Galeri görselleri (çoklu yükleme)'],
        'meta_title_tr'=> ['type' => 'text', 'label' => 'SEO başlık (TR)', 'col' => 6],
        'meta_title_en'=> ['type' => 'text', 'label' => 'SEO title (EN)', 'col' => 6],
        'meta_desc_tr' => ['type' => 'textarea', 'label' => 'SEO açıklama (TR)', 'col' => 6],
        'meta_desc_en' => ['type' => 'textarea', 'label' => 'Meta description (EN)', 'col' => 6],
        'is_featured'  => ['type' => 'checkbox', 'label' => 'Öne çıkan iş', 'col' => 4],
        'is_active'    => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order'   => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];

    /** Proje galerisi — çoklu yükleme, JSON olarak saklanır. */
    protected function afterSave(int $id, array $data, bool $isNew): void
    {
        if (!$this->request->isPost()) {
            return;
        }

        $uploader = new Uploader();
        $paths = Project::gallery($isNew ? ['gallery' => null] : (Project::findAny($id) ?? []));

        // Mevcut galeri metnini koru
        $raw = (string) $this->request->input('gallery', '');
        $paths = $raw === '' ? [] : array_values(array_filter(
            array_map('strval', (array) json_decode($raw, true))
        ));

        $single = $uploader->image('gallery_one', 'uploads/projects', $this->request);
        if ($single !== null) {
            array_unshift($paths, $single);
        }

        if ($paths !== []) {
            Project::saveGallery($id, array_values(array_unique($paths)));
        }
    }

    protected function beforeDelete(array $row): void
    {
        Uploader::delete($row['cover_image'] ?? null);
        foreach (Project::gallery($row) as $path) {
            Uploader::delete($path);
        }
    }
}
