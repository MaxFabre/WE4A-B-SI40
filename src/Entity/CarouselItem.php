<?php

namespace App\Entity;

use App\Repository\CarouselItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarouselItemRepository::class)]
class CarouselItem {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Film $film = null;

    #[ORM\Column]
    private ?int $position = null;

    public function getId(): ?int {
        return $this->id;
    }

    public function getFilm(): ?Film {
        return $this->film;
    }

    public function setFilm(Film $film): static {
        $this->film = $film;

        return $this;
    }

    public function getPosition(): ?int {
        return $this->position;
    }

    public function setPosition(int $position): static {
        $this->position = $position;

        return $this;
    }
}
