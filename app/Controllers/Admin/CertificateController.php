<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Core\Database;
use Models\Certificate;

final class CertificateController extends ResourceController
{
    protected string $model = Certificate::class;
    protected string $moduleSlug = 'certificates';
    protected string $defaultSort = 'sort_order';
    protected string $defaultDir = 'ASC';
    protected string $singular = 'certificate';
    protected string $plural = 'certificates';

    protected array $columns = [
        'cover_image' => ['type' => 'image', 'label' => 'admin.col.image', 'width' => 48],
        'title_tr'    => ['type' => 'title', 'label' => 'admin.col.title'],
        'issuer'      => ['type' => 'text',  'label' => 'admin.col.issuer'],
        'issue_date'  => ['type' => 'date',  'label' => 'admin.col.issue_date'],
        'is_active'   => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected function sortableColumns(): array
    {
        return ['title_tr', 'issuer', 'issue_date', 'expiry_date', 'sort_order'];
    }

    protected function stats(): array
    {
        $s = parent::stats();
        $s['expiring'] = (int) Database::value(
            'SELECT COUNT(*) FROM certificates WHERE deleted_at IS NULL
             AND expiry_date IS NOT NULL AND expiry_date <> "" AND expiry_date <= :d',
            ['d' => date('Y-m-d', strtotime('+60 days'))], 0
        );
        return $s;
    }

    protected array $form = [
        'title_tr'      => ['type' => 'text', 'label' => 'Belge adı (TR)', 'rules' => 'required|string|max:200', 'col' => 6],
        'title_en'      => ['type' => 'text', 'label' => 'Certificate title (EN)', 'rules' => 'nullable|string|max:200', 'col' => 6],
        'slug'          => ['type' => 'slug', 'label' => 'URL slug', 'hint' => 'admin.hint.slug', 'col' => 4],
        'issuer'        => ['type' => 'text', 'label' => 'Veren kurum (kısa)', 'col' => 4],
        'credential_id' => ['type' => 'text', 'label' => 'Belge numarası', 'col' => 4],
        'issue_date'    => ['type' => 'date', 'label' => 'Alım tarihi', 'col' => 4],
        'expiry_date'   => ['type' => 'date', 'label' => 'Geçerlilik bitiş', 'hint' => 'admin.hint.expiry', 'col' => 4],
        'score'         => ['type' => 'text', 'label' => 'Puan / derece', 'col' => 4],
        'issuer_tr'     => ['type' => 'text', 'label' => 'Veren kurum (tam, TR)', 'col' => 6],
        'issuer_en'     => ['type' => 'text', 'label' => 'Issuer (EN)', 'col' => 6],
        'cover_image'   => ['type' => 'image', 'label' => 'Belge görseli', 'col' => 6],
        'file_path'     => ['type' => 'file',  'label' => 'PDF dosyası', 'hint' => 'admin.hint.pdf', 'col' => 6],
        'url'           => ['type' => 'url',   'label' => 'Doğrulama bağlantısı', 'rules' => 'nullable|url|max:400', 'col' => 6],
        'description_tr'=> ['type' => 'textarea', 'label' => 'Açıklama (TR)', 'col' => 6],
        'description_en'=> ['type' => 'textarea', 'label' => 'Description (EN)', 'col' => 6],
        'is_featured'   => ['type' => 'checkbox', 'label' => 'Ana sayfada göster', 'col' => 4],
        'is_active'     => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order'    => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];
}
