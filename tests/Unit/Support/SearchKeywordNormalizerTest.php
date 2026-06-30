<?php

namespace Tests\Unit\Support;

use App\Support\SearchKeywordNormalizer;
use PHPUnit\Framework\TestCase;

class SearchKeywordNormalizerTest extends TestCase
{
    public function test_lowercases_and_trims(): void
    {
        $this->assertSame('brake pad', SearchKeywordNormalizer::normalize('  BraKe  Pad  '));
    }

    public function test_collapses_internal_whitespace(): void
    {
        $this->assertSame('oil filter pro', SearchKeywordNormalizer::normalize("oil\tfilter   pro"));
    }

    public function test_strips_control_characters(): void
    {
        $this->assertSame('headlight', SearchKeywordNormalizer::normalize("head\x00light\x1f"));
    }

    public function test_returns_null_when_below_min_length(): void
    {
        $this->assertNull(SearchKeywordNormalizer::normalize('a'));
        $this->assertNull(SearchKeywordNormalizer::normalize(' '));
        $this->assertNull(SearchKeywordNormalizer::normalize(''));
    }

    public function test_returns_null_when_only_symbols_or_digits(): void
    {
        $this->assertNull(SearchKeywordNormalizer::normalize('???'));
        $this->assertNull(SearchKeywordNormalizer::normalize('1234567890'));
        $this->assertNull(SearchKeywordNormalizer::normalize('___---'));
    }

    public function test_truncates_to_eighty_chars(): void
    {
        $long = str_repeat('a', 200);
        $this->assertSame(80, mb_strlen(SearchKeywordNormalizer::normalize($long)));
    }
}
