<?php
declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class OrderRequest
{
    #[Assert\NotBlank] public string $partnerId;
    #[Assert\NotBlank] public string $orderId;
    #[Assert\NotBlank] public string $expectedDeliveryAt; // ISO 8601 string
    /** @var list<OrderItemRequest> */
    #[Assert\Count(min: 1)]
    #[Assert\Valid] public array $products = [];
}