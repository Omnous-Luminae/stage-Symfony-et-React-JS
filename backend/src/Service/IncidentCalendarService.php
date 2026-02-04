<?php

namespace App\Service;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\Incident;
use App\Entity\User;
use App\Repository\CalendarRepository;
use App\Repository\EventRepository;
use App\Repository\EventTypeRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service pour gérer le calendrier des incidents
 * Ce calendrier est visible par tout le monde sauf les élèves
 */
class IncidentCalendarService
{
    public const CALENDAR_NAME = '🚨 Calendrier des Incidents';
    public const CALENDAR_TYPE = 'incidents'; // Type spécial pour le calendrier des incidents
    public const EVENT_TYPE_CODE = 'incident'; // Code pour le type d'événement incident

    public function __construct(
        private EntityManagerInterface $em,
        private CalendarRepository $calendarRepository,
        private EventRepository $eventRepository,
        private EventTypeRepository $eventTypeRepository
    ) {}

    /**
     * Récupère ou crée le calendrier des incidents
     */
    public function getOrCreateIncidentCalendar(): Calendar
    {
        // Chercher un calendrier existant avec le nom spécifique
        $calendar = $this->calendarRepository->findOneBy(['name' => self::CALENDAR_NAME]);
        
        if (!$calendar) {
            $calendar = new Calendar();
            $calendar->setName(self::CALENDAR_NAME);
            $calendar->setDescription('Calendrier des incidents signalés. Visible par le personnel et les professeurs.');
            $calendar->setColor('#dc2626'); // Rouge pour les incidents
            $calendar->setType(Calendar::TYPE_PUBLIC); // Public mais filtré par rôle
            $calendar->setOwner(null); // Pas de propriétaire spécifique
            
            $this->em->persist($calendar);
            $this->em->flush();
        }
        
        return $calendar;
    }

    /**
     * Récupère ou crée le type d'événement "Incident"
     */
    public function getOrCreateIncidentEventType(): EventType
    {
        $eventType = $this->eventTypeRepository->findByCode(self::EVENT_TYPE_CODE);
        
        if (!$eventType) {
            $eventType = new EventType();
            $eventType->setName('Incident');
            $eventType->setCode(self::EVENT_TYPE_CODE);
            $eventType->setDescription('Événement automatiquement créé pour un incident signalé');
            $eventType->setColor('#dc2626'); // Rouge
            $eventType->setIcon('🚨');
            $eventType->setIsActive(true);
            $eventType->setDisplayOrder(100); // À la fin de la liste
            
            $this->em->persist($eventType);
            $this->em->flush();
        }
        
        return $eventType;
    }

    /**
     * Crée un événement dans le calendrier des incidents pour un incident donné
     */
    public function createEventForIncident(Incident $incident, User $createdBy): Event
    {
        $calendar = $this->getOrCreateIncidentCalendar();
        $eventType = $this->getOrCreateIncidentEventType();
        
        // Créer l'événement
        $event = new Event();
        $event->setTitle($this->formatEventTitle($incident));
        $event->setDescription($this->formatEventDescription($incident));
        $event->setCalendar($calendar);
        $event->setEventType($eventType);
        $event->setType('Autre'); // Compatibilité avec l'ancien système ENUM
        $event->setCreatedBy($createdBy);
        
        // L'événement dure toute la journée de création
        $startDate = new \DateTime();
        $startDate->setTime(0, 0, 0);
        $endDate = clone $startDate;
        $endDate->setTime(23, 59, 59);
        
        $event->setStartDate($startDate);
        $event->setEndDate($endDate);
        
        // Définir la couleur selon la priorité
        $event->setColor($this->getColorByPriority($incident->getPriority()));
        
        // Emplacement si renseigné
        if ($incident->getLocation()) {
            $event->setLocation($incident->getLocation());
        }
        
        $this->em->persist($event);
        $this->em->flush();
        
        return $event;
    }

    /**
     * Met à jour l'événement associé à un incident
     */
    public function updateEventForIncident(Incident $incident): ?Event
    {
        $calendar = $this->getOrCreateIncidentCalendar();
        
        // Chercher l'événement existant (basé sur le titre qui contient l'ID)
        $events = $this->eventRepository->findBy([
            'calendar' => $calendar
        ]);
        
        $targetEvent = null;
        $searchPattern = "[Incident #" . $incident->getId() . "]";
        
        foreach ($events as $event) {
            if (str_contains($event->getTitle(), $searchPattern)) {
                $targetEvent = $event;
                break;
            }
        }
        
        if ($targetEvent) {
            $targetEvent->setTitle($this->formatEventTitle($incident));
            $targetEvent->setDescription($this->formatEventDescription($incident));
            $targetEvent->setColor($this->getColorByPriority($incident->getPriority()));
            
            if ($incident->getLocation()) {
                $targetEvent->setLocation($incident->getLocation());
            }
            
            $this->em->flush();
        }
        
        return $targetEvent;
    }

