<?php

namespace App\Services\Moderation;

class ContactGuard
{
    /**
     * @var array<string, string>
     */
    private array $patterns = [
        'email' => '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu',
        'phone' => '/(?:\+?\d[\s().\-]*){10,}/u',
        'telegram' => '/(@[a-z0-9_]{5,}|t(?:elegram)?\.me\/|telegram|телеграм|(?:^|\s)тг(?:\s|$)|напиши\s+в\s+тг)/iu',
        'whatsapp' => '/(whats?app|вотсап|перейдем\s+в\s+вотсап)/iu',
        'vk' => '/(vk\.com|вконтакте|(?:^|\s)vk(?:\s|$))/iu',
        'discord' => '/(discord(?:\.gg|app\.com)?|дискорд)/iu',
        'skype' => '/(skype|скайп)/iu',
        'bank_card' => '/(?:\d[ -]?){16}/u',
        'direct_deal_phrase' => '/(давай\s+напрямую|оплата\s+мимо\s+сайта|скинь\s+номер|перейдем\s+в\s+вотсап|напиши\s+в\s+тг)/iu',
    ];

    public function check(?string $text): ContactGuardResult
    {
        $normalized = $this->normalize($text);

        if ($normalized === '') {
            return ContactGuardResult::passed();
        }

        foreach ($this->patterns as $type => $pattern) {
            if (preg_match($pattern, $normalized, $matches) === 1) {
                return ContactGuardResult::failed($type, $matches[0]);
            }
        }

        return ContactGuardResult::passed();
    }

    private function normalize(?string $text): string
    {
        $text = mb_strtolower((string) $text);
        $text = str_replace(['ё'], ['е'], $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}
