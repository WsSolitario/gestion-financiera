<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\PaymentController;
use ReflectionClass;

class MoneyHelperTest extends TestCase
{
    public function test_money_formats_numbers(): void
    {
        $controller = new PaymentController();
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('money');
        $method->setAccessible(true);

        $this->assertSame('10.50', $method->invoke($controller, 10.5));
        $this->assertSame('0.00', $method->invoke($controller, null));
    }
}
