<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $performer = User::where('email', 'performer@taskora.local')->firstOrFail();

        $services = [
            [
                'category' => 'word-i-dokumenty',
                'title' => 'Оформлю документ Word по требованиям',
                'slug' => 'oformlyu-dokument-word-po-trebovaniyam',
                'short_description' => 'Приведу документ к требованиям: поля, стили, заголовки, списки, нумерация и аккуратная структура.',
                'description' => 'Помогу подготовить документ Word к сдаче или публикации: настрою стили, титульные блоки, оглавление, таблицы, списки и единый визуальный порядок. Работаю с готовым текстом и понятным техническим заданием.',
                'price_from' => 1200,
                'delivery_days' => 2,
                'rating' => 4.90,
                'reviews_count' => 18,
                'orders_count' => 32,
                'is_featured' => true,
            ],
            [
                'category' => 'powerpoint-prezentacii',
                'title' => 'Сделаю презентацию с современным дизайном',
                'slug' => 'sdelayu-prezentaciyu-s-sovremennym-dizaynom',
                'short_description' => 'Соберу презентацию с чистой структурой, визуальными акцентами и аккуратной сеткой слайдов.',
                'description' => 'Создам презентацию для учебного проекта, продукта или выступления. Помогу выстроить логику, привести слайды к единому стилю, оформить диаграммы, блоки текста и ключевые тезисы.',
                'price_from' => 2500,
                'delivery_days' => 4,
                'rating' => 4.95,
                'reviews_count' => 24,
                'orders_count' => 41,
                'is_featured' => true,
            ],
            [
                'category' => 'excel-i-tablicy',
                'title' => 'Подготовлю таблицу Excel с расчетами',
                'slug' => 'podgotovlyu-tablicu-excel-s-raschetami',
                'short_description' => 'Соберу таблицу с формулами, понятной структурой, расчетами и базовым оформлением.',
                'description' => 'Подготовлю Excel-таблицу для расчетов, учета или учебной задачи. Настрою формулы, листы, базовую проверку данных и понятный вид для дальнейшей работы.',
                'price_from' => 1800,
                'delivery_days' => 3,
                'rating' => 4.80,
                'reviews_count' => 15,
                'orders_count' => 27,
                'is_featured' => false,
            ],
            [
                'category' => 'kursovye-konsultacii',
                'title' => 'Помогу структурировать курсовую работу',
                'slug' => 'pomogu-strukturirovat-kursovuyu-rabotu',
                'short_description' => 'Разберу тему, помогу собрать план, структуру глав и список материалов для дальнейшей работы.',
                'description' => 'Консультационная услуга для тех, кому нужно привести курсовую работу к понятной структуре. Помогу сформулировать план, логику глав, задачи и аккуратный порядок материалов без написания работы вместо автора.',
                'price_from' => 2000,
                'delivery_days' => 3,
                'rating' => 4.85,
                'reviews_count' => 11,
                'orders_count' => 19,
                'is_featured' => true,
            ],
            [
                'category' => 'lendingi',
                'title' => 'Сделаю простой лендинг для услуги',
                'slug' => 'sdelayu-prostoy-lending-dlya-uslugi',
                'short_description' => 'Соберу одностраничный лендинг с понятной структурой, адаптивом и базовой визуальной системой.',
                'description' => 'Подготовлю простой лендинг для услуги, эксперта или небольшого продукта. Структура: первый экран, преимущества, процесс, блок доверия и форма-заглушка для заявки.',
                'price_from' => 9000,
                'delivery_days' => 7,
                'rating' => 4.92,
                'reviews_count' => 9,
                'orders_count' => 14,
                'is_featured' => false,
            ],
        ];

        foreach ($services as $index => $serviceData) {
            $category = Category::where('slug', $serviceData['category'])->firstOrFail();
            unset($serviceData['category']);

            $service = Service::updateOrCreate(
                ['slug' => $serviceData['slug']],
                [
                    ...$serviceData,
                    'user_id' => $performer->id,
                    'category_id' => $category->id,
                    'status' => Service::STATUS_PUBLISHED,
                ],
            );

            $this->syncPackages($service, $serviceData['price_from'], $serviceData['delivery_days']);
        }
    }

    private function syncPackages(Service $service, int $priceFrom, int $deliveryDays): void
    {
        $packages = [
            [
                'name' => 'Базовый',
                'description' => 'Минимальный объем работы по согласованному заданию.',
                'price' => $priceFrom,
                'delivery_days' => $deliveryDays,
                'revisions_count' => 1,
                'sort_order' => 10,
            ],
            [
                'name' => 'Оптимальный',
                'description' => 'Расширенный объем с дополнительной проверкой и аккуратной доработкой результата.',
                'price' => (int) round($priceFrom * 1.6),
                'delivery_days' => $deliveryDays + 1,
                'revisions_count' => 2,
                'sort_order' => 20,
            ],
            [
                'name' => 'Расширенный',
                'description' => 'Максимальный пакет с приоритетной подготовкой и несколькими итерациями правок.',
                'price' => (int) round($priceFrom * 2.3),
                'delivery_days' => $deliveryDays + 2,
                'revisions_count' => 3,
                'sort_order' => 30,
            ],
        ];

        foreach ($packages as $package) {
            ServicePackage::updateOrCreate(
                [
                    'service_id' => $service->id,
                    'name' => $package['name'],
                ],
                $package,
            );
        }
    }
}
