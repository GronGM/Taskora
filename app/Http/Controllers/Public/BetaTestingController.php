<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\BetaAccess;
use Inertia\Inertia;
use Inertia\Response;

class BetaTestingController extends Controller
{
    public function __invoke(): Response
    {
        abort_unless(BetaAccess::betaToolingAvailable(), 404);

        return Inertia::render('BetaTesting/Index', [
            'accounts' => $this->accounts(),
            'roleChecklists' => $this->roleChecklists(),
            'feedbackUrl' => route('beta-feedback.create'),
        ]);
    }

    /**
     * @return array<int, array{role: string, email: string, password: string, dashboard: string}>
     */
    private function accounts(): array
    {
        return [
            ['role' => 'Заказчик', 'email' => 'customer@taskora.local', 'password' => 'password', 'dashboard' => '/customer/dashboard'],
            ['role' => 'Исполнитель', 'email' => 'performer@taskora.local', 'password' => 'password', 'dashboard' => '/performer/dashboard'],
            ['role' => 'Модератор', 'email' => 'moderator@taskora.local', 'password' => 'password', 'dashboard' => '/moderator/dashboard'],
            ['role' => 'Администратор', 'email' => 'admin@taskora.local', 'password' => 'password', 'dashboard' => '/admin/dashboard'],
        ];
    }

    /**
     * @return array<int, array{role: string, goal: string, steps: array<int, string>, expected: string}>
     */
    private function roleChecklists(): array
    {
        return [
            [
                'role' => 'Гость',
                'goal' => 'Проверить публичную витрину и закрытый beta-доступ без входа в аккаунт.',
                'steps' => [
                    'Открыть главную страницу, каталог, задания и исполнителей.',
                    'Проверить страницу beta-тестирования и форму обратной связи.',
                    'Отправить короткий отзыв как гость без личных данных.',
                ],
                'expected' => 'Публичные страницы открываются после beta-пароля, форма сохраняет обращение.',
            ],
            [
                'role' => 'Заказчик',
                'goal' => 'Проверить поиск услуги, создание заказа и работу с заданием.',
                'steps' => [
                    'Войти как заказчик и открыть каталог.',
                    'Создать заказ из услуги через локальную заглушку оплаты.',
                    'Создать индивидуальное задание и проверить отклики.',
                ],
                'expected' => 'Заказ и задание проходят базовый сценарий без реальных платежей.',
            ],
            [
                'role' => 'Исполнитель',
                'goal' => 'Проверить управление услугами, отклики и рабочую область заказа.',
                'steps' => [
                    'Войти как исполнитель и открыть кабинет.',
                    'Создать услугу или отредактировать черновик.',
                    'Откликнуться на задание и отправить работу в заказе.',
                ],
                'expected' => 'Исполнитель видит свои услуги, отклики и заказы, а действия сохраняются.',
            ],
            [
                'role' => 'Модератор',
                'goal' => 'Проверить очереди модерации и обработку спорных материалов.',
                'steps' => [
                    'Войти как модератор.',
                    'Открыть услуги на модерации, профили исполнителей, флаги и споры.',
                    'Проверить, что реальные платежные действия недоступны.',
                ],
                'expected' => 'Модератор видит только рабочие очереди и не получает административные права.',
            ],
            [
                'role' => 'Администратор',
                'goal' => 'Проверить административные сводки и beta-обратную связь.',
                'steps' => [
                    'Войти как администратор.',
                    'Открыть административную панель и раздел beta-отзывов.',
                    'Изменить статус тестового обращения.',
                ],
                'expected' => 'Администратор видит обратную связь и меняет статусы без production-действий.',
            ],
        ];
    }
}
