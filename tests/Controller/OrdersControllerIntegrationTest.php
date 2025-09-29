<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrdersControllerIntegrationTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    public function testPostCreatesOrderAndPersists(): void
    {
        $client = static::createClient();

        $body = [
            'partnerId' => 'integration-p',
            'orderId' => 'integration-o',
            'expectedDeliveryAt' => '2025-12-01',
            'products' => [
                ['productId' => 'p1', 'name' => 'Product 1', 'price' => 100, 'quantity' => 2],
            ],
        ];

        // ensure no leftover from previous runs for this test identifiers
        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = static::getContainer()->get('doctrine');
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Order::class);
        $old = $repo->findOneBy(['partnerId' => 'integration-p', 'externalId' => 'integration-o']);
        if ($old) {
            $em->remove($old);
            $em->flush();
        }

        $client->request('POST', '/api/orders', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode($body));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = static::getContainer()->get('doctrine');
        /** @var \App\Repository\OrderRepository $repo */
        $repo = $doctrine->getRepository(Order::class);

        $order = $repo->findOneBy(['partnerId' => 'integration-p', 'externalId' => 'integration-o']);
        $this->assertNotNull($order);
        $this->assertSame(200, $order->getTotalPrice());

        // cleanup
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $em->remove($order);
        $em->flush();
    }

    public function testPostDuplicateReturns409(): void
    {
        $client = static::createClient();

        $body = [
            'partnerId' => 'dup-p',
            'orderId' => 'dup-o',
            'expectedDeliveryAt' => '2025-12-01',
            'products' => [
                ['productId' => 'p1', 'name' => 'Product 1', 'price' => 100, 'quantity' => 2],
            ],
        ];

        // ensure no leftover from previous runs for this test identifiers
        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = static::getContainer()->get('doctrine');
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository(Order::class);
        $old = $repo->findOneBy(['partnerId' => 'dup-p', 'externalId' => 'dup-o']);
        if ($old) {
            $em->remove($old);
            $em->flush();
        }

        // insert existing order directly to simulate duplicate
        $existing = new Order('dup-p', 'dup-o', new \DateTimeImmutable('2025-12-01'));
        $existing->addItem(new OrderItem('p1', 'Product 1', 100, 2));
        $em->persist($existing);
        $em->flush();

        // attempt to create same order via API should return 409
        $client->request('POST', '/api/orders', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode($body));
        $this->assertSame(409, $client->getResponse()->getStatusCode());

        // cleanup
        $order = $repo->findOneBy(['partnerId' => 'dup-p', 'externalId' => 'dup-o']);
        if ($order) {
            $em->remove($order);
            $em->flush();
        }
    }

    public function testPatchUpdatesExpectedDelivery(): void
    {
        $client = static::createClient();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = static::getContainer()->get('doctrine');
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();

        // ensure no leftover from previous runs for this patch test identifiers
        /** @var \App\Repository\OrderRepository $repo */
        $repo = $doctrine->getRepository(Order::class);
        $oldPatch = $repo->findOneBy(['partnerId' => 'int-patch-p', 'externalId' => 'int-patch-o']);
        if ($oldPatch) {
            $em->remove($oldPatch);
            $em->flush();
        }

        $order = new Order('int-patch-p', 'int-patch-o', new \DateTimeImmutable('2025-01-01'));
        $item = new OrderItem('ip1', 'Item', 10, 1);
        $order->addItem($item);
        $em->persist($order);
        $em->flush();

        $client->request('PATCH', '/api/orders/int-patch-p/int-patch-o/delivery-date', [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode(['expectedDeliveryAt' => '2026-01-01']));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        /** @var \App\Repository\OrderRepository $repo */
        $repo = $doctrine->getRepository(Order::class);
        $found = $repo->findOneBy(['partnerId' => 'int-patch-p', 'externalId' => 'int-patch-o']);
        $this->assertNotNull($found);
        /* @var \App\Entity\Order $found */
        $this->assertInstanceOf(Order::class, $found);
        $this->assertNotNull($found->getExpectedDeliveryAt());
        $this->assertSame('2026-01-01', $found->getExpectedDeliveryAt()->format('Y-m-d'));

        // cleanup
        $em->remove($found);
        $em->flush();
    }
}
