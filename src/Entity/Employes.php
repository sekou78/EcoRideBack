<?php

namespace App\Entity;

use App\Repository\EmployesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployesRepository::class)]
class Employes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $validationAvis = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isValidationAvis(): ?bool
    {
        return $this->validationAvis;
    }

    public function setValidationAvis(bool $validationAvis): static
    {
        $this->validationAvis = $validationAvis;

        return $this;
    }
}
