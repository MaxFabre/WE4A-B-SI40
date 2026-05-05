<?php

namespace App\Entity;

use App\Repository\CommentReportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentReportRepository::class)]
class CommentReport {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commentReports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Comment $comment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $complainant = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?bool $is_active = null;

    #[ORM\Column(length: 64)]
    private ?string $statut = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    private Collection $moderator;

    public function __construct() {
        $this->moderator = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static {
        $this->created_at = $created_at;

        return $this;
    }

    public function isActive(): ?bool {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static {
        $this->is_active = $is_active;

        return $this;
    }

    public function getStatut(): ?string {
        return $this->statut;
    }

    public function setStatut(string $statut): static {
        $this->statut = $statut;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getModerator(): Collection {
        return $this->moderator;
    }

    public function addModerator(User $moderator): static {
        if (!$this->moderator->contains($moderator)) {
            $this->moderator->add($moderator);
        }

        return $this;
    }

    public function removeModerator(User $moderator): static {
        $this->moderator->removeElement($moderator);

        return $this;
    }

    public function getComment(): ?Comment {
        return $this->comment;
    }

    public function setComment(?Comment $comment): static {
        $this->comment = $comment;

        return $this;
    }

    public function getComplainant(): ?User {
        return $this->complainant;
    }

    public function setComplainant(?User $complainant): static {
        $this->complainant = $complainant;

        return $this;
    }
}
