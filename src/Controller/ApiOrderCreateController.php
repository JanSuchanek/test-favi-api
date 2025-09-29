<?php
declare(strict_types=1);

namespace App\Controller;

use App\Dto\OrderRequest;
use App\Dto\OrderItemRequest;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ApiOrderCreateController
{
    public function __invoke(Request $req, ValidatorInterface $v, EntityManagerInterface $em): JsonResponse
    {
        $decoded = json_decode($req->getContent(), true);
        $data = is_array($decoded) ? $decoded : [];

        $dto = new OrderRequest();
        $dto->partnerId = isset($data['partnerId']) && is_string($data['partnerId']) ? $data['partnerId'] : '';
        $dto->orderId = isset($data['orderId']) && is_string($data['orderId']) ? $data['orderId'] : '';
        $dto->expectedDeliveryAt = isset($data['expectedDeliveryAt']) && is_string($data['expectedDeliveryAt']) ? $data['expectedDeliveryAt'] : '';

        $productsRaw = $data['products'] ?? [];
        $products = [];
        if (is_array($productsRaw)) {
            foreach ($productsRaw as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $it = new OrderItemRequest();
                $it->productId = isset($item['id']) && is_string($item['id']) ? $item['id'] : (isset($item['productId']) && is_string($item['productId']) ? $item['productId'] : '');
                $it->name = isset($item['name']) && is_string($item['name']) ? $item['name'] : '';
                $it->price = isset($item['price']) && (is_int($item['price']) || is_numeric($item['price'])) ? (int)$item['price'] : 0;
                $it->quantity = isset($item['quantity']) && (is_int($item['quantity']) || is_numeric($item['quantity'])) ? (int)$item['quantity'] : 0;
                $products[] = $it;
            }
        }
        $dto->products = $products;

        $errors = $v->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string)$errors], 422);
        }

        // duplicate guard
        $existing = $em->getRepository(Order::class)->findOneBy(['partnerId' => $dto->partnerId, 'externalId' => $dto->orderId]);
        if ($existing instanceof \App\Entity\Order) {
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
                ]
            ], 409, ['Location' => $location]);
        }

        $expected = new \DateTimeImmutable($dto->expectedDeliveryAt);
        $order = new Order($dto->partnerId, $dto->orderId, $expected);
        foreach ($dto->products as $p) {
            $item = new OrderItem($p->productId, $p->name, $p->price, $p->quantity);
            $order->addItem($item);
        }

        $em->persist($order);
        $em->flush();

        return new JsonResponse([
            'partnerId' => $dto->partnerId,
            'orderId' => $dto->orderId,
            'totalPrice' => $order->getTotalPrice(),
        ], 201);
    }
}


