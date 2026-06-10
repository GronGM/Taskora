<?php

namespace Tests\Unit;

use App\Services\Moderation\ContactGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContactGuardTest extends TestCase
{
    #[DataProvider('safeTechnicalNumbers')]
    public function test_technical_timestamps_are_not_detected_as_phone(string $text): void
    {
        $result = (new ContactGuard())->check($text);

        $this->assertFalse($result->failedCheck());
    }

    #[DataProvider('realPhones')]
    public function test_real_phone_numbers_are_blocked(string $text): void
    {
        $result = (new ContactGuard())->check($text);

        $this->assertTrue($result->failedCheck());
        $this->assertSame('phone', $result->matchedType);
    }

    #[DataProvider('otherContacts')]
    public function test_other_contact_types_still_block(string $text, string $type): void
    {
        $result = (new ContactGuard())->check($text);

        $this->assertTrue($result->failedCheck());
        $this->assertSame($type, $result->matchedType);
    }

    public static function safeTechnicalNumbers(): array
    {
        return [
            'compact timestamp' => ['Beta QA 20260610123456'],
            'date and time with dashes' => ['Beta QA task 2026-06-10-16-28-17'],
            'date and time with separators' => ['Срок проверки 2026-06-10 16:28:17'],
            'slug-like numeric tail' => ['document-review-2026-06-10-16-28-17'],
        ];
    }

    public static function realPhones(): array
    {
        return [
            'plus spaced' => ['Свяжемся по +7 999 123-45-67'],
            'eight spaced' => ['Телефон 8 999 123-45-67'],
            'seven compact' => ['79991234567'],
            'eight compact' => ['89991234567'],
            'plus compact' => ['+79991234567'],
        ];
    }

    public static function otherContacts(): array
    {
        return [
            'email' => ['Пишите на test@example.com', 'email'],
            'telegram' => ['Напиши в тг @taskora_helper', 'telegram'],
        ];
    }
}
