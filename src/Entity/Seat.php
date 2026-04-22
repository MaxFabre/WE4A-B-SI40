<?php

namespace App\Entity;

use App\Repository\SeatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeatRepository::class)]
class Seat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $number = null;

    #[ORM\Column]
    private ?int $class = null;

    #[ORM\ManyToOne(inversedBy: 'seats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\ManyToMany(targetEntity: Reservation::class, mappedBy: 'seats')]
    private Collection $reservations;

    public function __construct() {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getNumber(): ?int {
        return $this->number;
    }

    public function setNumber(int $number): static {
        $this->number = $number;

        return $this;
    }

    public function getClass(): ?int {
        return $this->class;
    }

    public function setClass(int $class): static {
        $this->class = $class;

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
            $reservation->addSeat($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static {
        if ($this->reservations->removeElement($reservation)) {
            $reservation->removeSeat($this);
        }

        return $this;
    }
}
