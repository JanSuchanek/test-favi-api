<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250923192542_add_unique_partner_external extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique index on partner_id + external_id in orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_partner_external ON "orders" (partner_id, external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_partner_external');
    }
}


