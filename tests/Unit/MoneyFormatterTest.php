<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Support\MoneyFormatter;

class MoneyFormatterTest extends TestCase
{
    public function test_money_formats_numbers(): void
    {
        $this->assertSame('10.50', MoneyFormatter::format(10.5));
        $this->assertSame('0.00', MoneyFormatter::format(null));
    }
}
