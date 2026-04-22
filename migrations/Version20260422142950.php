<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260422142950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE film_director (film_id INT NOT NULL, person_id INT NOT NULL, PRIMARY KEY (film_id, person_id))');
        $this->addSql('CREATE INDEX IDX_BC171C99567F5183 ON film_director (film_id)');
        $this->addSql('CREATE INDEX IDX_BC171C99217BBB47 ON film_director (person_id)');
        $this->addSql('CREATE TABLE film_actor (film_id INT NOT NULL, person_id INT NOT NULL, PRIMARY KEY (film_id, person_id))');
        $this->addSql('CREATE INDEX IDX_DD19A8A9567F5183 ON film_actor (film_id)');
        $this->addSql('CREATE INDEX IDX_DD19A8A9217BBB47 ON film_actor (person_id)');
        $this->addSql('ALTER TABLE film_director ADD CONSTRAINT FK_BC171C99567F5183 FOREIGN KEY (film_id) REFERENCES film (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE film_director ADD CONSTRAINT FK_BC171C99217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE film_actor ADD CONSTRAINT FK_DD19A8A9567F5183 FOREIGN KEY (film_id) REFERENCES film (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE film_actor ADD CONSTRAINT FK_DD19A8A9217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE film_director DROP CONSTRAINT FK_BC171C99567F5183');
        $this->addSql('ALTER TABLE film_director DROP CONSTRAINT FK_BC171C99217BBB47');
        $this->addSql('ALTER TABLE film_actor DROP CONSTRAINT FK_DD19A8A9567F5183');
        $this->addSql('ALTER TABLE film_actor DROP CONSTRAINT FK_DD19A8A9217BBB47');
        $this->addSql('DROP TABLE film_director');
        $this->addSql('DROP TABLE film_actor');
    }
}
