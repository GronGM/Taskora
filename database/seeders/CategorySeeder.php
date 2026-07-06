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
                    ['name' => 'Контрольные и задачи', 'slug' => 'kontrolnye-i-zadachi'],
                    ['name' => 'Отчеты по практике', 'slug' => 'otchety-po-praktike'],
                    ['name' => 'Эссе и сочинения', 'slug' => 'esse-i-sochineniya'],
                    ['name' => 'Чертежи', 'slug' => 'chertezhi'],
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
                'children' => [
                    ['name' => 'Логотипы и фирменный стиль', 'slug' => 'logotipy-i-firmennyy-stil'],
                    ['name' => 'Баннеры и соцсети', 'slug' => 'bannery-i-socseti'],
                    ['name' => 'Полиграфия', 'slug' => 'poligrafiya'],
                    ['name' => 'Обработка фото', 'slug' => 'obrabotka-foto'],
                ],
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
                'children' => [
                    ['name' => 'SEO и трафик', 'slug' => 'seo-i-trafik'],
                    ['name' => 'Контекстная реклама', 'slug' => 'kontekstnaya-reklama'],
                    ['name' => 'SMM и соцсети', 'slug' => 'smm-i-socseti'],
                    ['name' => 'Маркетплейсы', 'slug' => 'marketpleysy'],
                ],
            ],
            [
                'name' => 'Бизнес-задачи',
                'slug' => 'biznes-zadachi',
                'icon' => 'briefcase',
                'description' => 'Документы, таблицы, описания процессов и операционные задачи для команд.',
                'children' => [
                    ['name' => 'Таблицы и расчеты', 'slug' => 'tablicy-i-raschety'],
                    ['name' => 'Документы и договоры', 'slug' => 'dokumenty-i-dogovory'],
                    ['name' => 'Финансовые модели', 'slug' => 'finansovye-modeli'],
                    ['name' => 'Ассистент и поручения', 'slug' => 'assistent-i-porucheniya'],
                ],
            ],
            [
                'name' => 'Тексты и переводы',
                'slug' => 'teksty-i-perevody',
                'icon' => 'languages',
                'description' => 'Копирайтинг, редактура, переводы и подготовка продающих текстов.',
                'children' => [
                    ['name' => 'Копирайтинг', 'slug' => 'kopirayting'],
                    ['name' => 'Редактура и корректура', 'slug' => 'redaktura-i-korrektura'],
                    ['name' => 'Переводы', 'slug' => 'perevody'],
                    ['name' => 'Резюме и сопроводительные', 'slug' => 'rezyume-i-soprovoditelnye'],
                ],
            ],
            [
                'name' => 'Аудио и видео',
                'slug' => 'audio-i-video',
                'icon' => 'video',
                'description' => 'Монтаж роликов, обработка звука, озвучка и субтитры.',
                'children' => [
                    ['name' => 'Монтаж видео', 'slug' => 'montazh-video'],
                    ['name' => 'Обработка аудио', 'slug' => 'obrabotka-audio'],
                    ['name' => 'Озвучка', 'slug' => 'ozvuchka'],
                    ['name' => 'Субтитры', 'slug' => 'subtitry'],
                ],
            ],
            [
                'name' => 'Данные и аналитика',
                'slug' => 'dannye-i-analitika',
                'icon' => 'chart-column',
                'description' => 'Сбор данных, таблицы, отчеты, дашборды и аналитические выводы.',
                'children' => [
                    ['name' => 'Парсинг и сбор данных', 'slug' => 'parsing-i-sbor-dannyh'],
                    ['name' => 'Отчеты и аналитика', 'slug' => 'otchety-i-analitika'],
                    ['name' => 'Дашборды', 'slug' => 'dashbordy'],
                ],
            ],
            [
                'name' => 'Репетиторство и обучение',
                'slug' => 'repetitorstvo-i-obuchenie',
                'icon' => 'graduation-cap',
                'description' => 'Разбор тем, подготовка к экзаменам и индивидуальные консультации.',
                'children' => [
                    ['name' => 'Разбор темы', 'slug' => 'razbor-temy'],
                    ['name' => 'Подготовка к экзамену', 'slug' => 'podgotovka-k-ekzamenu'],
                ],
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
