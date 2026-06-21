<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\Image;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;
use App\Entity\User;
use App\Entity\Film;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
#[Uploadable]
class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['film.details', 'user.details', 'personality.search', 'personality.details', 'user.profile', 'user.list'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['film.details', 'user.details', 'personality.search', 'personality.details', 'user.profile', 'user.list'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 50)]
    #[Groups(['film.details', 'user.details', 'personality.search', 'personality.details', 'user.profile', 'user.list'])]
    private ?string $lastname = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['user.details', 'personality.details'])]
    private ?\DateTime $birthdate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user.details', 'personality.search', 'personality.details', 'user.profile'])]
    private ?string $photo = null;

    #[UploadableField(mapping: 'profil_pictures', fileNameProperty: 'photo')]
    #[Image]
    private ?File $photoFile = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $uploadedBy = null;

    /**
     * @var Collection<int, Film>
     */
    #[ORM\ManyToMany(targetEntity: Film::class, mappedBy: 'directors')]
    #[Groups(['personality.details'])]
    private Collection $directedFilms;

    /**
     * @var Collection<int, Film>
     */
    #[ORM\ManyToMany(targetEntity: Film::class, mappedBy: 'actors')]
    #[Groups(['personality.details'])]
    private Collection $playedFilms;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user.profile', 'user.list', 'personality.details'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user.list', 'personality.details'])]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->directedFilms = new ArrayCollection();
        $this->playedFilms = new ArrayCollection();
        // si tu veux initialiser created_at/updated_at par défaut, fais-le ici :
        // $this->created_at = new \DateTimeImmutable();
        // $this->updated_at = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getFirstname(): ?string { return $this->firstname; }
    public function setFirstname(string $firstname): static { $this->firstname = $firstname; return $this; }

    public function getLastname(): ?string { return $this->lastname; }
    public function getFullName(): string { return ($this->firstname ?? '') . ' ' . ($this->lastname ?? ''); }
    public function setLastname(string $lastname): static { $this->lastname = $lastname; return $this; }

    public function getBirthdate(): ?\DateTime { return $this->birthdate; }
    public function setBirthdate(?\DateTime $birthdate): static { $this->birthdate = $birthdate; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): static { $this->photo = $photo; return $this; }

    public function getPhotoFile(): ?File { return $this->photoFile; }
    public function setPhotoFile(?File $photoFile): static { $this->photoFile = $photoFile; return $this; }

    public function getUploadedBy(): ?User { return $this->uploadedBy; }
    public function setUploadedBy(?User $uploadedBy): static { $this->uploadedBy = $uploadedBy; return $this; }

    public function __serialize(): array {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'birthdate' => $this->birthdate,
            'photo' => $this->photo
        ];
    }

    public function __unserialize(array $data): void {
        $this->id = $data['id'] ?? null;
        $this->firstname = $data['firstname'] ?? null;
        $this->lastname = $data['lastname'] ?? null;
        $this->birthdate = $data['birthdate'] ?? null;
        $this->photo = $data['photo'] ?? null;
        $this->photoFile = null;
    }

    public function getDirectedFilms(): Collection { return $this->directedFilms; }
    public function addDirectedFilm(Film $directedFilm): static {
        if (!$this->directedFilms->contains($directedFilm)) {
            $this->directedFilms->add($directedFilm);
            $directedFilm->addDirector($this);
        }
        return $this;
    }
    public function removeDirectedFilm(Film $directedFilm): static {
        if ($this->directedFilms->removeElement($directedFilm)) {
            $directedFilm->removeDirector($this);
        }
        return $this;
    }

    public function getPlayedFilms(): Collection { return $this->playedFilms; }
    public function addPlayedFilm(Film $playedFilm): static {
        if (!$this->playedFilms->contains($playedFilm)) {
            $this->playedFilms->add($playedFilm);
            $playedFilm->addActor($this);
        }
        return $this;
    }
    public function removePlayedFilm(Film $playedFilm): static {
        if ($this->playedFilms->removeElement($playedFilm)) {
            $playedFilm->removeActor($this);
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->created_at; }
    public function setCreatedAt(?\DateTimeImmutable $created_at): static { $this->created_at = $created_at; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updated_at; }
    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static { $this->updated_at = $updated_at; return $this; }
}
