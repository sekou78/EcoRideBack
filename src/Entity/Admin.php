<?php

namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
class Admin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $droitsSuspension = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isDroitsSuspension(): ?bool
    {
        return $this->droitsSuspension;
    }

    public function setDroitsSuspension(bool $droitsSuspension): static
    {
        $this->droitsSuspension = $droitsSuspension;

        return $this;
    }
}
