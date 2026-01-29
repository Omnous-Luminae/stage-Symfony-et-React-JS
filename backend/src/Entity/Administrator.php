<?php

namespace App\Entity;

use App\Repository\AdministratorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AdministratorRepository::class)]
#[ORM\Table(name: 'administrators')]
#[ORM\HasLifecycleCallbacks]
class Administrator
{
    public const LEVEL_SUPER_ADMIN = 'Super_Admin';
    public const LEVEL_ADMIN = 'Admin';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_admin', type: Types::INTEGER)]
    #[Groups(['admin:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['admin:read'])]
    private ?User $user = null;

    #[ORM\Column(name: 'permission_level', type: Types::STRING, length: 20, enumType: null)]
    #[Groups(['admin:read', 'admin:write'])]
    private string $permissionLevel = self::LEVEL_ADMIN;

    #[ORM\Column(name: 'can_manage_users', type: Types::BOOLEAN)]
    #[Groups(['admin:read', 'admin:write'])]
    private bool $canManageUsers = true;

    #[ORM\Column(name: 'can_manage_calendars', type: Types::BOOLEAN)]
    #[Groups(['admin:read', 'admin:write'])]
    private bool $canManageCalendars = true;

    #[ORM\Column(name: 'can_manage_permissions', type: Types::BOOLEAN)]
    #[Groups(['admin:read', 'admin:write'])]
    private bool $canManagePermissions = true;

    #[ORM\Column(name: 'can_view_audit_logs', type: Types::BOOLEAN)]
    #[Groups(['admin:read', 'admin:write'])]
    private bool $canViewAuditLogs = true;

    #[ORM\Column(name: 'last_login', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['admin:read'])]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(name: 'last_login_ip', type: Types::STRING, length: 45, nullable: true)]
    #[Groups(['admin:read'])]
    private ?string $lastLoginIp = null;

    #[ORM\Column(name: 'failed_login_attempts', type: Types::INTEGER)]
    #[Groups(['admin:read'])]
    private int $failedLoginAttempts = 0;

    #[ORM\Column(name: 'locked_until', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['admin:read'])]
    private ?\DateTimeInterface $lockedUntil = null;

    #[ORM\Column(name: 'two_factor_enabled', type: Types::BOOLEAN)]
    #[Groups(['admin:read', 'admin:write'])]
    private bool $twoFactorEnabled = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[Groups(['admin:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[Groups(['admin:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPermissionLevel(): string
    {
        return $this->permissionLevel;
    }

    public function setPermissionLevel(string $permissionLevel): static
    {
        $this->permissionLevel = $permissionLevel;
        return $this;
    }

    public function isSuperAdmin(): bool
    {
        return $this->permissionLevel === self::LEVEL_SUPER_ADMIN;
    }

    public function canManageUsers(): bool
    {
        return $this->canManageUsers;
    }

    public function setCanManageUsers(bool $canManageUsers): static
    {
        $this->canManageUsers = $canManageUsers;
        return $this;
    }

    public function canManageCalendars(): bool
    {
        return $this->canManageCalendars;
    }

    public function setCanManageCalendars(bool $canManageCalendars): static
    {
        $this->canManageCalendars = $canManageCalendars;
        return $this;
    }

    public function canManagePermissions(): bool
    {
        return $this->canManagePermissions;
    }

    public function setCanManagePermissions(bool $canManagePermissions): static
    {
        $this->canManagePermissions = $canManagePermissions;
        return $this;
    }

    public function canViewAuditLogs(): bool
    {
        return $this->canViewAuditLogs;
    }

    public function setCanViewAuditLogs(bool $canViewAuditLogs): static
    {
        $this->canViewAuditLogs = $canViewAuditLogs;
        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function getLastLoginIp(): ?string
    {
        return $this->lastLoginIp;
    }

    public function setLastLoginIp(?string $lastLoginIp): static
    {
        $this->lastLoginIp = $lastLoginIp;
        return $this;
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function setFailedLoginAttempts(int $failedLoginAttempts): static
    {
        $this->failedLoginAttempts = $failedLoginAttempts;
        return $this;
    }

    public function incrementFailedLoginAttempts(): static
    {
        $this->failedLoginAttempts++;
        return $this;
    }

    public function resetFailedLoginAttempts(): static
    {
        $this->failedLoginAttempts = 0;
        return $this;
    }

    public function getLockedUntil(): ?\DateTimeInterface
    {
        return $this->lockedUntil;
    }

    public function setLockedUntil(?\DateTimeInterface $lockedUntil): static
    {
        $this->lockedUntil = $lockedUntil;
        return $this;
    }

    public function isLocked(): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > new \DateTime();
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $twoFactorEnabled): static
    {
        $this->twoFactorEnabled = $twoFactorEnabled;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
