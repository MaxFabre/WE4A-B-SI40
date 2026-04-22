<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Programme $programme = null;

    /**
     * @var Collection<int, Seat>
     */
    #[ORM\ManyToMany(targetEntity: Seat::class, inversedBy: 'reservations')]
    private Collection $seats;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Basket $basket = null;

    public function __construct() {
        $this->seats = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getProgramme(): ?Programme {
        return $this->programme;
    }

    public function setProgramme(?Programme $programme): static {
        $this->programme = $programme;

        return $this;
    }

    /**
     * @return Collection<int, Seat>
     */
    public function getSeats(): Collection {
        return $this->seats;
    }

    public function addSeat(Seat $seat): static {
        if (!$this->seats->contains($seat)) {
            $this->seats->add($seat);
        }

        return $this;
    }

    public function removeSeat(Seat $seat): static {
        $this->seats->removeElement($seat);

        return $this;
    }

    public function getBasket(): ?Basket
    {
        return $this->basket;
    }

    public function setBasket(?Basket $basket): static
    {
        $this->basket = $basket;

        return $this;
    }
}
