<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
class AuditLog
{
    // Action types
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_LOGIN_FAILED = 'login_failed';
    public const ACTION_PROMOTE = 'promote';
    public const ACTION_DEMOTE = 'demote';
    public const ACTION_PERMISSION_CHANGE = 'permission_change';
    public const ACTION_UNDO = 'undo';

    // Entity types
    public const ENTITY_USER = 'user';
    public const ENTITY_CALENDAR = 'calendar';
    public const ENTITY_EVENT = 'event';
    public const ENTITY_ADMIN = 'administrator';
    public const ENTITY_PERMISSION = 'permission';
    public const ENTITY_INCIDENT = 'incident';
    public const ENTITY_INCIDENT_CATEGORY = 'incident_category';
    public const ENTITY_INCIDENT_COMMENT = 'incident_comment';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_log', type: Types::INTEGER)]
    #[Groups(['log:read'])]
    private ?int $id = null;

    // L'admin qui a fait l'action (peut être null si c'est un utilisateur normal)
    #[ORM\ManyToOne(targetEntity: Administrator::class)]
    #[ORM\JoinColumn(name: 'admin_id', referencedColumnName: 'id_admin', nullable: true)]
    #[Groups(['log:read'])]
    private ?Administrator $admin = null;

    // L'utilisateur qui a fait l'action (toujours renseigné)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: true)]
    #[Groups(['log:read'])]
    private ?User $user = null;

    #[ORM\Column(name: 'action', type: Types::STRING, length: 100)]
    #[Groups(['log:read'])]
    private string $action;

    #[ORM\Column(name: 'entity_type', type: Types::STRING, length: 50)]
    #[Groups(['log:read'])]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', type: Types::INTEGER, nullable: true)]
    #[Groups(['log:read'])]
    private ?int $entityId = null;

    #[ORM\Column(name: 'old_value', type: Types::TEXT, nullable: true)]
    #[Groups(['log:read'])]
    private ?string $oldValue = null;

    #[ORM\Column(name: 'new_value', type: Types::TEXT, nullable: true)]
    #[Groups(['log:read'])]
    private ?string $newValue = null;

    #[ORM\Column(name: 'ip_address', type: Types::STRING, length: 45, nullable: true)]
    #[Groups(['log:read'])]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'user_agent', type: Types::TEXT, nullable: true)]
    #[Groups(['log:read'])]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[Groups(['log:read'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdmin(): ?Administrator
    {
        return $this->admin;
    }

    public function setAdmin(?Administrator $admin): static
    {
        $this->admin = $admin;
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

    /**
     * Retourne le nom de l'utilisateur qui a fait l'action
     */
    public function getPerformerName(): string
    {
        if ($this->user) {
            return $this->user->getFirstName() . ' ' . $this->user->getLastName();
        }
        if ($this->admin && $this->admin->getUser()) {
            return $this->admin->getUser()->getFirstName() . ' ' . $this->admin->getUser()->getLastName();
        }
        return 'Système';
    }

    /**
     * Vérifie si l'action a été faite par un admin
     */
    public function isAdminAction(): bool
    {
        return $this->admin !== null;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getOldValue(): ?string
    {
        return $this->oldValue;
    }

    public function setOldValue(?string $oldValue): static
    {
        $this->oldValue = $oldValue;
        return $this;
    }

    public function getOldValueDecoded(): ?array
    {
        return $this->oldValue ? json_decode($this->oldValue, true) : null;
    }

    public function getNewValue(): ?string
    {
        return $this->newValue;
    }

    public function setNewValue(?string $newValue): static
    {
        $this->newValue = $newValue;
        return $this;
    }

    public function getNewValueDecoded(): ?array
    {
        return $this->newValue ? json_decode($this->newValue, true) : null;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
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

    /**
     * Get human-readable action label
     */
    public function getActionLabel(): string
    {
        return match($this->action) {
            self::ACTION_CREATE => 'Création',
            self::ACTION_UPDATE => 'Modification',
            self::ACTION_DELETE => 'Suppression',
            self::ACTION_LOGIN => 'Connexion',
            self::ACTION_LOGOUT => 'Déconnexion',
            self::ACTION_LOGIN_FAILED => 'Échec de connexion',
            self::ACTION_PROMOTE => 'Promotion admin',
            self::ACTION_DEMOTE => 'Rétrogradation',
            self::ACTION_PERMISSION_CHANGE => 'Changement de permissions',
            self::ACTION_UNDO => 'Annulation',
            default => $this->action
        };
    }

    /**
     * Get human-readable entity type label
     */
    public function getEntityTypeLabel(): string
    {
        return match($this->entityType) {
            self::ENTITY_USER => 'Utilisateur',
            self::ENTITY_CALENDAR => 'Calendrier',
            self::ENTITY_EVENT => 'Événement',
            self::ENTITY_ADMIN => 'Administrateur',
            self::ENTITY_PERMISSION => 'Permission',
            default => $this->entityType
        };
    }
}
