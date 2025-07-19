<?php

namespace App\Entity;

use App\Repository\ProfilConducteurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
            'trajetChoisi:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?string $plaqueImmatriculation = null;

    #[ORM\Column]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?\DateTimeImmutable $dateImmatriculation = null;

    #[ORM\Column(length: 50)]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?string $modele = null;

    #[ORM\Column(length: 50)]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?string $marque = null;

    #[ORM\Column(length: 20)]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?string $couleur = null;

    #[ORM\Column]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?int $nombrePlaces = null;

    #[ORM\Column]
    #[Groups(
        [
            'profilConducteur:read',
            'trajet:read',
            'trajetChoisi:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?bool $electrique = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'profilConducteurs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\OneToMany(
        mappedBy: 'vehicule',
        targetEntity: Trajet::class,
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $trajets;

    public function __construct()
    {
        $this->trajets = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Trajet>
     */
    public function getTrajets(): Collection
    {
        return $this->trajets;
    }

    public function addTrajet(Trajet $trajet): self
    {
        if (!$this->trajets->contains($trajet)) {
            $this->trajets[] = $trajet;
            $trajet->setVehicule($this);
        }

        return $this;
    }

    public function removeTrajet(Trajet $trajet): self
    {
        if ($this->trajets->removeElement($trajet)) {
            // set the owning side to null (unless already changed)
            if ($trajet->getVehicule() === $this) {
                $trajet->setVehicule(null);
            }
        }

        return $this;
    }
}
