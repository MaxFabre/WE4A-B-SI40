<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $capacity = null;

    /**
     * @var Collection<int, Seat>
     */
    #[ORM\OneToMany(targetEntity: Seat::class, mappedBy: 'room')]
    private Collection $seats;

    /**
     * @var Collection<int, Programme>
     */
    #[ORM\OneToMany(targetEntity: Programme::class, mappedBy: 'room')]
    private Collection $programmes;

    public function __construct() {
        $this->seats = new ArrayCollection();
        $this->programmes = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): static {
        $this->name = $name;

        return $this;
    }

    public function getCapacity(): ?int {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static {
        $this->capacity = $capacity;

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
            $seat->setRoom($this);
        }

        return $this;
    }

    public function removeSeat(Seat $seat): static {
        if ($this->seats->removeElement($seat)) {
            // set the owning side to null (unless already changed)
            if ($seat->getRoom() === $this) {
                $seat->setRoom(null);
            }
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
            $programme->setRoom($this);
        }

        return $this;
    }

    public function removeProgramme(Programme $programme): static {
        if ($this->programmes->removeElement($programme)) {
            // set the owning side to null (unless already changed)
            if ($programme->getRoom() === $this) {
                $programme->setRoom(null);
            }
        }

        return $this;
    }
}
