<?php

namespace App\Entity;

use App\Repository\TrajetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reservation:read', 'historique:read', 'trajet:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['trajet:read'])]
    private ?string $adresseDepart = null;

    #[ORM\Column(length: 255)]
    #[Groups(['trajet:read'])]
    private ?string $adresseArrivee = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['trajet:read'])]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['trajet:read'])]
    private ?\DateTimeInterface $dateArrivee = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['trajet:read'])]
    private ?string $prix = null;

    #[ORM\Column]
    #[Groups(['trajet:read'])]
    private ?bool $estEcologique = null;

    #[ORM\Column]
    #[Groups(['trajet:read'])]
    private ?int $nombrePlacesDisponible = null;

    #[ORM\Column(length: 255)]
    #[Groups(['reservation:read', 'historique:read', 'trajet:read'])]
    private ?string $statut = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'trajet')]
    private Collection $reservations;

    /**
     * @var Collection<int, Historique>
     */
    #[ORM\OneToMany(targetEntity: Historique::class, mappedBy: 'trajet')]
    #[Groups(['historique:read'])]
    private Collection $historiques;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'trajets')]
    #[Groups(['trajet:read'])]
    private ?User $user = null;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->historiques = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdresseDepart(): ?string
    {
        return $this->adresseDepart;
    }

    public function setAdresseDepart(string $adresseDepart): static
    {
        $this->adresseDepart = $adresseDepart;

        return $this;
    }

    public function getAdresseArrivee(): ?string
    {
        return $this->adresseArrivee;
    }

    public function setAdresseArrivee(string $adresseArrivee): static
    {
        $this->adresseArrivee = $adresseArrivee;

        return $this;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(\DateTimeInterface $dateDepart): static
    {
        $this->dateDepart = $dateDepart;

        return $this;
    }

    public function getDateArrivee(): ?\DateTimeInterface
    {
        return $this->dateArrivee;
    }

    public function setDateArrivee(\DateTimeInterface $dateArrivee): static
    {
        $this->dateArrivee = $dateArrivee;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function isEstEcologique(): ?bool
    {
        return $this->estEcologique;
    }

    public function setEstEcologique(bool $estEcologique): static
    {
        $this->estEcologique = $estEcologique;

        return $this;
    }

    public function getNombrePlacesDisponible(): ?int
    {
        return $this->nombrePlacesDisponible;
    }

    public function setNombrePlacesDisponible(int $nombrePlacesDisponible): static
    {
        $this->nombrePlacesDisponible = $nombrePlacesDisponible;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setTrajet($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getTrajet() === $this) {
                $reservation->setTrajet(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Historique>
     */
    public function getHistoriques(): Collection
    {
        return $this->historiques;
    }

    public function addHistorique(Historique $historique): static
    {
        if (!$this->historiques->contains($historique)) {
            $this->historiques->add($historique);
            $historique->setTrajet($this);
        }

        return $this;
    }

    public function removeHistorique(Historique $historique): static
    {
        if ($this->historiques->removeElement($historique)) {
            // set the owning side to null (unless already changed)
            if ($historique->getTrajet() === $this) {
                $historique->setTrajet(null);
            }
        }

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
