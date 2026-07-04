<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_legal_pages_are_public(): void
    {
        foreach (['legal.offer', 'legal.safe-deal', 'legal.payments', 'legal.privacy'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('Legal/Show')
                    ->has('sections')
                    ->has('updatedAt'));
        }
    }

    public function test_offer_contains_company_requisites_and_fee(): void
    {
        config(['payments.platform_fee_percent' => 15]);

        $this->get(route('legal.offer'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('title', 'Публичная оферта')
                ->where('company.ogrn', '1214300009351')
                ->where('company.inn', '4312157043')
                ->where('sections', fn ($sections) => str_contains(json_encode($sections, JSON_UNESCAPED_UNICODE), '15% от суммы Сделки')));
    }

    public function test_safe_deal_rules_contain_required_topics(): void
    {
        $this->get(route('legal.safe-deal'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('sections', function ($sections): bool {
                $text = json_encode($sections, JSON_UNESCAPED_UNICODE);

                return str_contains($text, 'Условия предоставления услуги')
                    && str_contains($text, 'Порядок заключения сделки')
                    && str_contains($text, 'Удержание вознаграждения Платформы')
                    && str_contains($text, 'Расторжение сделки и возвраты')
                    && str_contains($text, 'Гарантии и зоны ответственности');
            }));
    }

    public function test_privacy_policy_mentions_152_fz_and_operator(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('sections', fn ($sections) => str_contains(json_encode($sections, JSON_UNESCAPED_UNICODE), '152-ФЗ')));
    }
}
