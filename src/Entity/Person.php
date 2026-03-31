<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Date;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
class Person {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_human = null;

    #[ORM\Column]
    private ?string $firstName = null;

    #[ORM\Column]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?date $birthDate = null;

    #[ORM\Column]
    private ?string $string = null;

    #[ORM\Column]
    public function getId(): ?int {
        return $this->id_human;
    }
}
