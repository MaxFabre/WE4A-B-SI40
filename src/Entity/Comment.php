<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Film $film = null;

    #[ORM\Column(length: 64)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private ?float $note = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column]
    private ?bool $is_visible = null;

    /**
     * @var Collection<int, CommentReport>
     */
    #[ORM\OneToMany(targetEntity: CommentReport::class, mappedBy: 'comment', orphanRemoval: true)]
    private Collection $commentReports;

    public function __construct()
    {
        $this->commentReports = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getAuthor(): ?User {
        return $this->author;
    }

    public function setAuthor(?User $author): static {
        $this->author = $author;

        return $this;
    }

    public function getFilm(): ?Film {
        return $this->film;
    }

    public function setFilm(?Film $film): static {
        $this->film = $film;

        return $this;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(string $string): static {
        $this->title = $string;

        return $this;
    }

    public function getContent(): ?string {
        return $this->content;
    }

    public function setContent(string $content): static {
        $this->content = $content;

        return $this;
    }

    public function getNote(): ?float {
        return $this->note;
    }

    public function setNote(float $note): static {
        $this->note = $note;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function isVisible(): ?bool {
        return $this->is_visible;
    }

    public function setIsVisible(bool $is_visible): static {
        $this->is_visible = $is_visible;

        return $this;
    }

    /**
     * @return Collection<int, CommentReport>
     */
    public function getCommentReports(): Collection {
        return $this->commentReports;
    }

    public function addCommentReport(CommentReport $commentReport): static {
        if (!$this->commentReports->contains($commentReport)) {
            $this->commentReports->add($commentReport);
            $commentReport->setComment($this);
        }

        return $this;
    }

    public function removeCommentReport(CommentReport $commentReport): static {
        if ($this->commentReports->removeElement($commentReport)) {
            // set the owning side to null (unless already changed)
            if ($commentReport->getComment() === $this) {
                $commentReport->setComment(null);
            }
        }

        return $this;
    }
}
