<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: "trajets_archives")]
class TrajetArchive
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: "int")]
    private int $trajetId;

    #[MongoDB\Field(type: "hash")]
    private array $snapshot;

    #[MongoDB\Field(type: "date")]
    private \DateTime $archivedAt;

    public function __construct(int $trajetId, array $snapshot)
    {
        $this->trajetId = $trajetId;
        $this->snapshot = $snapshot;
        $this->archivedAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTrajetId(): int
    {
        return $this->trajetId;
    }

    public function getSnapshot(): array
    {
        return $this->snapshot;
    }

    public function setSnapshot(array $snapshot): void
    {
        $this->snapshot = $snapshot;
    }

    public function getArchivedAt(): \DateTime
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(\DateTime $archivedAt): void
    {
        $this->archivedAt = $archivedAt;
    }
}
