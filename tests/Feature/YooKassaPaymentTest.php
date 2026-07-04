<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\PaymentOperation;
use App\Models\ProviderWebhookEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YooKassaPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.mode' => 'yookassa',
            'payments.yookassa.shop_id' => '1402173',
            'payments.yookassa.secret_key' => 'test_secret',
        ]);
    }

    public function test_pay_button_redirects_to_yookassa_confirmation(): void
    {
        Http::fake([
            'api.yookassa.ru/v3/payments' => Http::response([
                'id' => 'pay_123',
                'status' => 'pending',
                'confirmation' => ['type' => 'redirect', 'confirmation_url' => 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=pay_123'],
            ], 200),
        ]);

        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['price' => 5000]);

        $this->actingAs($customer)
            ->post(route('customer.orders.mark-paid', $order))
            ->assertRedirect('https://yoomoney.ru/checkout/payments/v2/contract?orderId=pay_123');

        // Заказ не меняется до подтверждения вебхуком.
        $order->refresh();
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->status);
        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/v3/payments')
                && $request['amount']['value'] === '5000.00'
                && $request['metadata']['order_id'] !== '';
        });
    }

    public function test_webhook_confirms_payment_and_records_ledger(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()->for($customer, 'customer')->for($performer, 'performer')->create([
            'price' => 5000,
            'platform_fee_amount' => 750,
            'performer_amount' => 4250,
        ]);

        Http::fake([
            'api.yookassa.ru/v3/payments/pay_123' => Http::response([
                'id' => 'pay_123',
                'status' => 'succeeded',
                'amount' => ['value' => '5000.00', 'currency' => 'RUB'],
                'metadata' => ['order_id' => (string) $order->id],
            ], 200),
        ]);

        $this->postJson(route('webhooks.yookassa'), [
            'type' => 'notification',
            'event' => 'payment.succeeded',
            'object' => ['id' => 'pay_123'],
        ])->assertOk()->assertJson(['status' => 'ok']);

        $order->refresh();
        $this->assertSame(Order::STATUS_IN_PROGRESS, $order->status);
        $this->assertSame(Order::PAYMENT_HELD, $order->payment_status);

        $operation = PaymentOperation::query()->where('type', PaymentOperation::TYPE_PAYMENT_HOLD)->firstOrFail();
        $this->assertSame(PaymentOperation::PROVIDER_YOOKASSA, $operation->provider);
        $this->assertSame('pay_123', $operation->provider_operation_id);
        $this->assertSame(4, LedgerEntry::query()->count());

        $this->assertDatabaseHas('provider_webhook_events', [
            'provider' => 'yookassa',
            'event_id' => 'payment.succeeded:pay_123',
            'status' => ProviderWebhookEvent::STATUS_PROCESSED,
        ]);
    }

    public function test_webhook_is_idempotent(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['price' => 5000]);

        Http::fake([
            'api.yookassa.ru/v3/payments/pay_777' => Http::response([
                'id' => 'pay_777',
                'status' => 'succeeded',
                'amount' => ['value' => '5000.00', 'currency' => 'RUB'],
                'metadata' => ['order_id' => (string) $order->id],
            ], 200),
        ]);

        $payload = ['type' => 'notification', 'event' => 'payment.succeeded', 'object' => ['id' => 'pay_777']];

        $this->postJson(route('webhooks.yookassa'), $payload)->assertOk();
        $this->postJson(route('webhooks.yookassa'), $payload)->assertOk()->assertJson(['status' => 'already_processed']);

        $this->assertSame(1, PaymentOperation::query()->where('type', PaymentOperation::TYPE_PAYMENT_HOLD)->count());
        $this->assertSame(4, LedgerEntry::query()->count());
    }

    public function test_forged_webhook_is_ignored_when_api_says_pending(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['price' => 5000]);

        Http::fake([
            'api.yookassa.ru/v3/payments/pay_fake' => Http::response([
                'id' => 'pay_fake',
                'status' => 'pending',
                'amount' => ['value' => '5000.00', 'currency' => 'RUB'],
                'metadata' => ['order_id' => (string) $order->id],
            ], 200),
        ]);

        $this->postJson(route('webhooks.yookassa'), [
            'type' => 'notification',
            'event' => 'payment.succeeded',
            'object' => ['id' => 'pay_fake'],
        ])->assertOk()->assertJson(['status' => 'ignored']);

        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->refresh()->status);
        $this->assertSame(0, PaymentOperation::query()->count());
    }

    public function test_webhook_rejects_amount_mismatch(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['price' => 5000]);

        Http::fake([
            'api.yookassa.ru/v3/payments/pay_low' => Http::response([
                'id' => 'pay_low',
                'status' => 'succeeded',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
                'metadata' => ['order_id' => (string) $order->id],
            ], 200),
        ]);

        $this->postJson(route('webhooks.yookassa'), [
            'type' => 'notification',
            'event' => 'payment.succeeded',
            'object' => ['id' => 'pay_low'],
        ])->assertOk()->assertJson(['status' => 'amount_mismatch']);

        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->refresh()->status);
        $this->assertSame(0, PaymentOperation::query()->count());
    }

    public function test_webhook_bypasses_beta_access_gate(): void
    {
        config(['beta.enabled' => true, 'beta.password' => 'secret-beta']);

        $this->postJson(route('webhooks.yookassa'), [])
            ->assertOk()
            ->assertJson(['status' => 'ignored']);
    }

    public function test_stub_mode_still_works_by_default(): void
    {
        config(['payments.mode' => 'stub']);

        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->post(route('customer.orders.mark-paid', $order))
            ->assertRedirect(route('customer.orders.show', $order));

        $this->assertSame(Order::PAYMENT_HELD, $order->refresh()->payment_status);
        Http::assertNothingSent();
    }
}
