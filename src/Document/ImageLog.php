<?php
// src/Document/ImageLog.php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'images')]
class ImageLog
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $path = '';

    #[ODM\Field(type: 'string')]
    private string $name = '';

    #[ODM\Field(type: 'string')]
    private string $format = '';

    #[ODM\Field(type: 'string')]
    private string $size = '';

    #[ODM\Field(type: 'hash')]
    private array $uploader = [];

    #[ODM\Field(type: 'hash')]
    private ?array $film = null;

    #[ODM\Field(type: 'hash')]
    private ?array $person = null;

    #[ODM\Field(type: 'hash')]
    private ?array $user = null;

    public function __construct(array $data = [])
    {
        $this->path = $data['path'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->format = $data['format'] ?? '';
        $this->size = $data['size'] ?? '';
        $this->uploader = $data['uploader'] ?? [];
        $this->film = $data['film'] ?? null;
        $this->person = $data['person'] ?? null;
        $this->user = $data['user'] ?? null;
    }

    public function getId(): ?string { return $this->id; }

    public function getUser(): ?array { return $this->user; }
    public function setUser(?array $user): void { $this->user = $user; }

    public function getPerson(): ?array { return $this->person; }
    public function setPerson(?array $person): void { $this->person = $person; }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'name' => $this->name,
            'format' => $this->format,
            'size' => $this->size,
            'uploader' => $this->uploader,
            'user' => $this->user,
            'film' => $this->film,
            'person' => $this->person,
        ];
    }
}
