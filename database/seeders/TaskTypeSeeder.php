<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\TaskType;
use Illuminate\Database\Seeder;

class TaskTypeSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'uchebnaya-pomoshch' => [
                'Консультация по работе',
                'Оформление по требованиям',
                'Проверка и правки',
                'Расчеты и таблицы',
                'Структура курсовой',
                'Подготовка к защите',
            ],
            'teksty-i-dokumenty' => [
                'Word-документ',
                'Редактирование текста',
                'Проверка орфографии',
                'Резюме и деловые документы',
                'Справка/инструкция',
                'Перевод текста',
            ],
            'prezentacii' => [
                'PowerPoint-презентация',
                'Презентация для защиты',
                'Инфографика',
                'Дизайн слайдов',
                'Речь к презентации',
            ],
            'dizayn' => [
                'Баннер',
                'Обложка',
                'Карточка товара',
                'Логотип',
                'Макет страницы',
                'Инфографика',
            ],
            'sayty-i-razrabotka' => [
                'Лендинг',
                'Доработка сайта',
                'Верстка блока',
                'Исправление ошибки',
                'Форма/калькулятор',
                'Настройка интеграции',
            ],
            'marketing' => [
                'Текст объявления',
                'Контент-план',
                'SEO-текст',
                'Анализ конкурентов',
                'Описание товара',
            ],
            'biznes-zadachi' => [
                'Excel-таблица',
                'Финансовый расчет',
                'Коммерческое предложение',
                'Аналитическая таблица',
                'Документ для клиента',
            ],
        ];

        foreach ($groups as $categorySlug => $names) {
            $category = Category::where('slug', $categorySlug)->firstOrFail();

            foreach ($names as $index => $name) {
                $baseSlug = str($name)->slug('-', 'ru')->toString();
                $existing = TaskType::where('slug', $baseSlug)->first();
                $slug = $existing && $existing->category_id !== $category->id
                    ? "{$categorySlug}-{$baseSlug}"
                    : $baseSlug;

                TaskType::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'description' => "Задания формата «{$name}» в разделе «{$category->name}».",
                        'sort_order' => $index * 10,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
