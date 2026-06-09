<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Учебная помощь',
                'slug' => 'uchebnaya-pomoshch',
                'icon' => 'book-open',
                'description' => 'Консультации, структура работ, оформление и аккуратная подготовка учебных материалов.',
                'children' => [
                    ['name' => 'Курсовые консультации', 'slug' => 'kursovye-konsultacii'],
                    ['name' => 'Оформление работ', 'slug' => 'oformlenie-rabot'],
                    ['name' => 'Расчеты и таблицы', 'slug' => 'raschety-i-tablicy'],
                    ['name' => 'Рефераты и доклады', 'slug' => 'referaty-i-doklady'],
                    ['name' => 'Дипломные консультации', 'slug' => 'diplomnye-konsultacii'],
                ],
            ],
            [
                'name' => 'Тексты и документы',
                'slug' => 'teksty-i-dokumenty',
                'icon' => 'file-text',
                'description' => 'Редактура, оформление, структурирование и подготовка деловых документов.',
                'children' => [
                    ['name' => 'Word и документы', 'slug' => 'word-i-dokumenty'],
                    ['name' => 'Excel и таблицы', 'slug' => 'excel-i-tablicy'],
                ],
            ],
            [
                'name' => 'Презентации',
                'slug' => 'prezentacii',
                'icon' => 'presentation',
                'description' => 'Логичные, чистые и современные презентации для учебы, бизнеса и выступлений.',
                'children' => [
                    ['name' => 'PowerPoint-презентации', 'slug' => 'powerpoint-prezentacii'],
                    ['name' => 'Инфографика', 'slug' => 'infografika'],
                ],
            ],
            [
                'name' => 'Дизайн',
                'slug' => 'dizayn',
                'icon' => 'palette',
                'description' => 'Визуальные материалы, обложки, простые макеты и презентационная графика.',
            ],
            [
                'name' => 'Сайты и разработка',
                'slug' => 'sayty-i-razrabotka',
                'icon' => 'code',
                'description' => 'Лендинги, доработки сайтов и простые технические задачи для малого бизнеса.',
                'children' => [
                    ['name' => 'Лендинги', 'slug' => 'lendingi'],
                    ['name' => 'Доработка сайтов', 'slug' => 'dorabotka-saytov'],
                ],
            ],
            [
                'name' => 'Маркетинг',
                'slug' => 'marketing',
                'icon' => 'megaphone',
                'description' => 'Упаковка предложений, базовая аналитика и подготовка материалов для продвижения.',
            ],
            [
                'name' => 'Бизнес-задачи',
                'slug' => 'biznes-zadachi',
                'icon' => 'briefcase',
                'description' => 'Документы, таблицы, описания процессов и операционные задачи для команд.',
            ],
        ];

        foreach ($categories as $index => $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    ...$categoryData,
                    'parent_id' => null,
                    'sort_order' => $index * 10,
                    'is_active' => true,
                ],
            );

            foreach ($children as $childIndex => $childData) {
                Category::updateOrCreate(
                    ['slug' => $childData['slug']],
                    [
                        ...$childData,
                        'parent_id' => $category->id,
                        'description' => $childData['description'] ?? "Услуги в разделе «{$childData['name']}».",
                        'icon' => $childData['icon'] ?? null,
                        'sort_order' => $childIndex * 10,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
