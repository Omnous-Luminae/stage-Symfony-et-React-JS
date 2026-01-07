<?php

namespace App\Entity;

use App\Repository\CalendarPermissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CalendarPermissionRepository::class)]
#[ORM\Table(name: 'calendar_permissions')]
#[ORM\UniqueConstraint(name: 'unique_calendar_user', columns: ['calendar_id', 'user_id'])]
class CalendarPermission
{
    public const PERMISSION_VIEW = 'view';
    public const PERMISSION_EDIT = 'edit';
    public const PERMISSION_ADMIN = 'admin';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['permission:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['permission:read'])]
    private ?Calendar $calendar = null;

    #[ORM\ManyToOne(inversedBy: 'calendarPermissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['permission:read', 'calendar:detail'])]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    #[Groups(['permission:read', 'permission:write', 'calendar:detail'])]
    private ?string $permission = null;

    #[ORM\Column]
    #[Groups(['permission:read'])]
    private ?\DateTimeImmutable $grantedAt = null;

    public function __construct()
    {
        $this->grantedAt = new \DateTimeImmutable();
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

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(string $permission): static
    {
        $validPermissions = [
            self::PERMISSION_VIEW,
            self::PERMISSION_EDIT,
            self::PERMISSION_ADMIN
        ];

        if (!in_array($permission, $validPermissions)) {
            throw new \InvalidArgumentException('Invalid permission level');
        }

        $this->permission = $permission;

        return $this;
    }

    public function getGrantedAt(): ?\DateTimeImmutable
    {
        return $this->grantedAt;
    }

    public function setGrantedAt(\DateTimeImmutable $grantedAt): static
    {
        $this->grantedAt = $grantedAt;

        return $this;
    }

    public function canView(): bool
    {
        return in_array($this->permission, [
            self::PERMISSION_VIEW,
            self::PERMISSION_EDIT,
            self::PERMISSION_ADMIN
        ]);
    }

    public function canEdit(): bool
    {
        return in_array($this->permission, [
            self::PERMISSION_EDIT,
            self::PERMISSION_ADMIN
        ]);
    }

    public function canAdmin(): bool
    {
        return $this->permission === self::PERMISSION_ADMIN;
    }
}
