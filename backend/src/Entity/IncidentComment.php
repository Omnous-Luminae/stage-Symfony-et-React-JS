<?php

namespace App\Entity;

use App\Repository\IncidentCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: IncidentCommentRepository::class)]
#[ORM\Table(name: 'incident_comments')]
class IncidentComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_comment', type: Types::INTEGER)]
    #[Groups(['incident:read', 'comment:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Incident::class)]
    #[ORM\JoinColumn(name: 'incident_id', referencedColumnName: 'id_incident', nullable: false, onDelete: 'CASCADE')]
    private Incident $incident;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id_user', nullable: false)]
    #[Groups(['incident:read', 'comment:read'])]
    private User $author;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['incident:read', 'comment:read'])]
    private string $content;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['incident:read', 'comment:read'])]
    private bool $isInternal = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['incident:read', 'comment:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['incident:read', 'comment:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIncident(): Incident
    {
        return $this->incident;
    }

    public function setIncident(Incident $incident): static
    {
        $this->incident = $incident;
        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function isInternal(): bool
    {
        return $this->isInternal;
    }

    public function setIsInternal(bool $isInternal): static
    {
        $this->isInternal = $isInternal;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function updateTimestamp(): static
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }
}
