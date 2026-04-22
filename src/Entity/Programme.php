<?php

namespace App\Entity;

use App\Repository\ProgrammeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgrammeRepository::class)]
class Programme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $date = null;

    #[ORM\ManyToOne(inversedBy: 'programmes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'programme')]
    private Collection $reservations;

    #[ORM\ManyToOne(inversedBy: 'programmes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Film $film = null;

    #[ORM\ManyToOne(inversedBy: 'programmes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lang $lang = null;

    public function __construct() {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getDate(): ?\DateTime {
        return $this->date;
    }

    public function setDate(\DateTime $date): static {
        $this->date = $date;

        return $this;
    }

    public function getRoom(): ?Room {
        return $this->room;
    }

    public function setRoom(?Room $room): static {
        $this->room = $room;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setProgramme($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getProgramme() === $this) {
                $reservation->setProgramme(null);
            }
        }

        return $this;
    }

    public function getFilm(): ?Film {
        return $this->film;
    }

    public function setFilm(?Film $film): static {
        $this->film = $film;

        return $this;
    }

    public function getLang(): ?Lang {
        return $this->lang;
    }

    public function setLang(?Lang $lang): static {
        $this->lang = $lang;

        return $this;
    }
}
