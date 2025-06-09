<?php

namespace App\Entity;

use App\Repository\TrajetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(
        [
            'reservation:read',
            'historique:read',
            'trajet:read',
            'user:read',
            'avis:read'
        ]
    )]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups([
        'trajet:read',
        'reservation:read',
        'avis:read'
    ])]
    private ?string $adresseDepart = null;

    #[ORM\Column(length: 255)]
    #[Groups([
        'trajet:read',
        'reservation:read',
        'avis:read'
    ])]
    private ?string $adresseArrivee = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups([
        'trajet:read',
        'reservation:read',
        'avis:read'
    ])]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups([
        'trajet:read',
        'reservation:read',
        'avis:read'
    ])]
    private ?\DateTimeInterface $dateArrivee = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups([
        'trajet:read',
        'reservation:read',
        'avis:read'
    ])]
    private ?\DateTimeInterface $heureDepart = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups([
        'trajet:read',
        'reservation:read',
        'avis:read'
    ])]
    private ?\DateTimeInterface $dureeVoyage = null;

    #[ORM\Column]
    #[Groups([
        'trajet:read',
        'reservation:read',
        'avis:read'
    ])]
    private ?bool $peage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups([
        'trajet:read',
        'reservation:read',
        'avis:read'
    ])]
    private ?string $prix = null;

    #[ORM\Column]
    #[Groups([
        'trajet:read',
        'reservation:read',
        'avis:read'
    ])]
    private ?bool $estEcologique = null;

    #[ORM\Column]
    #[Groups(
        [
            'trajet:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?int $nombrePlacesDisponible = null;

    #[Assert\Choice(
        choices: [
            'EN_ATTENTE',
            'EN_COURS',
            'TERMINEE',
        ],
        message: 'Le statut doit être "EN_ATTENTE", "EN_COURS" ou "TERMINEE".'
    )]
    #[ORM\Column(length: 255)]
    #[Groups(
        [
            'reservation:read',
            'historique:read',
            'trajet:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?string $statut = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(
        targetEntity: Reservation::class,
        mappedBy: 'trajet',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $reservations;

    /**
     * @var Collection<int, Historique>
     */
    #[ORM\OneToMany(targetEntity: Historique::class, mappedBy: 'trajet')]
    private Collection $historiques;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'trajet')]
    private Collection $users;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->historiques = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    // Méthode pour compter le nombre de passagers (excluant le chauffeur)
    public function getNombrePassagers(): int
    {
        return $this->users->count() - 1; // En supposant que le chauffeur est inclus dans la collection users
    }

    // Méthode pour compter le nombre de chauffeurs
    public function getNombreChauffeurs(): int
    {
        // Supposons que le chauffeur est l'utilisateur créé ce trajet (ou assigné explicitement)
        return $this->users->contains($this->getChauffeur()) ? 1 : 0;
    }

    // Méthode pour compter le nombre de passagers-chauffeurs
    public function getNombrePassagersChauffeurs(): int
    {
        $count = 0;

        foreach ($this->users as $user) {
            if ($user->hasRole('ROLE_CHAUFFEUR') && $this->isPassager($user)) {
                $count++;
            }
        }

        return $count;
    }

    // Vérifier si un utilisateur est un passager
    private function isPassager(User $user): bool
    {
        return $this->users->contains($user);
    }

    // Si tu veux ajouter un chauffeur spécifique
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(
        [
            'trajet:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?User $chauffeur = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(
        [
            'trajet:read',
            'trajetChoisi:read',
            'reservation:read',
            'avis:read'
        ]
    )]
    private ?ProfilConducteur $vehicule = null;

    public function getChauffeur(): ?User
    {
        return $this->chauffeur;
    }

    public function setChauffeur(User $chauffeur): static
    {
        $this->chauffeur = $chauffeur;
        return $this;
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

    public function getHeureDepart(): ?\DateTimeInterface
    {
        return $this->heureDepart;
    }

    public function setHeureDepart(\DateTimeInterface $heureDepart): static
    {
        $this->heureDepart = $heureDepart;

        return $this;
    }

    public function getDureeVoyage(): ?\DateTimeInterface
    {
        return $this->dureeVoyage;
    }

    public function setDureeVoyage(\DateTimeInterface $dureeVoyage): static
    {
        $this->dureeVoyage = $dureeVoyage;

        return $this;
    }

    public function isPeage(): ?bool
    {
        return $this->peage;
    }

    public function setPeage(bool $peage): static
    {
        $this->peage = $peage;

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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addTrajet($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeTrajet($this);
        }

        return $this;
    }

    public function getVehicule(): ?ProfilConducteur
    {
        return $this->vehicule;
    }

    public function setVehicule(ProfilConducteur $vehicule): static
    {
        $this->vehicule = $vehicule;

        return $this;
    }
}
