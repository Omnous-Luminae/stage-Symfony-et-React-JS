<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_user', type: Types::INTEGER)]
    #[Groups(['user:read', 'calendar:read', 'event:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['user:read', 'user:write', 'calendar:read', 'event:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Groups(['user:read', 'user:write', 'calendar:read', 'event:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['user:read'])]
    private string $role = 'Intervenant';

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 10)]
    #[Groups(['user:read'])]
    private string $status = 'Actif';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastPasswordChange = null;

    #[ORM\Column(type: 'boolean')]
    private bool $rememberMe = false;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Calendar::class, orphanRemoval: true)]
    private Collection $ownedCalendars;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Event::class)]
    private Collection $createdEvents;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CalendarPermission::class, orphanRemoval: true)]
    private Collection $calendarPermissions;

    public function __construct()
    {
        $this->ownedCalendars = new ArrayCollection();
        $this->createdEvents = new ArrayCollection();
        $this->calendarPermissions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->role = 'Intervenant';
        $this->status = 'Actif';
        $this->rememberMe = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        if (!in_array($role, ['Élève', 'Professeur', 'Personnel', 'Intervenant'])) {
            throw new \InvalidArgumentException('Invalid role');
        }
        $this->role = $role;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, ['Actif', 'Inactif'])) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;

        return $this;
    }

    public function getLastPasswordChange(): ?\DateTimeImmutable
    {
        return $this->lastPasswordChange;
    }

    public function setLastPasswordChange(?\DateTimeImmutable $lastPasswordChange): static
    {
        $this->lastPasswordChange = $lastPasswordChange;

        return $this;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function setRememberMe(bool $rememberMe): static
    {
        $this->rememberMe = $rememberMe;

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Calendar>
     */
    public function getOwnedCalendars(): Collection
    {
        return $this->ownedCalendars;
    }

    public function addOwnedCalendar(Calendar $ownedCalendar): static
    {
        if (!$this->ownedCalendars->contains($ownedCalendar)) {
            $this->ownedCalendars->add($ownedCalendar);
            $ownedCalendar->setOwner($this);
        }

        return $this;
    }

    public function removeOwnedCalendar(Calendar $ownedCalendar): static
    {
        if ($this->ownedCalendars->removeElement($ownedCalendar) && $ownedCalendar->getOwner() === $this) {
            $ownedCalendar->setOwner(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getCreatedEvents(): Collection
    {
        return $this->createdEvents;
    }

    public function addCreatedEvent(Event $createdEvent): static
    {
        if (!$this->createdEvents->contains($createdEvent)) {
            $this->createdEvents->add($createdEvent);
            $createdEvent->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCreatedEvent(Event $createdEvent): static
    {
        if ($this->createdEvents->removeElement($createdEvent) && $createdEvent->getCreatedBy() === $this) {
            $createdEvent->setCreatedBy(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, CalendarPermission>
     */
    public function getCalendarPermissions(): Collection
    {
        return $this->calendarPermissions;
    }

    public function addCalendarPermission(CalendarPermission $calendarPermission): static
    {
        if (!$this->calendarPermissions->contains($calendarPermission)) {
            $this->calendarPermissions->add($calendarPermission);
            $calendarPermission->setUser($this);
        }

        return $this;
    }

    public function removeCalendarPermission(CalendarPermission $calendarPermission): static
    {
        if ($this->calendarPermissions->removeElement($calendarPermission) && $calendarPermission->getUser() === $this) {
            $calendarPermission->setUser(null);
        }

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }
}
