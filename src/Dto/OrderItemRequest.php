<?php
declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class OrderItemRequest
{
    #[Assert\NotBlank] public string $productId;
    #[Assert\NotBlank] public string $name;
    #[Assert\PositiveOrZero] public int $price;
    #[Assert\Positive] public int $quantity;
}
