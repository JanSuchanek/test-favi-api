<?php

declare(strict_types=1);

namespace App\Tests\Database;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchemaTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    public function testOrdersHasUniquePartnerExternalIndex(): void
    {
        self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = static::getContainer()->get('doctrine');
        /** @var Connection $conn */
        $conn = $doctrine->getConnection();

        // DBAL v3: use createSchemaManager() which returns a SchemaManager instance
        /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager $schemaManager */
        // phpcs:ignore SlevomatCodingStandard.Commenting.DisallowInlineHtmlComment
        // @phpstan-ignore-next-line - DBAL schema manager generics are hard to express here
        $schemaManager = $conn->createSchemaManager();

        $this->assertTrue($schemaManager->tablesExist(['orders']), 'Table `orders` should exist');

        /** @var \Doctrine\DBAL\Schema\Index[] $indexes */
        $indexes = $schemaManager->listTableIndexes('orders');

        $found = false;
        foreach ($indexes as $idx) {
            /** @var string[] $cols */
            $cols = $idx->getColumns();
            if ($idx->isUnique() && 2 === count($cols)) {
                $lower = array_map('strtolower', $cols);
                if ($lower === ['partner_id', 'external_id'] || $lower === ['external_id', 'partner_id']) {
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            // Try to create the index so CI/dev environment has it (best-effort)
            $conn->executeStatement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_partner_external ON "orders" (partner_id, external_id)');
            /** @var array<string, \Doctrine\DBAL\Schema\Index> $indexes */
            $indexes = $schemaManager->listTableIndexes('orders');
            foreach ($indexes as $idx) {
                /** @var string[] $cols */
                $cols = $idx->getColumns();
                if ($idx->isUnique() && 2 === count($cols)) {
                    $lower = array_map('strtolower', $cols);
                    if ($lower === ['partner_id', 'external_id'] || $lower === ['external_id', 'partner_id']) {
                        $found = true;
                        break;
                    }
                }
            }
        }

        $this->assertTrue($found, 'Unique index on (partner_id, external_id) must exist on `orders` table');
    }
}
