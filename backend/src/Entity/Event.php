<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
#[ORM\HasLifecycleCallbacks]
class Event
{
    // Anciennes constantes gardées pour compatibilité
    public const TYPE_COURSE = 'Cours';
    public const TYPE_MEETING = 'Réunion';
    public const TYPE_EXAM = 'Examen';
    public const TYPE_ADMINISTRATIVE = 'Administratif';
    public const TYPE_TRAINING = 'Formation';
    public const TYPE_OTHER = 'Autre';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_events', type: Types::INTEGER)]
    #[Groups(['event:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $location = null;

    // Ancien champ type gardé pour compatibilité (sera supprimé plus tard)
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $type = null;

    // Nouvelle relation vers EventType
    #[ORM\ManyToOne(targetEntity: EventType::class, inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'event_type_id', referencedColumnName: 'id_event_type', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['event:read', 'event:write'])]
    private ?EventType $eventType = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $color = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['event:read', 'event:write'])]
    private ?bool $isRecurrent = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $recurrenceType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?int $recurrenceInterval = 1;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?array $recurrenceDays = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $recurrenceEndDate = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childEvents')]
    #[ORM\JoinColumn(name: 'parent_event_id', referencedColumnName: 'id_events', nullable: true, onDelete: 'SET NULL')]
    private ?Event $parentEvent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentEvent')]
    private Collection $childEvents;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'calendar_id', referencedColumnName: 'id_calendar', nullable: true, onDelete: 'CASCADE')]
    #[Groups(['event:read'])]
    private ?Calendar $calendar = null;

    #[ORM\ManyToOne(inversedBy: 'createdEvents')]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id_user', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['event:read'])]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['event:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['event:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->childEvents = new ArrayCollection();
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

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

    public function getType(): ?string
    {
        // Si eventType est défini, retourner son nom pour compatibilité
        if ($this->eventType !== null) {
            return $this->eventType->getName();
        }
        return $this->type;
    }

    public function setType(?string $type): static
    {
        // Garde la compatibilité avec l'ancien système
        $this->type = $type;
        return $this;
    }

    public function getEventType(): ?EventType
    {
        return $this->eventType;
    }

    public function setEventType(?EventType $eventType): static
    {
        $this->eventType = $eventType;
        // Synchroniser avec l'ancien champ pour compatibilité
        if ($eventType !== null) {
            $this->type = $eventType->getName();
        }
        return $this;
    }

    /**
     * Retourne le code du type d'événement
     */
    public function getEventTypeCode(): ?string
    {
        return $this->eventType?->getCode();
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function isIsRecurrent(): ?bool
    {
        return $this->isRecurrent;
    }

    public function setIsRecurrent(bool $isRecurrent): static
    {
        $this->isRecurrent = $isRecurrent;

        return $this;
    }

    public function getRecurrenceType(): ?string
    {
        return $this->recurrenceType;
    }

    public function setRecurrenceType(?string $recurrenceType): static
    {
        $validTypes = ['daily', 'weekly', 'biweekly', 'monthly', 'yearly'];
        if ($recurrenceType && !in_array($recurrenceType, $validTypes)) {
            throw new \InvalidArgumentException('Invalid recurrence type: ' . $recurrenceType);
        }
        $this->recurrenceType = $recurrenceType;

        return $this;
    }

    public function getRecurrenceInterval(): ?int
    {
        return $this->recurrenceInterval;
    }

    public function setRecurrenceInterval(?int $recurrenceInterval): static
    {
        $this->recurrenceInterval = $recurrenceInterval;

        return $this;
    }

    public function getRecurrenceDays(): ?array
    {
        return $this->recurrenceDays;
    }

    public function setRecurrenceDays(?array $recurrenceDays): static
    {
        $this->recurrenceDays = $recurrenceDays;

        return $this;
    }

    public function getRecurrenceEndDate(): ?\DateTimeInterface
    {
        return $this->recurrenceEndDate;
    }

    public function setRecurrenceEndDate(?\DateTimeInterface $recurrenceEndDate): static
    {
        $this->recurrenceEndDate = $recurrenceEndDate;

        return $this;
    }

    public function getParentEvent(): ?self
    {
        return $this->parentEvent;
    }

    public function setParentEvent(?self $parentEvent): static
    {
        $this->parentEvent = $parentEvent;

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getChildEvents(): Collection
    {
        return $this->childEvents;
    }

    public function addChildEvent(Event $childEvent): static
    {
        if (!$this->childEvents->contains($childEvent)) {
            $this->childEvents->add($childEvent);
            $childEvent->setParentEvent($this);
        }

        return $this;
    }

    public function removeChildEvent(Event $childEvent): static
    {
        if ($this->childEvents->removeElement($childEvent)) {
            if ($childEvent->getParentEvent() === $this) {
                $childEvent->setParentEvent(null);
            }
        }

        return $this;
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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

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

    public function getDuration(): \DateInterval
    {
        return $this->startDate->diff($this->endDate);
    }

    public function isOngoing(?\DateTimeInterface $now = null): bool
    {
        $now = $now ?? new \DateTime();
        return $this->startDate <= $now && $this->endDate >= $now;
    }

    public function isPast(?\DateTimeInterface $now = null): bool
    {
        $now = $now ?? new \DateTime();
        return $this->endDate < $now;
    }

    public function isFuture(?\DateTimeInterface $now = null): bool
    {
        $now = $now ?? new \DateTime();
        return $this->startDate > $now;
    }
}
