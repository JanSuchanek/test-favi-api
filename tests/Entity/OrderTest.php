<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\OrderItem;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testTotalPriceCalculation(): void
    {
        $order = new Order('partner-1', 'ord-1', new \DateTimeImmutable('2025-09-23'));

        $item1 = new OrderItem('p1', 'Product 1', 100, 2); // 200
        $item2 = new OrderItem('p2', 'Product 2', 50, 1);  // 50

        $order->addItem($item1);
        $order->addItem($item2);

        $this->assertSame(250, $order->getTotalPrice());
    }
}


