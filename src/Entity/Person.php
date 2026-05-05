<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /**
     * @var Collection<int, Film>
     */
    #[ORM\ManyToMany(targetEntity: Film::class, mappedBy: 'directors')]
    private Collection $directedFilms;

    /**
     * @var Collection<int, Film>
     */
    #[ORM\ManyToMany(targetEntity: Film::class, mappedBy: 'actors')]
    private Collection $playedFilms;

    public function __construct()
    {
        $this->directedFilms = new ArrayCollection();
        $this->playedFilms = new ArrayCollection();
    }

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
        $this->id = $data['id'];
        $this->firstname = $data['firstname'];
        $this->lastname = $data['lastname'];
        $this->birthdate = $data['birthdate'];
        $this->photo = $data['photo'];
        $this->photoFile = null;
    }

    /**
     * @return Collection<int, Film>
     */
    public function getDirectedFilms(): Collection
    {
        return $this->directedFilms;
    }

    public function addDirectedFilm(Film $directedFilm): static
    {
        if (!$this->directedFilms->contains($directedFilm)) {
            $this->directedFilms->add($directedFilm);
            $directedFilm->addDirector($this);
        }

        return $this;
    }

    public function removeDirectedFilm(Film $directedFilm): static
    {
        if ($this->directedFilms->removeElement($directedFilm)) {
            $directedFilm->removeDirector($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Film>
     */
    public function getPlayedFilms(): Collection
    {
        return $this->playedFilms;
    }

    public function addPlayedFilm(Film $playedFilm): static
    {
        if (!$this->playedFilms->contains($playedFilm)) {
            $this->playedFilms->add($playedFilm);
            $playedFilm->addActor($this);
        }

        return $this;
    }

    public function removePlayedFilm(Film $playedFilm): static
    {
        if ($this->playedFilms->removeElement($playedFilm)) {
            $playedFilm->removeActor($this);
        }

        return $this;
    }
}
