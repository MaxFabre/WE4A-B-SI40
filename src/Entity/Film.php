<?php

namespace App\Entity;

use App\Repository\FilmRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\Image;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

#[ORM\Entity(repositoryClass: FilmRepository::class)]
#[Uploadable]
class Film {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(length: 60)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverPath = null;

    #[UploadableField(mapping: 'films_cover', fileNameProperty: 'coverpath')]
    #[Image]
    private ?File $coverFile = null;

    /**
     * @var Collection<int, Gender>
     */
    #[ORM\ManyToMany(targetEntity: Gender::class, mappedBy: 'films')]
    private Collection $genders;

    public function __construct() {
        $this->genders = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(string $title): static {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): static {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): ?string {
        return $this->slug;
    }

    public function setSlug(string $slug): static {
        $this->slug = $slug;

        return $this;
    }

    public function getDuration(): ?int {
        return $this->duration;
    }

    public function setDuration(?int $duration): static {
        $this->duration = $duration;

        return $this;
    }

    public function getPrice(): ?string {
        return $this->price;
    }

    public function setPrice(?string $price): static {
        $this->price = $price;

        return $this;
    }

    public function getCoverPath(): ?string {
        return $this->coverPath;
    }

    public function setCoverPath(?string $cover_path): static {
        $this->coverPath = $cover_path;

        return $this;
    }

    public function getCoverFile(): ?File {
        return $this->coverFile;
    }

    public function setCoverFile(?File $photoFile): static {
        $this->coverFile = $photoFile;

        return $this;
    }

    /**
     * @return Collection<int, Gender>
     */
    public function getGender(): Collection {
        return $this->genders;
    }

    public function addGender(Gender $gender): static
    {
        if (!$this->genders->contains($gender)) {
            $this->genders->add($gender);
            $gender->addFilm($this);
        }

        return $this;
    }

    public function removeGender(Gender $gender): static
    {
        if ($this->genders->removeElement($gender)) {
            $gender->removeTest($this);
        }

        return $this;
    }
}
