<?php
declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use Symfony\Component\Serializer\Annotation\Groups;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`orders`')]
#[ApiResource(
    normalizationContext: ['groups' => ['order:read']],
    denormalizationContext: ['groups' => ['order:write']],
    operations: [
        new Post(
            uriTemplate: '/api/orders',
            controller: \App\Controller\ApiOrderCreateController::class
        ),
        new Patch(
            uriTemplate: '/api/orders/{partnerId}/{orderId}/delivery-date',
            controller: \App\Controller\ApiOrderDeliveryController::class
        ),
        new Get(),
        new GetCollection(),
    ]
)]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @internal Used by Doctrine */
    // @phpstan-ignore-next-line
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    #[Groups(['order:read','order:write'])]
    private ?string $partnerId = null;

    #[ORM\Column(length: 64)]
    #[Groups(['order:read','order:write'])]
    private ?string $externalId = null;

    #[ORM\Column]
    #[Groups(['order:read','order:write'])]
    private ?\DateTimeImmutable $expectedDeliveryAt = null;

    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $totalPrice = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['order:read','order:write'])]
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\OrderItem>
     * @phpstan-var \Doctrine\Common\Collections\Collection<int, \App\Entity\OrderItem>
     */
    // @phpstan-ignore-next-line
    private Collection $items;

    public function __construct(string $partnerId = '', string $externalId = '', ?\DateTimeImmutable $expectedDeliveryAt = null)
    {
        $this->items = new ArrayCollection();
        if ($partnerId !== '') {
            $this->partnerId = $partnerId;
        }
        if ($externalId !== '') {
            $this->externalId = $externalId;
        }
        if ($expectedDeliveryAt instanceof \DateTimeImmutable) {
            $this->expectedDeliveryAt = $expectedDeliveryAt;
        }
        $this->totalPrice = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartnerId(): ?string
    {
        return $this->partnerId;
    }

    public function setPartnerId(string $partnerId): static
    {
        $this->partnerId = $partnerId;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getExpectedDeliveryAt(): ?\DateTimeImmutable
    {
        return $this->expectedDeliveryAt;
    }

    public function setExpectedDeliveryAt(\DateTimeImmutable $expectedDeliveryAt): static
    {
        $this->expectedDeliveryAt = $expectedDeliveryAt;

        return $this;
    }

    public function getTotalPrice(): ?int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }
        $this->recalculateTotal();
        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item) && $item->getOrder() === $this) {
            $item->setOrder(null);
        }
        $this->recalculateTotal();
        return $this;
    }    

    public function updateExpectedDelivery(\DateTimeImmutable $date): static
    {
        $this->expectedDeliveryAt = $date;
        return $this;
    }

    private function recalculateTotal(): void
    {
        $sum = 0;
        /** @var OrderItem $it */
        foreach ($this->items as $it) {
            $price = $it->getPrice() ?? 0;
            $quantity = $it->getQuantity() ?? 0;
            $sum += $price * $quantity;
        }
        $this->totalPrice = $sum;
    }
}
