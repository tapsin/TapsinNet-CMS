<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\Profile;

final class ProfileController extends ResourceController
{
    protected string $model = Profile::class;
    protected string $moduleSlug = 'profiles';
    protected string $defaultSort = 'sort_order';
    protected string $defaultDir = 'ASC';
    protected string $singular = 'profile';
    protected string $plural = 'profiles';

    protected array $columns = [
        'avatar'        => ['type' => 'avatar', 'label' => 'admin.col.avatar'],
        'name'          => ['type' => 'title',  'label' => 'admin.col.name'],
        'title_tr'      => ['type' => 'text',   'label' => 'admin.col.role'],
        'company'       => ['type' => 'text',   'label' => 'admin.col.company'],
        'profile_type'  => ['type' => 'text',   'label' => 'admin.col.type'],
        'is_active'     => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected function sortableColumns(): array
    {
        return ['name', 'company', 'profile_type', 'sort_order', 'created_at'];
    }

    protected function filters(): array
    {
        return ['type' => ['profile_type', '=']];
    }

    protected function filterOptions(): array
    {
        $counts = Profile::typeCounts();
        $out = [];
        foreach (Profile::TYPES as $type) {
            $out[] = ['key' => $type, 'label' => Profile::typeLabel($type), 'count' => $counts[$type] ?? 0];
        }
        return ['type' => $out];
    }

    protected array $form = [
        'name'         => ['type' => 'text', 'label' => 'Ad Soyad / Marka', 'rules' => 'required|string|max:160', 'col' => 6],
        'slug'         => ['type' => 'slug', 'label' => 'URL slug', 'hint' => 'admin.hint.slug', 'col' => 6],
        'title_tr'     => ['type' => 'text', 'label' => 'Ünvan / rol (TR)', 'col' => 6],
        'title_en'     => ['type' => 'text', 'label' => 'Role (EN)', 'col' => 6],
        'company'      => ['type' => 'text', 'label' => 'Şirket / kurum', 'col' => 6],
        'profile_type' => ['type' => 'select', 'label' => 'Profil tipi', 'rules' => 'required', 'col' => 6,
                           'options' => ['team' => 'Ekip', 'client' => 'Müşteri', 'partner' => 'İş ortağı', 'other' => 'Diğer']],
        'avatar'       => ['type' => 'image', 'label' => 'Profil fotoğrafı', 'col' => 6],
        'location'     => ['type' => 'text', 'label' => 'Konum', 'col' => 6],
        'email'        => ['type' => 'email', 'label' => 'E-posta', 'rules' => 'nullable|email|max:190', 'col' => 6],
        'phone'        => ['type' => 'tel',   'label' => 'Telefon', 'rules' => 'nullable|max:40', 'col' => 6],
        'website'      => ['type' => 'url',   'label' => 'Web sitesi', 'rules' => 'nullable|url|max:300', 'col' => 6],
        'bio_tr'       => ['type' => 'textarea', 'label' => 'Biyografi (TR)', 'col' => 6],
        'bio_en'       => ['type' => 'textarea', 'label' => 'Bio (EN)', 'col' => 6],
        'linkedin'     => ['type' => 'text', 'label' => 'LinkedIn kullanıcı adı', 'col' => 3],
        'github'       => ['type' => 'text', 'label' => 'GitHub kullanıcı adı', 'col' => 3],
        'instagram'    => ['type' => 'text', 'label' => 'Instagram kullanıcı adı', 'col' => 3],
        'twitter'      => ['type' => 'text', 'label' => 'X kullanıcı adı', 'col' => 3],
        'is_featured'  => ['type' => 'checkbox', 'label' => 'Ana sayfada göster', 'col' => 4],
        'is_active'    => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order'   => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];
}
