<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
#[ORM\HasLifecycleCallbacks]
class Event
{
    public const TYPE_COURSE = 'course';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_EXAM = 'exam';
    public const TYPE_ADMINISTRATIVE = 'administrative';
    public const TYPE_TRAINING = 'training';
    public const TYPE_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
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

    #[ORM\Column(length: 50)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $type = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $color = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['event:read', 'event:write'])]
    private ?bool $isRecurrent = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?array $recurrencePattern = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['event:read'])]
    private ?Calendar $calendar = null;

    #[ORM\ManyToOne(inversedBy: 'createdEvents')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['event:read'])]
    private ?User $createdBy = null;

    #[ORM\Column]
    #[Groups(['event:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['event:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
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
        return $this->type;
    }

    public function setType(string $type): static
    {
        $validTypes = [
            self::TYPE_COURSE,
            self::TYPE_MEETING,
            self::TYPE_EXAM,
            self::TYPE_ADMINISTRATIVE,
            self::TYPE_TRAINING,
            self::TYPE_OTHER
        ];

        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException('Invalid event type');
        }

        $this->type = $type;

        return $this;
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

    public function getRecurrencePattern(): ?array
    {
        return $this->recurrencePattern;
    }

    public function setRecurrencePattern(?array $recurrencePattern): static
    {
        $this->recurrencePattern = $recurrencePattern;

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
