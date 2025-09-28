<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`orders`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $partnerId = null;

    #[ORM\Column(length: 64)]
    private ?string $externalId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expectedDeliveryAt = null;

    #[ORM\Column]
    private int $totalPrice = 0;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct(
        string $partnerId = '',
        string $externalId = '',
        ?\DateTimeImmutable $expectedDeliveryAt = null
    ) {
        $this->items = new ArrayCollection();
        if ($partnerId !== '') {
            $this->partnerId = $partnerId;
        }
        if ($externalId !== '') {
            $this->externalId = $externalId;
        }
        if ($expectedDeliveryAt !== null) {
            $this->expectedDeliveryAt = $expectedDeliveryAt;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartnerId(): ?string
    {
        return $this->partnerId;
    }

    public function setPartnerId(string $partnerId): self
    {
        $this->partnerId = $partnerId;
        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getExpectedDeliveryAt(): ?\DateTimeImmutable
    {
        return $this->expectedDeliveryAt;
    }

    public function setExpectedDeliveryAt(?\DateTimeImmutable $expectedDeliveryAt): self
    {
        $this->expectedDeliveryAt = $expectedDeliveryAt;
        return $this;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }
        $this->recalculateTotal();
        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }
        $this->recalculateTotal();
        return $this;
    }

    private function recalculateTotal(): void
    {
        $sum = 0;
        /** @var OrderItem $it */
        foreach ($this->items as $it) {
            $sum += ($it->getPrice() ?? 0) * ($it->getQuantity() ?? 0);
        }
        $this->totalPrice = $sum;
    }
}