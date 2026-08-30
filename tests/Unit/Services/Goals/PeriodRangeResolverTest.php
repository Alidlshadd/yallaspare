<?php

namespace Tests\Unit\Services\Goals;

use App\Services\Goals\PeriodRangeResolver;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PeriodRangeResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_it_resolves_all_periods_in_the_business_timezone(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 10:00', 'Asia/Baghdad'));
        $resolver = app(PeriodRangeResolver::class);

        $weekly = $resolver->resolve('weekly');
        $monthly = $resolver->resolve('monthly', '2026-09-15');
        $yearly = $resolver->resolve('yearly', '2026-06-01');

        $this->assertSame('2026-08-31', $weekly['anchor']);
        $this->assertSame('2026-09-06', $weekly['display_end']->toDateString());
        $this->assertSame('2026-09-01', $monthly['anchor']);
        $this->assertSame('2026-10-01', $monthly['next_anchor']);
        $this->assertSame('2026-01-01', $yearly['anchor']);
        $this->assertSame('Asia/Baghdad', $yearly['timezone']);
    }
}
