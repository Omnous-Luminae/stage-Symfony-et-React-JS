<?php

namespace App\Entity;

use App\Repository\CalendarPermissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CalendarPermissionRepository::class)]
#[ORM\Table(name: 'calendar_permissions')]
#[ORM\UniqueConstraint(name: 'unique_calendar_user', columns: ['calendar_id', 'user_id'])]
#[ORM\UniqueConstraint(name: 'unique_calendar_role', columns: ['calendar_id', 'role_name'])]
class CalendarPermission
{
    public const PERMISSION_CONSULTATION = 'Consultation';
    public const PERMISSION_MODIFICATION = 'Modification';
    public const PERMISSION_ADMINISTRATION = 'Administration';

    // Alias en anglais pour compatibilité avec le repository et le front
    public const PERMISSION_VIEW = self::PERMISSION_CONSULTATION;
    public const PERMISSION_EDIT = self::PERMISSION_MODIFICATION;
    public const PERMISSION_ADMIN = self::PERMISSION_ADMINISTRATION;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_c_perm', type: Types::INTEGER)]
    #[Groups(['permission:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(name: 'calendar_id', referencedColumnName: 'id_calendar', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['permission:read'])]
    private ?Calendar $calendar = null;

    #[ORM\ManyToOne(inversedBy: 'calendarPermissions')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: true, onDelete: 'CASCADE')]
    #[Groups(['permission:read', 'calendar:detail'])]
    private ?User $user = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['permission:read', 'permission:write'])]
    private ?string $roleName = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['permission:read', 'permission:write', 'calendar:detail'])]
    private ?string $permission = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['permission:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['permission:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCalendar(): ?Calendar
    {
        return $this->calendar;
    }

    public function setCalendar(?Calendar $calendar): static
    {
        $this->calendar = $calendar;

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

    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    public function setRoleName(?string $roleName): static
    {
        $this->roleName = $roleName;

        return $this;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(string $permission): static
    {
        $validPermissions = [
            self::PERMISSION_CONSULTATION,
            self::PERMISSION_MODIFICATION,
            self::PERMISSION_ADMINISTRATION
        ];

        if (!in_array($permission, $validPermissions)) {
            throw new \InvalidArgumentException('Invalid permission level');
        }

        $this->permission = $permission;

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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function canView(): bool
    {
        return in_array($this->permission, [
            self::PERMISSION_CONSULTATION,
            self::PERMISSION_MODIFICATION,
            self::PERMISSION_ADMINISTRATION
        ]);
    }

    public function canEdit(): bool
    {
        return in_array($this->permission, [
            self::PERMISSION_MODIFICATION,
            self::PERMISSION_ADMINISTRATION
        ]);
    }

    public function canAdmin(): bool
    {
        return $this->permission === self::PERMISSION_ADMINISTRATION;
    }
}
