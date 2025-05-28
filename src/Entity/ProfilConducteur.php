<?php

namespace App\Entity;

use App\Repository\ProfilConducteurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProfilConducteurRepository::class)]
class ProfilConducteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read'
        ]
    )]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read'
        ]
    )]
    private ?string $plaqueImmatriculation = null;

    #[ORM\Column]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read'
        ]
    )]
    private ?\DateTimeImmutable $dateImmatriculation = null;

    #[ORM\Column(length: 50)]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read'
        ]
    )]
    private ?string $modele = null;

    #[ORM\Column(length: 50)]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read'
        ]
    )]
    private ?string $marque = null;

    #[ORM\Column(length: 20)]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read'
        ]
    )]
    private ?string $couleur = null;

    #[ORM\Column]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read'
        ]
    )]
    private ?int $nombrePlaces = null;

    #[ORM\Column]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read'
        ]
    )]
    private ?bool $electrique = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'profilConducteurs')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlaqueImmatriculation(): ?string
    {
        return $this->plaqueImmatriculation;
    }

    public function setPlaqueImmatriculation(string $plaqueImmatriculation): static
    {
        $this->plaqueImmatriculation = $plaqueImmatriculation;

        return $this;
    }

    public function getDateImmatriculation(): ?\DateTimeImmutable
    {
        return $this->dateImmatriculation;
    }

    public function setDateImmatriculation(\DateTimeImmutable $dateImmatriculation): static
    {
        $this->dateImmatriculation = $dateImmatriculation;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(string $marque): static
    {
        $this->marque = $marque;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getNombrePlaces(): ?int
    {
        return $this->nombrePlaces;
    }

    public function setNombrePlaces(int $nombrePlaces): static
    {
        $this->nombrePlaces = $nombrePlaces;

        return $this;
    }

    public function isElectrique(): ?bool
    {
        return $this->electrique;
    }

    public function setElectrique(bool $electrique): static
    {
        $this->electrique = $electrique;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
