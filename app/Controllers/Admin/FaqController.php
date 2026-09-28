<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Models\FaqItem;

final class FaqController extends ResourceController
{
    protected string $model = FaqItem::class;
    protected string $moduleSlug = 'faq';
    protected string $defaultSort = 'sort_order';
    protected string $defaultDir = 'ASC';
    protected string $singular = 'faq';
    protected string $plural = 'faq';

    protected array $columns = [
        'question_tr' => ['type' => 'title', 'label' => 'admin.col.question'],
        'category'    => ['type' => 'text',  'label' => 'admin.col.category'],
        'sort_order'  => ['type' => 'order', 'label' => 'admin.col.order'],
        'is_active'   => ['type' => 'toggle', 'label' => 'admin.col.status'],
    ];

    protected function sortableColumns(): array
    {
        return ['question_tr', 'category', 'sort_order', 'created_at'];
    }

    protected function filters(): array
    {
        return ['kategori' => ['category', '=']];
    }

    protected function filterOptions(): array
    {
        return ['kategori' => FaqItem::categories()];
    }

    protected array $form = [
        'question_tr' => ['type' => 'text', 'label' => 'Soru (TR)', 'rules' => 'required|string|max:300', 'col' => 12],
        'question_en' => ['type' => 'text', 'label' => 'Question (EN)', 'rules' => 'nullable|string|max:300', 'col' => 12],
        'slug'        => ['type' => 'slug', 'label' => 'URL slug', 'hint' => 'admin.hint.slug', 'col' => 6],
        'category'    => ['type' => 'text', 'label' => 'Kategori', 'col' => 6],
        'answer_tr'   => ['type' => 'richtext', 'label' => 'Cevap (TR)', 'rules' => 'required', 'col' => 12],
        'answer_en'   => ['type' => 'richtext', 'label' => 'Answer (EN)', 'col' => 12],
        'is_featured' => ['type' => 'checkbox', 'label' => 'Ana sayfada göster', 'col' => 4],
        'is_active'   => ['type' => 'checkbox', 'label' => 'Yayında', 'col' => 4, 'default' => 1],
        'sort_order'  => ['type' => 'number', 'label' => 'Sıra numarası', 'col' => 4],
    ];
}
