<?php

namespace App\Services\Moderation;

class ContactGuardResult
{
    private function __construct(
        public readonly bool $passed,
        public readonly ?string $matchedType = null,
        public readonly ?string $matchedValue = null,
    ) {
    }

    public static function passed(): self
    {
        return new self(true);
    }

    public static function failed(string $matchedType, string $matchedValue): self
    {
        return new self(false, $matchedType, mb_substr($matchedValue, 0, 120));
    }

    public function failedCheck(): bool
    {
        return ! $this->passed;
    }
}
