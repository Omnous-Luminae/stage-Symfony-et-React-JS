<?php

namespace App\Entity;

use App\Repository\UserRoleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRoleRepository::class)]
#[ORM\Table(name: 'user_roles')]
class UserRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_role', type: Types::INTEGER)]
    #[Groups(['role:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['role:read', 'role:write'])]
    private string $name;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['role:read', 'role:write'])]
    private string $code;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['role:read', 'role:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 7, options: ['default' => '#6366f1'])]
    #[Groups(['role:read', 'role:write'])]
    private string $color = '#6366f1';

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['role:read', 'role:write'])]
    private ?string $icon = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['role:read', 'role:write'])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['role:read', 'role:write'])]
    private bool $isSystem = false;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['role:read', 'role:write'])]
    private int $displayOrder = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['role:read', 'role:write'])]
    private bool $canCreateEvents = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['role:read', 'role:write'])]
    private bool $canCreatePublicEvents = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['role:read', 'role:write'])]
    private bool $canShareCalendars = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['role:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['role:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): static
    {
        $this->isSystem = $isSystem;
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

    public function canCreateEvents(): bool
    {
        return $this->canCreateEvents;
    }

    public function setCanCreateEvents(bool $canCreateEvents): static
    {
        $this->canCreateEvents = $canCreateEvents;
        return $this;
    }

    public function canCreatePublicEvents(): bool
    {
        return $this->canCreatePublicEvents;
    }

    public function setCanCreatePublicEvents(bool $canCreatePublicEvents): static
    {
        $this->canCreatePublicEvents = $canCreatePublicEvents;
        return $this;
    }

    public function canShareCalendars(): bool
    {
        return $this->canShareCalendars;
    }

    public function setCanShareCalendars(bool $canShareCalendars): static
    {
        $this->canShareCalendars = $canShareCalendars;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
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
}
