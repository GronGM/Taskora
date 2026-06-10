<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@taskora.local')->firstOrFail();
        $performer = User::where('email', 'performer@taskora.local')->firstOrFail();

        $tasks = [
            [
                'category' => 'word-i-dokumenty',
                'title' => 'Нужно оформить документ Word по методичке',
                'slug' => 'nuzhno-oformit-dokument-word-po-metodichke',
                'description' => 'Есть готовый текст и методичка. Нужно привести поля, заголовки, списки, таблицы и оглавление к единым требованиям. Результат нужен в аккуратном формате Word.',
                'budget_min' => 1800,
                'budget_max' => 3000,
                'deadline_at' => now()->addDays(5)->toDateString(),
            ],
            [
                'category' => 'powerpoint-prezentacii',
                'title' => 'Сделать презентацию на 10 слайдов',
                'slug' => 'sdelat-prezentaciyu-na-10-slaydov',
                'description' => 'Нужно собрать презентацию по готовому плану: титульный слайд, структура, основные тезисы, диаграмма и финальный вывод. Важны чистая сетка и единый стиль.',
                'budget_min' => 3000,
                'budget_max' => 5000,
                'deadline_at' => now()->addDays(7)->toDateString(),
            ],
            [
                'category' => 'excel-i-tablicy',
                'title' => 'Подготовить Excel-таблицу с расчетами',
                'slug' => 'podgotovit-excel-tablicu-s-raschetami',
                'description' => 'Есть исходные данные и пример расчета. Нужно оформить таблицу, добавить формулы, итоговые показатели и понятные подписи для проверки.',
                'budget_min' => 2500,
                'budget_max' => 4500,
                'deadline_at' => now()->addDays(6)->toDateString(),
            ],
            [
                'category' => 'kursovye-konsultacii',
                'title' => 'Нужна консультация по структуре курсовой',
                'slug' => 'nuzhna-konsultaciya-po-strukture-kursovoy',
                'description' => 'Нужно помочь выстроить структуру курсовой: главы, параграфы, задачи, логика переходов и список материалов для дальнейшей самостоятельной работы.',
                'budget_min' => 1500,
                'budget_max' => 2500,
                'deadline_at' => now()->addDays(4)->toDateString(),
            ],
            [
                'category' => 'lendingi',
                'title' => 'Сверстать простой лендинг для услуги',
                'slug' => 'sverstat-prostoy-lending-dlya-uslugi',
                'description' => 'Нужен адаптивный одностраничный лендинг для описания услуги: первый экран, преимущества, процесс работы, доверительный блок и форма-заглушка.',
                'budget_min' => 9000,
                'budget_max' => 15000,
                'deadline_at' => now()->addDays(12)->toDateString(),
            ],
        ];

        $createdTasks = collect();

        foreach ($tasks as $taskData) {
            $category = Category::where('slug', $taskData['category'])->firstOrFail();
            unset($taskData['category']);

            $createdTasks->push(Task::updateOrCreate(
                ['slug' => $taskData['slug']],
                [
                    ...$taskData,
                    'user_id' => $customer->id,
                    'category_id' => $category->id,
                    'status' => Task::STATUS_PUBLISHED,
                ],
            ));
        }

        $this->syncOffer($createdTasks[0], $performer, [
            'message' => 'Готов оформить документ по методичке: сначала проверю требования, затем приведу стили, поля, оглавление и таблицы к единому виду.',
            'price' => 2400,
            'delivery_days' => 3,
        ]);

        $this->syncOffer($createdTasks[1], $performer, [
            'message' => 'Соберу презентацию в чистом стиле, выровняю структуру и подготовлю слайды для защиты или выступления.',
            'price' => 4200,
            'delivery_days' => 5,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncOffer(Task $task, User $performer, array $payload): void
    {
        TaskOffer::updateOrCreate(
            [
                'task_id' => $task->id,
                'user_id' => $performer->id,
            ],
            [
                ...$payload,
                'status' => TaskOffer::STATUS_SUBMITTED,
            ],
        );

        $task->update([
            'offers_count' => $task->offers()->submitted()->count(),
        ]);
    }
}
