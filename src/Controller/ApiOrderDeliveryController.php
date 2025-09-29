<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ApiOrderDeliveryController
{
    public function __invoke(string $partnerId, string $orderId, Request $req, EntityManagerInterface $em): JsonResponse
    {
        $decoded = json_decode($req->getContent(), true);
        $body = is_array($decoded) ? $decoded : [];
        if (!isset($body['expectedDeliveryAt']) || !is_string($body['expectedDeliveryAt'])) {
            return new JsonResponse(['error' => 'expectedDeliveryAt is required (ISO 8601)'], 400);
        }

        $date = new \DateTimeImmutable($body['expectedDeliveryAt']);
        $order = $em->getRepository(Order::class)->findOneBy(['partnerId' => $partnerId, 'externalId' => $orderId]);
        if (!$order instanceof \App\Entity\Order) {
            return new JsonResponse(['error' => 'Order not found'], 404);
        }

        $order->updateExpectedDelivery($date);
        $em->flush();

        return new JsonResponse(['status' => 'ok']);
    }
}


