<?php

namespace App\Entity;

use App\Repository\CalendarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CalendarRepository::class)]
#[ORM\Table(name: 'calendars')]
#[ORM\HasLifecycleCallbacks]
class Calendar
{
    public const TYPE_PERSONAL = 'personal';
    public const TYPE_SHARED = 'shared';
    public const TYPE_PUBLIC = 'public';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['calendar:read', 'event:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['calendar:read', 'calendar:write', 'event:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['calendar:read', 'calendar:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Groups(['calendar:read', 'calendar:write'])]
    private ?string $type = null;

    #[ORM\Column(length: 7)]
    #[Groups(['calendar:read', 'calendar:write', 'event:read'])]
    private ?string $color = '#3788d8';

    #[ORM\ManyToOne(inversedBy: 'ownedCalendars')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['calendar:read'])]
    private ?User $owner = null;

    #[ORM\OneToMany(mappedBy: 'calendar', targetEntity: Event::class, orphanRemoval: true)]
    private Collection $events;

    #[ORM\OneToMany(mappedBy: 'calendar', targetEntity: CalendarPermission::class, orphanRemoval: true)]
    #[Groups(['calendar:detail'])]
    private Collection $permissions;

    #[ORM\Column]
    #[Groups(['calendar:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['calendar:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!in_array($type, [self::TYPE_PERSONAL, self::TYPE_SHARED, self::TYPE_PUBLIC])) {
            throw new \InvalidArgumentException('Invalid calendar type');
        }
        $this->type = $type;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setCalendar($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event) && $event->getCalendar() === $this) {
            $event->setCalendar(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, CalendarPermission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(CalendarPermission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
            $permission->setCalendar($this);
        }

        return $this;
    }

    public function removePermission(CalendarPermission $permission): static
    {
        if ($this->permissions->removeElement($permission) && $permission->getCalendar() === $this) {
            $permission->setCalendar(null);
        }

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

    public function isPersonal(): bool
    {
        return $this->type === self::TYPE_PERSONAL;
    }

    public function isShared(): bool
    {
        return $this->type === self::TYPE_SHARED;
    }

    public function isPublic(): bool
    {
        return $this->type === self::TYPE_PUBLIC;
    }
}
