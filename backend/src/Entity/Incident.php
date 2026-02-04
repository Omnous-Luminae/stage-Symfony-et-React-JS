<?php

namespace App\Entity;

use App\Repository\IncidentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: IncidentRepository::class)]
#[ORM\Table(name: 'incidents')]
class Incident
{
    // Priorités
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    // Statuts
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_incident', type: Types::INTEGER)]
    #[Groups(['incident:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['incident:read', 'incident:write'])]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['incident:read', 'incident:write'])]
    private string $description;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['incident:read', 'incident:write'])]
    private ?string $location = null;

    #[ORM\ManyToOne(targetEntity: IncidentCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id_category', nullable: false)]
    #[Groups(['incident:read'])]
    private IncidentCategory $category;

    #[ORM\Column(length: 20, options: ['default' => 'medium'])]
    #[Groups(['incident:read', 'incident:write'])]
    private string $priority = self::PRIORITY_MEDIUM;

    #[ORM\Column(length: 20, options: ['default' => 'open'])]
    #[Groups(['incident:read'])]
    private string $status = self::STATUS_OPEN;

    // L'utilisateur qui a créé l'incident
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reporter_id', referencedColumnName: 'id_user', nullable: false)]
    #[Groups(['incident:read'])]
    private User $reporter;

    // L'utilisateur assigné (peut être null si assigné à un rôle)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assignee_id', referencedColumnName: 'id_user', nullable: true)]
    #[Groups(['incident:read'])]
    private ?User $assignee = null;

    // Le rôle assigné (ex: "Personnel", "Professeur")
    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['incident:read', 'incident:write'])]
    private ?string $assigneeRole = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['incident:read'])]
    private ?string $resolution = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['incident:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['incident:read'])]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['incident:read'])]
    private ?\DateTimeInterface $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getCategory(): IncidentCategory
    {
        return $this->category;
    }

    public function setCategory(IncidentCategory $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriorityLabel(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Basse',
            self::PRIORITY_MEDIUM => 'Moyenne',
            self::PRIORITY_HIGH => 'Haute',
            self::PRIORITY_URGENT => 'Urgente',
            default => $this->priority
        };
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        if ($status === self::STATUS_RESOLVED || $status === self::STATUS_CLOSED) {
            $this->resolvedAt = new \DateTime();
        }
        return $this;
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'Ouvert',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_RESOLVED => 'Résolu',
            self::STATUS_CLOSED => 'Fermé',
            default => $this->status
        };
    }

    public function getReporter(): User
    {
        return $this->reporter;
    }

    public function setReporter(User $reporter): static
    {
        $this->reporter = $reporter;
        return $this;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): static
    {
        $this->assignee = $assignee;
        return $this;
    }

    public function getAssigneeRole(): ?string
    {
        return $this->assigneeRole;
    }

    public function setAssigneeRole(?string $assigneeRole): static
    {
        $this->assigneeRole = $assigneeRole;
        return $this;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): static
    {
        $this->resolution = $resolution;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function updateTimestamp(): static
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getResolvedAt(): ?\DateTimeInterface
    {
        return $this->resolvedAt;
    }

    /**
     * Vérifie si un utilisateur peut voir cet incident
     */
    public function isVisibleTo(User $user): bool
    {
        // Le reporter peut toujours voir son incident
        if ($this->reporter->getId() === $user->getId()) {
            return true;
        }

        // L'assigné peut voir l'incident
        if ($this->assignee && $this->assignee->getId() === $user->getId()) {
            return true;
        }

        // Les utilisateurs du rôle assigné peuvent voir l'incident
        if ($this->assigneeRole && $user->getRole() === $this->assigneeRole) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si un utilisateur peut modifier cet incident
     */
    public function isEditableBy(User $user): bool
    {
        // Le reporter peut modifier tant que c'est ouvert
        if ($this->reporter->getId() === $user->getId() && $this->status === self::STATUS_OPEN) {
            return true;
        }

        // L'assigné peut modifier
        if ($this->assignee && $this->assignee->getId() === $user->getId()) {
            return true;
        }

        // Les utilisateurs du rôle assigné peuvent modifier
        if ($this->assigneeRole && $user->getRole() === $this->assigneeRole) {
            return true;
        }

        return false;
    }
}
