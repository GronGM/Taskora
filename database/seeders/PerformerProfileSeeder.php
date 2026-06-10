<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PerformerPortfolioItem;
use App\Models\PerformerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class PerformerProfileSeeder extends Seeder
{
    public function run(): void
    {
        $performer = User::query()->where('email', 'performer@taskora.local')->firstOrFail();
        $moderator = User::query()
            ->whereIn('role', [User::ROLE_MODERATOR, User::ROLE_ADMIN])
            ->orderByRaw("case when role = 'moderator' then 0 else 1 end")
            ->first();

        $profile = PerformerProfile::query()->updateOrCreate(
            ['user_id' => $performer->id],
            [
                'display_name' => 'Исполнитель Таскоры',
                'headline' => 'Документы, презентации и Excel-расчеты',
                'bio' => 'Помогаю аккуратно готовить документы, презентации и таблицы для учебных и рабочих задач. Структурирую материалы, привожу оформление к требованиям, собираю расчеты в Excel и объясняю результат понятным языком внутри Таскоры.',
                'experience_years' => 5,
                'response_time_label' => 'Отвечает в течение дня',
                'portfolio_summary' => 'В портфолио собраны примеры структуры документов, презентаций и расчетных таблиц без персональных данных заказчиков.',
                'verification_status' => PerformerProfile::STATUS_VERIFIED,
                'verification_note' => null,
                'verified_at' => now(),
                'verified_by' => $moderator?->id,
                'submitted_for_verification_at' => now()->subDay(),
                'published_at' => now(),
                'is_public' => true,
            ],
        );

        $categories = Category::query()
            ->whereIn('slug', ['uchebnaya-pomoshch', 'prezentacii', 'teksty-i-dokumenty'])
            ->pluck('id', 'slug');

        $profile->specializations()->sync($categories->values()->all());

        $items = [
            [
                'title' => 'Структура учебного доклада',
                'description' => 'Пример логики доклада: план, тезисы, оформление списка источников и аккуратная подача материала без контактов и персональных данных.',
                'category_id' => $categories['uchebnaya-pomoshch'] ?? null,
                'sort_order' => 10,
            ],
            [
                'title' => 'Презентация для защиты проекта',
                'description' => 'Сценарий презентации с понятной структурой, титульным слайдом, блоком выводов и визуально спокойными таблицами.',
                'category_id' => $categories['prezentacii'] ?? null,
                'sort_order' => 20,
            ],
            [
                'title' => 'Excel-таблица с расчетами',
                'description' => 'Пример расчетной таблицы с разделением исходных данных, формул и итогового блока для проверки результата.',
                'category_id' => $categories['teksty-i-dokumenty'] ?? null,
                'sort_order' => 30,
            ],
        ];

        foreach ($items as $item) {
            PerformerPortfolioItem::query()->updateOrCreate(
                [
                    'performer_profile_id' => $profile->id,
                    'title' => $item['title'],
                ],
                [
                    ...$item,
                    'image_path' => null,
                    'file_path' => null,
                    'external_url' => null,
                    'status' => PerformerPortfolioItem::STATUS_PUBLISHED,
                    'is_public' => true,
                ],
            );
        }
    }
}
