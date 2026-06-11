<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@taskora.local')->firstOrFail();
        $performer = User::where('email', 'performer@taskora.local')->firstOrFail();

        $tasks = [
            ['type' => 'Word-документ', 'title' => 'Оформить Word-документ по требованиям', 'budget_min' => 1800, 'budget_max' => 3000, 'days' => 5],
            ['type' => 'PowerPoint-презентация', 'title' => 'Подготовить презентацию на 10 слайдов', 'budget_min' => 3000, 'budget_max' => 5000, 'days' => 7],
            ['type' => 'Excel-таблица', 'title' => 'Собрать Excel-таблицу с расчетами', 'budget_min' => 2500, 'budget_max' => 4500, 'days' => 6],
            ['type' => 'Структура курсовой', 'title' => 'Проверить структуру курсовой и дать рекомендации', 'budget_min' => 1500, 'budget_max' => 2500, 'days' => 4],
            ['type' => 'Инфографика', 'title' => 'Сделать инфографику для доклада', 'budget_min' => 3500, 'budget_max' => 6500, 'days' => 8],
            ['type' => 'Описание товара', 'title' => 'Написать описание товара для маркетплейса', 'budget_min' => 1200, 'budget_max' => 2400, 'days' => 3],
            ['type' => 'Исправление ошибки', 'title' => 'Исправить ошибку в форме на лендинге', 'budget_min' => 2500, 'budget_max' => 6000, 'days' => 2],
            ['type' => 'Коммерческое предложение', 'title' => 'Подготовить коммерческое предложение для клиента', 'budget_min' => 4000, 'budget_max' => 8000, 'days' => 6],
            ['type' => 'Верстка блока', 'title' => 'Сверстать блок сайта по макету', 'budget_min' => 5000, 'budget_max' => 9000, 'days' => 5],
            ['type' => 'Контент-план', 'title' => 'Подготовить контент-план на две недели', 'budget_min' => 3000, 'budget_max' => 7000, 'days' => 9],
            ['type' => 'Консультация по работе', 'title' => 'Провести консультацию по плану учебной работы', 'budget_min' => 1000, 'budget_max' => 2200, 'days' => 2],
            ['type' => 'Оформление по требованиям', 'title' => 'Привести список источников к требованиям', 'budget_min' => 1500, 'budget_max' => 2800, 'days' => 4],
            ['type' => 'Проверка и правки', 'title' => 'Проверить текст и предложить правки', 'budget_min' => 2000, 'budget_max' => 4500, 'days' => 5],
            ['type' => 'Расчеты и таблицы', 'title' => 'Оформить расчеты и пояснения в таблице', 'budget_min' => 2800, 'budget_max' => 5200, 'days' => 6],
            ['type' => 'Подготовка к защите', 'title' => 'Подготовить тезисы для защиты проекта', 'budget_min' => 2500, 'budget_max' => 4800, 'days' => 5],
            ['type' => 'Редактирование текста', 'title' => 'Отредактировать текст инструкции', 'budget_min' => 1800, 'budget_max' => 3500, 'days' => 4],
            ['type' => 'Проверка орфографии', 'title' => 'Проверить орфографию и пунктуацию в документе', 'budget_min' => 900, 'budget_max' => 1800, 'days' => 2],
            ['type' => 'Резюме и деловые документы', 'title' => 'Обновить резюме и сопроводительное письмо', 'budget_min' => 2500, 'budget_max' => 5000, 'days' => 5],
            ['type' => 'Справка/инструкция', 'title' => 'Собрать понятную инструкцию для сотрудников', 'budget_min' => 3500, 'budget_max' => 7000, 'days' => 8],
            ['type' => 'Перевод текста', 'title' => 'Перевести короткий текст и адаптировать стиль', 'budget_min' => 1600, 'budget_max' => 3200, 'days' => 3],
            ['type' => 'Презентация для защиты', 'title' => 'Собрать презентацию для защиты проекта', 'budget_min' => 4500, 'budget_max' => 8500, 'days' => 7],
            ['type' => 'Дизайн слайдов', 'title' => 'Обновить дизайн слайдов в готовой презентации', 'budget_min' => 3000, 'budget_max' => 6500, 'days' => 5],
            ['type' => 'Речь к презентации', 'title' => 'Подготовить речь к презентации', 'budget_min' => 2200, 'budget_max' => 4200, 'days' => 4],
            ['type' => 'Баннер', 'title' => 'Сделать баннер для акции', 'budget_min' => 2500, 'budget_max' => 5000, 'days' => 3],
            ['type' => 'Обложка', 'title' => 'Подготовить обложку для PDF-материала', 'budget_min' => 2000, 'budget_max' => 4200, 'days' => 4],
            ['type' => 'Карточка товара', 'title' => 'Оформить карточку товара с преимуществами', 'budget_min' => 3000, 'budget_max' => 6000, 'days' => 5],
            ['type' => 'Логотип', 'title' => 'Нарисовать простой логотип для проекта', 'budget_min' => 5000, 'budget_max' => 12000, 'days' => 10],
            ['type' => 'Макет страницы', 'title' => 'Подготовить макет страницы услуги', 'budget_min' => 7000, 'budget_max' => 14000, 'days' => 9],
            ['type' => 'Лендинг', 'title' => 'Собрать структуру лендинга для услуги', 'budget_min' => 9000, 'budget_max' => 15000, 'days' => 12],
            ['type' => 'Доработка сайта', 'title' => 'Доработать блок преимуществ на сайте', 'budget_min' => 4500, 'budget_max' => 9000, 'days' => 6],
            ['type' => 'Форма/калькулятор', 'title' => 'Настроить форму расчета стоимости', 'budget_min' => 6000, 'budget_max' => 12000, 'days' => 8],
            ['type' => 'Настройка интеграции', 'title' => 'Проверить и настроить простую интеграцию формы', 'budget_min' => 5000, 'budget_max' => 10000, 'days' => 7],
            ['type' => 'Текст объявления', 'title' => 'Написать текст объявления для услуги', 'budget_min' => 1000, 'budget_max' => 2500, 'days' => 2],
            ['type' => 'SEO-текст', 'title' => 'Подготовить SEO-текст для страницы', 'budget_min' => 3500, 'budget_max' => 7500, 'days' => 7],
            ['type' => 'Анализ конкурентов', 'title' => 'Собрать краткий анализ конкурентов', 'budget_min' => 5000, 'budget_max' => 11000, 'days' => 10],
            ['type' => 'Финансовый расчет', 'title' => 'Проверить финансовый расчет и оформить выводы', 'budget_min' => 4500, 'budget_max' => 9500, 'days' => 7],
            ['type' => 'Аналитическая таблица', 'title' => 'Собрать аналитическую таблицу по исходным данным', 'budget_min' => 4000, 'budget_max' => 8500, 'days' => 8],
            ['type' => 'Документ для клиента', 'title' => 'Подготовить документ для клиента по шаблону', 'budget_min' => 3000, 'budget_max' => 6500, 'days' => 6],
        ];

        $createdTasks = collect();

        foreach ($tasks as $index => $taskData) {
            $taskType = TaskType::where('name', $taskData['type'])->firstOrFail();
            $title = $taskData['title'];

            $createdTasks->push(Task::updateOrCreate(
                ['slug' => str($title)->slug('-', 'ru')->toString()],
                [
                    'user_id' => $customer->id,
                    'category_id' => $taskType->category_id,
                    'task_type_id' => $taskType->id,
                    'title' => $title,
                    'description' => $this->description($title, $taskType->name),
                    'budget_min' => $taskData['budget_min'],
                    'budget_max' => $taskData['budget_max'],
                    'deadline_at' => now()->addDays($taskData['days'])->toDateString(),
                    'status' => Task::STATUS_PUBLISHED,
                    'views_count' => 20 + $index * 3,
                ],
            ));
        }

        foreach ($createdTasks->take(12) as $index => $task) {
            if ($index % 3 === 2) {
                continue;
            }

            $this->syncOffer($task, $performer, [
                'message' => 'Готов выполнить задачу внутри платформы: уточню требования, согласую формат результата и подготовлю аккуратную версию без передачи контактов.',
                'price' => max(1000, (int) round(($task->budget_min + $task->budget_max) / 2)),
                'delivery_days' => max(1, (int) floor(now()->diffInDays($task->deadline_at, false) - 1)),
            ]);
        }
    }

    private function description(string $title, string $taskType): string
    {
        return "Нужно: {$title}. Формат задания: {$taskType}. Есть исходные материалы и ориентиры по результату. Важно сохранить аккуратную структуру, понятные подписи и выполнить работу внутри Таскоры без обмена внешними контактами.";
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
