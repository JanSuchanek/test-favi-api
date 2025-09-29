<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\OrderRequest;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;

final class OrderService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function createFromRequest(OrderRequest $dto): Order
    {
        $expected = new \DateTimeImmutable($dto->expectedDeliveryAt);
        $order = new Order($dto->partnerId, $dto->orderId, $expected);
        foreach ($dto->products as $p) {
            $item = new OrderItem($p->productId, $p->name, $p->price, $p->quantity);
            $order->addItem($item);
        }

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }
}
