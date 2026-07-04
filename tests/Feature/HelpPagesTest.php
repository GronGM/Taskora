<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_how_it_works_page_is_public(): void
    {
        $this->get('/how-it-works')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Help/HowItWorks')
                ->has('feePercent')
                ->has('reviewHold.min')
                ->has('reviewHold.max'));
    }

    public function test_faq_page_is_public_and_has_items(): void
    {
        $this->get('/faq')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Help/Faq')
                ->has('items', 10)
                ->where('items.0.question', 'Сколько стоит пользоваться Таскорой?'));
    }

    public function test_faq_answers_use_configured_fee_percent(): void
    {
        config(['payments.platform_fee_percent' => 12]);

        $this->get('/faq')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('items.0.answer', fn ($answer) => str_contains($answer, '12%')));
    }
}
