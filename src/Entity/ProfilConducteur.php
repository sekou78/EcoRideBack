<?php

namespace App\Entity;

use App\Repository\ProfilConducteurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProfilConducteurRepository::class)]
class ProfilConducteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['profilConducteur:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Groups(['profilConducteur:read'])]
    private ?string $plaqueImmatriculation = null;

    #[ORM\Column(length: 50)]
    #[Groups(['profilConducteur:read'])]
    private ?string $modele = null;

    #[ORM\Column(length: 50)]
    #[Groups(['profilConducteur:read'])]
    private ?string $marque = null;

    #[ORM\Column(length: 20)]
    #[Groups(['profilConducteur:read'])]
    private ?string $couleur = null;

    #[ORM\Column]
    #[Groups(['profilConducteur:read'])]
    private ?int $nombrePlaces = null;

    #[ORM\Column]
    #[Groups(['profilConducteur:read'])]
    private ?bool $accepteFumeur = null;

    #[ORM\Column]
    #[Groups(['profilConducteur:read'])]
    private ?bool $accepteAnimaux = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['profilConducteur:read'])]
    private ?string $autresPreferences = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'profilConducteurs')]
    #[Groups(['profilConducteur:read'])]
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

    public function isAccepteFumeur(): ?bool
    {
        return $this->accepteFumeur;
    }

    public function setAccepteFumeur(bool $accepteFumeur): static
    {
        $this->accepteFumeur = $accepteFumeur;

        return $this;
    }

    public function isAccepteAnimaux(): ?bool
    {
        return $this->accepteAnimaux;
    }

    public function setAccepteAnimaux(bool $accepteAnimaux): static
    {
        $this->accepteAnimaux = $accepteAnimaux;

        return $this;
    }

    public function getAutresPreferences(): ?string
    {
        return $this->autresPreferences;
    }

    public function setAutresPreferences(?string $autresPreferences): static
    {
        $this->autresPreferences = $autresPreferences;

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
