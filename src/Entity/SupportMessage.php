<?php

namespace App\Entity;

use App\Repository\SupportMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: SupportMessageRepository::class)]
class SupportMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est requis.')]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L’email est requis.')]
    #[Assert\Email(message: 'L’email "{{ value }}" est invalide.')]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le sujet est requis.')]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le message ne peut pas être vide.')]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null; // Nom du fichier uploadé, si applicable

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(
        choices: [
            'new',
            'read',
            'resolved'
        ],
        message: 'Le statut "{{ value }}" n’est pas valide.'
    )]
    private ?string $status = 'new'; // Statut par défaut

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $assignedTo = null;


    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Choice(
        choices: [
            'low',
            'normal',
            'high'
        ],
        message: 'La priorité "{{ value }}" est invalide.'
    )]
    private ?string $priority = 'normal';


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $updatedBy = null;

    #[ORM\OneToMany(
        targetEntity: SupportComment::class,
        mappedBy: 'supportMessage',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $supportComments;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    public function __construct()
    {
        $this->supportComments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getAssignedTo(): ?string
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?string $assignedTo): static
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return Collection<int, SupportComment>
     */
    public function getSupportComments(): Collection
    {
        return $this->supportComments;
    }

    public function addSupportComment(SupportComment $supportComment): static
    {
        if (!$this->supportComments->contains($supportComment)) {
            $this->supportComments->add($supportComment);
            $supportComment->setSupportMessage($this);
        }

        return $this;
    }

    public function removeSupportComment(SupportComment $supportComment): static
    {
        if ($this->supportComments->removeElement($supportComment)) {
            // set the owning side to null (unless already changed)
            if ($supportComment->getSupportMessage() === $this) {
                $supportComment->setSupportMessage(null);
            }
        }

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
