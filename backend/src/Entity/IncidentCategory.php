<?php

namespace App\Entity;

use App\Repository\IncidentCategoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: IncidentCategoryRepository::class)]
#[ORM\Table(name: 'incident_categories')]
class IncidentCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_category', type: Types::INTEGER)]
    #[Groups(['incident:read', 'category:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['incident:read', 'category:read'])]
    private string $name;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['incident:read', 'category:read'])]
    private string $code;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['category:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 7, options: ['default' => '#6366f1'])]
    #[Groups(['incident:read', 'category:read'])]
    private string $color = '#6366f1';

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['incident:read', 'category:read'])]
    private ?string $icon = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['category:read'])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['category:read'])]
    private int $displayOrder = 0;

    // Le rôle par défaut à qui assigner les incidents de cette catégorie
    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['category:read'])]
    private ?string $defaultAssigneeRole = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['category:read'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function getDefaultAssigneeRole(): ?string
    {
        return $this->defaultAssigneeRole;
    }

    public function setDefaultAssigneeRole(?string $defaultAssigneeRole): static
    {
        $this->defaultAssigneeRole = $defaultAssigneeRole;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
