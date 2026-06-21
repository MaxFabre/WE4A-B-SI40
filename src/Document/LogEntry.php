<?php

namespace App\Document;

use DateTime;
use DateTimeZone;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'logs')]
#[ODM\Index(keys: ['createdAt' => 1], options: ['expireAfterSeconds' => 60 * 60 * 24 * 90])]
class LogEntry
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'int')]
    private ?int $userId = null;

    #[ODM\Field(type: 'string')]
    private string $type;

    #[ODM\Field(type: 'string')]
    private string $status = '';

    #[ODM\Field(type: 'string')]
    private ?string $details = null;

    #[ODM\Field(type: 'date')]
    private DateTime $createdAt;

    public function __construct(?int $userId, string $type, string $status = '', ?string $details = null)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->status = $status;
        $this->details = $details;
        $this->createdAt = new DateTime('now', new \DateTimeZone('+02:00'));
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
