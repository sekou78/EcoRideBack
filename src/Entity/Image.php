<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::BLOB)]
    private $identite;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentite()
    {
        return $this->identite;
    }

    public function setIdentite($identite): static
    {
        $this->identite = $identite;

        return $this;
    }
}
