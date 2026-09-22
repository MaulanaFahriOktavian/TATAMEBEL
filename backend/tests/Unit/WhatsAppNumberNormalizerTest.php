<?php

namespace Tests\Unit;

use App\Support\WhatsAppNumberNormalizer;
use PHPUnit\Framework\TestCase;

class WhatsAppNumberNormalizerTest extends TestCase
{
    public function test_normalizes_standard_indonesian_phone_starting_with_zero(): void
    {
        $normalized = WhatsAppNumberNormalizer::normalize('081234567890');
        $this->assertEquals('6281234567890', $normalized);
    }

    public function test_normalizes_phone_with_plus_prefix(): void
    {
        $normalized = WhatsAppNumberNormalizer::normalize('+6281234567890');
        $this->assertEquals('6281234567890', $normalized);
    }

    public function test_normalizes_phone_already_in_international_format(): void
    {
        $normalized = WhatsAppNumberNormalizer::normalize('6281234567890');
        $this->assertEquals('6281234567890', $normalized);
    }

    public function test_strips_formatting_characters_spaces_dashes_parentheses(): void
    {
        $this->assertEquals('6281234567890', WhatsAppNumberNormalizer::normalize('0812-3456-7890'));
        $this->assertEquals('6281234567890', WhatsAppNumberNormalizer::normalize('+62 812 3456 7890'));
        $this->assertEquals('6281234567890', WhatsAppNumberNormalizer::normalize('(0812) 3456 7890'));
        $this->assertEquals('6281234567890', WhatsAppNumberNormalizer::normalize('0812.3456.7890'));
    }

    public function test_rejects_empty_and_null(): void
    {
        $this->assertNull(WhatsAppNumberNormalizer::normalize(null));
        $this->assertNull(WhatsAppNumberNormalizer::normalize(''));
        $this->assertNull(WhatsAppNumberNormalizer::normalize('   '));
    }

    public function test_rejects_invalid_strings(): void
    {
        $this->assertNull(WhatsAppNumberNormalizer::normalize('invalid-number'));
        $this->assertNull(WhatsAppNumberNormalizer::normalize('0812abc3456'));
        $this->assertNull(WhatsAppNumberNormalizer::normalize('12345')); // too short
        $this->assertNull(WhatsAppNumberNormalizer::normalize('0211234567')); // Indonesian landline (not mobile 08)
    }

    public function test_validates_correctly(): void
    {
        $this->assertTrue(WhatsAppNumberNormalizer::isValid('6281234567890'));
        $this->assertFalse(WhatsAppNumberNormalizer::isValid('12345'));
        $this->assertFalse(WhatsAppNumberNormalizer::isValid(null));
        $this->assertFalse(WhatsAppNumberNormalizer::isValid('62211234567')); // landline
    }
}
