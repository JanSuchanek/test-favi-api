<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\OrderItemRequest;
use App\Dto\OrderRequest;
use App\Entity\Order;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ApiOrderCreateController
{
    public function __invoke(Request $req, ValidatorInterface $v, EntityManagerInterface $em, OrderService $orderService): JsonResponse
    {
        $decoded = json_decode($req->getContent(), true);
        $data = is_array($decoded) ? $decoded : [];

        $dto = new OrderRequest();

        $dto->partnerId = '';
        if (isset($data['partnerId']) && is_string($data['partnerId'])) {
            $dto->partnerId = $data['partnerId'];
        }

        $dto->orderId = '';
        if (isset($data['orderId']) && is_string($data['orderId'])) {
            $dto->orderId = $data['orderId'];
        }

        $dto->expectedDeliveryAt = '';
        if (isset($data['expectedDeliveryAt']) && is_string($data['expectedDeliveryAt'])) {
            $dto->expectedDeliveryAt = $data['expectedDeliveryAt'];
        }

        $productsRaw = $data['products'] ?? [];
        $products = [];
        if (is_array($productsRaw)) {
            foreach ($productsRaw as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $it = new OrderItemRequest();

                $productId = '';
                if (isset($item['id']) && is_string($item['id'])) {
                    $productId = $item['id'];
                } elseif (isset($item['productId']) && is_string($item['productId'])) {
                    $productId = $item['productId'];
                }
                $it->productId = $productId;

                $it->name = '';
                if (isset($item['name']) && is_string($item['name'])) {
                    $it->name = $item['name'];
                }

                $it->price = 0;
                if (isset($item['price']) && (is_int($item['price']) || is_numeric($item['price']))) {
                    $it->price = (int) $item['price'];
                }

                $it->quantity = 0;
                if (isset($item['quantity']) && (is_int($item['quantity']) || is_numeric($item['quantity']))) {
                    $it->quantity = (int) $item['quantity'];
                }
                $products[] = $it;
            }
        }
        $dto->products = $products;

        $errors = $v->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 422);
        }

        // duplicate guard
        $existing = $em->getRepository(Order::class)
            ->findOneBy([
                'partnerId' => $dto->partnerId,
                'externalId' => $dto->orderId,
            ]);
        if ($existing instanceof Order) {
            // Return 409 with a helpful body pointing to existing resource
            $partner = $existing->getPartnerId() ?? '';
            $external = $existing->getExternalId() ?? '';
            $location = '/api/orders/' . urlencode($partner) . '/' . urlencode($external);

            return new JsonResponse([
                'error' => 'order already exists',
                'existing' => [
                    'partnerId' => $existing->getPartnerId(),
                    'orderId' => $existing->getExternalId(),
                    'totalPrice' => $existing->getTotalPrice(),
                    'location' => $location,
                ],
            ], 409, ['Location' => $location]);
        }

        // Validate expectedDeliveryAt presence and format to avoid 500 on invalid input
        if ('' === trim($dto->expectedDeliveryAt)) {
            return new JsonResponse(['errors' => 'expectedDeliveryAt is required'], 422);
        }

        try {
            $expected = new \DateTimeImmutable($dto->expectedDeliveryAt);
        } catch (\Throwable $e) {
            return new JsonResponse(['errors' => 'expectedDeliveryAt has invalid format'], 422);
        }
        // Delegate creation and persistence to OrderService
        $order = $orderService->createFromRequest($dto);

        return new JsonResponse([
            'partnerId' => $dto->partnerId,
            'orderId' => $dto->orderId,
            'totalPrice' => $order->getTotalPrice(),
        ], 201);
    }
}
