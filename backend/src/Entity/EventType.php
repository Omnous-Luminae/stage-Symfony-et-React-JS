<?php

namespace App\Entity;

use App\Repository\EventTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EventTypeRepository::class)]
#[ORM\Table(name: 'event_types')]
#[ORM\HasLifecycleCallbacks]
class EventType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_event_type', type: 'integer')]
    #[Groups(['event:read', 'event:write', 'event_type:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['event:read', 'event:write', 'event_type:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Groups(['event:read', 'event:write', 'event_type:read'])]
    private ?string $code = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['event_type:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 7)]
    #[Groups(['event:read', 'event:write', 'event_type:read'])]
    private string $color = '#3788d8';

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['event:read', 'event:write', 'event_type:read'])]
    private ?string $icon = null;

    #[ORM\Column(name: 'is_active', type: 'boolean')]
    #[Groups(['event_type:read'])]
    private bool $isActive = true;

    #[ORM\Column(name: 'display_order', type: 'integer')]
    #[Groups(['event_type:read'])]
    private int $displayOrder = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'eventType', targetEntity: Event::class)]
    private Collection $events;

    // Constantes pour les codes des types (pour compatibilité)
    public const CODE_COURSE = 'course';
    public const CODE_MEETING = 'meeting';
    public const CODE_EXAM = 'exam';
    public const CODE_TRAINING = 'training';
    public const CODE_ADMINISTRATIVE = 'administrative';
    public const CODE_OTHER = 'other';

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
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

    public function getCode(): ?string
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
            $event->setEventType($this);
        }
        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            if ($event->getEventType() === $this) {
                $event->setEventType(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
