<?php

namespace App\Entity;

use App\Repository\CarouselItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CarouselItemRepository::class)]
class CarouselItem {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['film.pined'])]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['film.pined'])]
    private ?Film $film = null;

    #[ORM\Column]
    #[Groups(['film.pined'])]
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
