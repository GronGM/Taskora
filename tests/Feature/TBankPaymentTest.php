<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\PaymentOperation;
use App\Models\User;
use App\Services\Payments\TBankClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TBankPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.mode' => 'tbank',
            'payments.tbank.terminal_key' => '1783349932680DEMO',
            'payments.tbank.password' => 'test_password',
        ]);
    }

    public function test_pay_button_inits_payment_and_redirects(): void
    {
        Http::fake([
            'securepay.tinkoff.ru/v2/Init' => Http::response([
                'Success' => true,
                'ErrorCode' => '0',
                'Status' => 'NEW',
                'PaymentId' => '4444555',
                'PaymentURL' => 'https://pay.tbank.ru/new/abc123',
            ], 200),
        ]);

        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['price' => 5000]);

        $this->actingAs($customer)
            ->post(route('customer.orders.mark-paid', $order))
            ->assertRedirect('https://pay.tbank.ru/new/abc123');

        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->refresh()->status);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/v2/Init')) {
                return false;
            }

            // Сумма в копейках, подпись присутствует и корректна.
            $client = app(TBankClient::class);

            return $request['Amount'] === 500000
                && $request['Token'] === $client->token(collect($request->data())->except('Token')->all());
        });
    }

    public function test_failed_init_shows_error_instead_of_500(): void
    {
        Http::fake([
            'securepay.tinkoff.ru/v2/Init' => Http::response([
                'Success' => false,
                'ErrorCode' => '204',
                'Message' => 'Неверные параметры.',
                'Details' => 'Неверный токен. Проверьте пару TerminalKey/SecretKey.',
            ], 200),
        ]);

        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['price' => 5000]);

        $this->actingAs($customer)
            ->from(route('customer.orders.show', $order))
            ->post(route('customer.orders.mark-paid', $order))
            ->assertRedirect(route('customer.orders.show', $order))
            ->assertSessionHas('error');

        // Заказ не изменился, деньги не двигались.
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->refresh()->status);
        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        $this->assertSame(0, PaymentOperation::query()->count());
        $this->assertSame(0, LedgerEntry::query()->count());
    }

    public function test_webhook_with_valid_token_confirms_payment(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()->for($customer, 'customer')->for($performer, 'performer')->create([
            'price' => 5000,
            'platform_fee_amount' => 750,
            'performer_amount' => 4250,
        ]);

        Http::fake([
            'securepay.tinkoff.ru/v2/GetState' => Http::response([
                'Success' => true,
                'Status' => 'CONFIRMED',
                'PaymentId' => '900001',
                'Amount' => 500000,
            ], 200),
        ]);

        $this->postJson(route('webhooks.tbank'), $this->signedNotification($order, 'CONFIRMED', '900001', 500000))
            ->assertOk()
            ->assertSee('OK');

        $order->refresh();
        $this->assertSame(Order::STATUS_IN_PROGRESS, $order->status);
        $this->assertSame(Order::PAYMENT_HELD, $order->payment_status);

        $operation = PaymentOperation::query()->where('type', PaymentOperation::TYPE_PAYMENT_HOLD)->firstOrFail();
        $this->assertSame(PaymentOperation::PROVIDER_TBANK, $operation->provider);
        $this->assertSame('900001', $operation->provider_operation_id);
        $this->assertSame(4, LedgerEntry::query()->count());
    }

    public function test_webhook_with_invalid_token_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['price' => 5000]);

        $payload = $this->signedNotification($order, 'CONFIRMED', '900002', 500000);
        $payload['Token'] = 'forged-token';

        $this->postJson(route('webhooks.tbank'), $payload)->assertStatus(403);

        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->refresh()->status);
        $this->assertSame(0, PaymentOperation::query()->count());
        Http::assertNothingSent();
    }

    public function test_webhook_is_idempotent(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['price' => 5000]);

        Http::fake([
            'securepay.tinkoff.ru/v2/GetState' => Http::response([
                'Success' => true,
                'Status' => 'CONFIRMED',
                'PaymentId' => '900003',
                'Amount' => 500000,
            ], 200),
        ]);

        $payload = $this->signedNotification($order, 'CONFIRMED', '900003', 500000);

        $this->postJson(route('webhooks.tbank'), $payload)->assertOk();
        $this->postJson(route('webhooks.tbank'), $payload)->assertOk();

        $this->assertSame(1, PaymentOperation::query()->where('type', PaymentOperation::TYPE_PAYMENT_HOLD)->count());
        $this->assertSame(4, LedgerEntry::query()->count());
    }

    public function test_webhook_rejects_amount_mismatch(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['price' => 5000]);

        Http::fake([
            'securepay.tinkoff.ru/v2/GetState' => Http::response([
                'Success' => true,
                'Status' => 'CONFIRMED',
                'PaymentId' => '900004',
                'Amount' => 100,
            ], 200),
        ]);

        $this->postJson(route('webhooks.tbank'), $this->signedNotification($order, 'CONFIRMED', '900004', 100))
            ->assertOk();

        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->refresh()->status);
        $this->assertSame(0, PaymentOperation::query()->count());
    }

    public function test_non_confirmed_statuses_are_ignored(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['price' => 5000]);

        $this->postJson(route('webhooks.tbank'), $this->signedNotification($order, 'AUTHORIZED', '900005', 500000))
            ->assertOk();

        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->refresh()->status);
        Http::assertNothingSent();
    }

    /**
     * @return array<string, mixed>
     */
    private function signedNotification(Order $order, string $status, string $paymentId, int $amount): array
    {
        $payload = [
            'TerminalKey' => '1783349932680DEMO',
            'OrderId' => 'taskora-'.$order->id.'-1751800000',
            'Success' => true,
            'Status' => $status,
            'PaymentId' => $paymentId,
            'Amount' => $amount,
            'ErrorCode' => '0',
        ];

        $payload['Token'] = app(TBankClient::class)->token($payload);

        return $payload;
    }
}
