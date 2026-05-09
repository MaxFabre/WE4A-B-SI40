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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
     * @var Collection<int, Genre>
     */
    #[ORM\ManyToMany(targetEntity: Genre::class, mappedBy: 'films')]
    private Collection $genres;

    /**
     * @var Collection<int, Programme>
     */
    #[ORM\OneToMany(targetEntity: Programme::class, mappedBy: 'film')]
    private Collection $programmes;

    /**
     * @var Collection<int, Person>
     */
    #[ORM\ManyToMany(targetEntity: Person::class, inversedBy: 'directedFilms')]
    #[ORM\JoinTable(name: "film_director")]
    private Collection $directors;

    /**
     * @var Collection<int, Person>
     */
    #[ORM\ManyToMany(targetEntity: Person::class, inversedBy: 'playedFilms')]
    #[ORM\JoinTable(name: "film_actor")]
    private Collection $actors;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'film', orphanRemoval: true)]
    private Collection $comments;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;


    public function __construct() {
        $this->genres = new ArrayCollection();
        $this->programmes = new ArrayCollection();
        $this->directors = new ArrayCollection();
        $this->actors = new ArrayCollection();
        $this->comments = new ArrayCollection();
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
    #[Assert\Callback]
    public function validatePrice(ExecutionContextInterface $context): void {//Fonction appelée automatiquement en cas de validation d'un formulaire qui va set un price. ça affichera le message d'erreur jsute en dessous du champ.
        if ($this->price === null || $this->price === '') {
            return;
        }

        $precision = 4; // Set plus haut dans la taille de la donnée
        $scale = 2;      // Same

        $value = (string) $this->price;
        // On gère les différents séparateurs
        $value = str_replace(',', '.', $value);

        if (!preg_match('/^-?\d+(\.\d+)?$/', $value)) { // Si ça ne commence pas
            $context->buildViolation('Le prix doit être un nombre valide.')
                ->atPath('price')
                ->addViolation();
            return;
        }

        $negative = $value[0] === '-'; // Si jamais y a un -, on l'enlève
        if ($negative) {
            $value = substr($value, 1);
        }

        [$intPart, $decPart] = array_pad(explode('.', $value, 2), 2, '');
        $intPart = ltrim($intPart, '0');
        $intDigits = $intPart === '' ? 0 : strlen($intPart);
        $decDigits = strlen(rtrim($decPart, '0'));

        $maxIntDigits = $precision - $scale;
        if ($intDigits > $maxIntDigits) {
            $context->buildViolation(sprintf('La partie entière du prix est trop longue (max %d chiffres).', $maxIntDigits))
                ->atPath('price')
                ->addViolation();
        }

        if ($decDigits > $scale) {
            $context->buildViolation(sprintf('Le prix ne doit pas avoir plus de %d décimales.', $scale))
                ->atPath('price')
                ->addViolation();
        }
    }

    public function getCoverPath(): ?string {
        return $this->coverPath;
    }

    public function setCoverPath(?string $cover_path): static {
        $this->coverPath = $cover_path;

        return $this;
    }

    #[Assert\Callback]
    public function validateCoverPath(ExecutionContextInterface $context): void { //Fonction appelée automatiquement en cas de validation d'un formulaire qui va set un price. ça affichera le message d'erreur jsute en dessous du champ.

        $maxSize = 255;
        $value = (string) $this->coverPath;

        if (strlen($value) > $maxSize) {
            $context->buildViolation(sprintf('Le chemin du cover est trop long (max %d caractères).', $maxSize))
                ->atPath('coverPath')
                ->addViolation();
            return;
        }
    }

    public function getCoverFile(): ?File {
        return $this->coverFile;
    }

    public function setCoverFile(?File $photoFile): static {
        $this->coverFile = $photoFile;

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getgenre(): Collection {
        return $this->genres;
    }

    public function addgenre(Genre $genre): static {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
            $genre->addFilm($this);
        }

        return $this;
    }

    public function removegenre(Genre $genre): static {
        if ($this->genres->removeElement($genre)) {
            $genre->removeTest($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Programme>
     */
    public function getProgrammes(): Collection {
        return $this->programmes;
    }

    public function addProgramme(Programme $programme): static {
        if (!$this->programmes->contains($programme)) {
            $this->programmes->add($programme);
            $programme->setFilm($this);
        }

        return $this;
    }

    public function removeProgramme(Programme $programme): static {
        if ($this->programmes->removeElement($programme)) {
            // set the owning side to null (unless already changed)
            if ($programme->getFilm() === $this) {
                $programme->setFilm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Person>
     */
    public function getDirectors(): Collection {
        return $this->directors;
    }

    public function addDirector(Person $director): static {
        if (!$this->directors->contains($director)) {
            $this->directors->add($director);
        }

        return $this;
    }

    public function removeDirector(Person $director): static {
        $this->directors->removeElement($director);

        return $this;
    }

    /**
     * @return Collection<int, Person>
     */
    public function getActors(): Collection {
        return $this->actors;
    }

    public function addActor(Person $actor): static {
        if (!$this->actors->contains($actor)) {
            $this->actors->add($actor);
        }

        return $this;
    }

    public function removeActor(Person $actor): static {
        $this->actors->removeElement($actor);

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setFilm($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getFilm() === $this) {
                $comment->setFilm(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeImmutable $created_at): static {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static {
        $this->updated_at = $updated_at;

        return $this;
    }
}
