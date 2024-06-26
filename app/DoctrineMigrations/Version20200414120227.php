<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Wallabag\CoreBundle\Doctrine\WallabagMigration;

/**
 * Remove baggy theme.
 */
final class Version20200414120227 extends WallabagMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE ' . $this->getTable('config', true) . " SET theme = 'material';");
    }

    public function down(Schema $schema): void
    {
        throw new SkipMigrationException('Not possible ... ');
    }
}
