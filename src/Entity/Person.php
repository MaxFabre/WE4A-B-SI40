<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\Image;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
#[Uploadable]
class Person {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $firstname = null;

    #[ORM\Column(length: 50)]
    private ?string $lastname = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $birthdate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[UploadableField(mapping: 'profil_pictures', fileNameProperty: 'photo')]
    #[Image]
    private ?File $photoFile = null;

    public function getId(): ?int {
        return $this->id;
    }

    public function getFirstname(): ?string {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static {
        $this->lastname = $lastname;

        return $this;
    }

    public function getBirthdate(): ?\DateTime {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTime $birthdate): static {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getPhoto(): ?string {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static {
        $this->photo = $photo;

        return $this;
    }

    public function getPhotoFile(): ?File {
        return $this->photoFile;
    }

    public function setPhotoFile(?File $photoFile): static {
        $this->photoFile = $photoFile;
        return $this;
    }
}