    /**
     * Supprime l'événement associé à un incident
     */
    public function deleteEventForIncident(Incident $incident): bool
    {
        $calendar = $this->getOrCreateIncidentCalendar();
        
        $events = $this->eventRepository->findBy([
            'calendar' => $calendar
        ]);
        
        $searchPattern = "[Incident #" . $incident->getId() . "]";
        
        foreach ($events as $event) {
            if (str_contains($event->getTitle(), $searchPattern)) {
                $this->em->remove($event);
                $this->em->flush();
                return true;
            }
        }
        
        return false;
    }

    /**
     * Formate le titre de l'événement
     */
    private function formatEventTitle(Incident $incident): string
    {
        $priorityEmoji = match($incident->getPriority()) {
            Incident::PRIORITY_URGENT => '🔴',
            Incident::PRIORITY_HIGH => '🟠',
            Incident::PRIORITY_MEDIUM => '🟡',
            Incident::PRIORITY_LOW => '🟢',
            default => '⚪'
        };
        
        $statusEmoji = match($incident->getStatus()) {
            Incident::STATUS_OPEN => '📋',
            Incident::STATUS_IN_PROGRESS => '🔧',
            Incident::STATUS_RESOLVED => '✅',
            Incident::STATUS_CLOSED => '🔒',
            default => '📋'
        };
        
        return sprintf(
            '%s %s [Incident #%d] %s',
            $priorityEmoji,
            $statusEmoji,
            $incident->getId(),
            $incident->getTitle()
        );
    }

    /**
     * Formate la description de l'événement
     */
    private function formatEventDescription(Incident $incident): string
    {
        $priorityLabels = [
            Incident::PRIORITY_LOW => 'Faible',
            Incident::PRIORITY_MEDIUM => 'Moyenne',
            Incident::PRIORITY_HIGH => 'Haute',
            Incident::PRIORITY_URGENT => 'Urgente'
        ];
        
        $statusLabels = [
            Incident::STATUS_OPEN => 'Ouvert',
            Incident::STATUS_IN_PROGRESS => 'En cours',
            Incident::STATUS_RESOLVED => 'Résolu',
            Incident::STATUS_CLOSED => 'Fermé'
        ];
        
        $lines = [
            "📌 **Catégorie:** " . $incident->getCategory()->getName(),
            "⚡ **Priorité:** " . ($priorityLabels[$incident->getPriority()] ?? $incident->getPriority()),
            "📊 **Statut:** " . ($statusLabels[$incident->getStatus()] ?? $incident->getStatus()),
            "",
            "📝 **Description:**",
            $incident->getDescription()
        ];
        
        if ($incident->getLocation()) {
            array_splice($lines, 3, 0, ["📍 **Lieu:** " . $incident->getLocation()]);
        }
        
        if ($incident->getAssignee()) {
            $lines[] = "";
            $lines[] = "👤 **Assigné à:** " . $incident->getAssignee()->getFullName();
        } elseif ($incident->getAssigneeRole()) {
            $lines[] = "";
            $lines[] = "👥 **Assigné au rôle:** " . $incident->getAssigneeRole();
        }
        
        $lines[] = "";
        $lines[] = "---";
        $lines[] = "🕐 Signalé par " . $incident->getReporter()->getFullName();
        $lines[] = "le " . $incident->getCreatedAt()->format('d/m/Y à H:i');
        
        return implode("\n", $lines);
    }

    /**
     * Retourne la couleur selon la priorité
     */
    private function getColorByPriority(string $priority): string
    {
        return match($priority) {
            Incident::PRIORITY_URGENT => '#dc2626', // Rouge vif
            Incident::PRIORITY_HIGH => '#ea580c',   // Orange
            Incident::PRIORITY_MEDIUM => '#ca8a04', // Jaune foncé
            Incident::PRIORITY_LOW => '#16a34a',    // Vert
            default => '#6b7280'                    // Gris
        };
    }

    /**
     * Vérifie si un utilisateur peut voir le calendrier des incidents
     * (tout le monde sauf les élèves)
     */
    public function canUserViewIncidentCalendar(User $user): bool
    {
        return $user->getRole() !== 'Élève';
    }
}
