<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['trajet:read', 'reservation:read', 'historique:read', 'profilConducteur:read', 'employes:read', 'admin:read', 'avis:read'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Veuillez renseigner un email.')]
    #[Assert\Email(message: 'Veuillez renseigner un email valide.')]
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[Assert\NotBlank]
    #[Assert\All([
        new Assert\Choice(
            choices: [
                'ROLE_PASSAGER',
                'ROLE_CHAUFFEUR',
                'ROLE_PASSAGER_CHAUFFEUR',
            ],
            message: 'Choisissez un rôle valide.'
        )
    ])]
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[Assert\NotBlank(message: 'Veuillez renseigner un mot de passe.')]
    #[Assert\Length(min: 8, minMessage: "Le mot de passe n'est pas conforme !
          Au moins 8 caractères comprenant: 
          1 lettre majuscule,
          1 lettre miniscule,
          1chiffre et 1 caractères sepéciale.")]
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $apiToken = null;

    #[Assert\NotBlank(message: 'Veuillez renseigner un pseudo.')]
    #[Assert\Length(min: 2, max: 50)]
    #[ORM\Column(length: 50)]
    #[Groups(
        [
            'trajet:read',
            'reservation:read',
            'historique:read',
            'profilConducteur:read',
            'employes:read',
            'admin:read',
            'avis:read'
        ]
    )]
    private ?string $pseudo = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $dateNaissance = null;

    #[ORM\Column(nullable: true)]
    private ?int $credits = null;

    /**
     * @var Collection<int, Historique>
     */
    #[ORM\OneToMany(targetEntity: Historique::class, mappedBy: 'user')]
    private Collection $historiques;

    /**
     * @var Collection<int, ProfilConducteur>
     */
    #[ORM\OneToMany(targetEntity: ProfilConducteur::class, mappedBy: 'user')]
    private Collection $profilConducteurs;

    /**
     * @var Collection<int, Avis>
     */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'user')]
    private Collection $avis;

    #[ORM\Column]
    private ?bool $compteSuspendu = null;

    /**
     * @var Collection<int, Trajet>
     */
    #[ORM\ManyToMany(targetEntity: Trajet::class, inversedBy: 'users')]
    private Collection $trajet;

    #[ORM\Column]
    private ?bool $isPassager = null;

    #[ORM\Column]
    private ?bool $isChauffeur = null;

    #[ORM\Column]
    private ?bool $isPassagerChauffeur = null;

    /** @throws \Exception */
    public function __construct()
    {
        $this->apiToken = bin2hex(random_bytes(50));
        $this->historiques = new ArrayCollection();
        $this->profilConducteurs = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->trajet = new ArrayCollection();
    }

    private function updateRoleBooleans(): void
    {
        $this->isPassager = in_array('ROLE_PASSAGER', $this->roles);
        $this->isChauffeur = in_array('ROLE_CHAUFFEUR', $this->roles);
        $this->isPassagerChauffeur = in_array('ROLE_PASSAGER_CHAUFFEUR', $this->roles);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        $this->updateRoleBooleans();

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(string $apiToken): static
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getDateNaissance(): ?string
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(string $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits ?? 0;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function addCredits(int $amount): self
    {
        $this->credits = ($this->credits ?? 0) + $amount;
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
            $historique->setUser($this);
        }

        return $this;
    }

    public function removeHistorique(Historique $historique): static
    {
        if ($this->historiques->removeElement($historique)) {
            // set the owning side to null (unless already changed)
            if ($historique->getUser() === $this) {
                $historique->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProfilConducteur>
     */
    public function getProfilConducteurs(): Collection
    {
        return $this->profilConducteurs;
    }

    public function addProfilConducteur(ProfilConducteur $profilConducteur): static
    {
        if (!$this->profilConducteurs->contains($profilConducteur)) {
            $this->profilConducteurs->add($profilConducteur);
            $profilConducteur->setUser($this);
        }

        return $this;
    }

    public function removeProfilConducteur(ProfilConducteur $profilConducteur): static
    {
        if ($this->profilConducteurs->removeElement($profilConducteur)) {
            // set the owning side to null (unless already changed)
            if ($profilConducteur->getUser() === $this) {
                $profilConducteur->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Avis>
     */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function addAvi(Avis $avi): static
    {
        if (!$this->avis->contains($avi)) {
            $this->avis->add($avi);
            $avi->setUser($this);
        }

        return $this;
    }

    public function removeAvi(Avis $avi): static
    {
        if ($this->avis->removeElement($avi)) {
            // set the owning side to null (unless already changed)
            if ($avi->getUser() === $this) {
                $avi->setUser(null);
            }
        }

        return $this;
    }

    public function isCompteSuspendu(): ?bool
    {
        return $this->compteSuspendu;
    }

    public function setCompteSuspendu(bool $compteSuspendu): static
    {
        $this->compteSuspendu = $compteSuspendu;

        return $this;
    }

    /**
     * @return Collection<int, Trajet>
     */
    public function getTrajet(): Collection
    {
        return $this->trajet;
    }

    public function addTrajet(Trajet $trajet): static
    {
        if (!$this->trajet->contains($trajet)) {
            $this->trajet->add($trajet);
        }

        return $this;
    }

    public function removeTrajet(Trajet $trajet): static
    {
        $this->trajet->removeElement($trajet);

        return $this;
    }

    public function isPassager(): ?bool
    {
        return $this->isPassager;
    }

    public function setIsPassager(bool $isPassager): static
    {
        $this->isPassager = $isPassager;

        return $this;
    }

    public function isChauffeur(): ?bool
    {
        return $this->isChauffeur;
    }

    public function setIsChauffeur(bool $isChauffeur): static
    {
        $this->isChauffeur = $isChauffeur;

        return $this;
    }

    public function isPassagerChauffeur(): ?bool
    {
        return $this->isPassagerChauffeur;
    }

    public function setIsPassagerChauffeur(bool $isPassagerChauffeur): static
    {
        $this->isPassagerChauffeur = $isPassagerChauffeur;

        return $this;
    }
}
