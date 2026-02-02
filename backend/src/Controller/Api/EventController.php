<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Calendar;
use App\Entity\User;
use App\Repository\AdministratorRepository;
use App\Repository\CalendarPermissionRepository;
use App\Repository\EventRepository;
use App\Service\AuditLogService;
use App\Service\SessionUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'api_')]
class EventController extends AbstractController
{
    private AuditLogService $auditLogService;
    private AdministratorRepository $adminRepository;
    private SessionUserService $sessionUserService;

    public function __construct(
        AuditLogService $auditLogService,
        AdministratorRepository $adminRepository,
        SessionUserService $sessionUserService
    ) {
        $this->auditLogService = $auditLogService;
        $this->adminRepository = $adminRepository;
        $this->sessionUserService = $sessionUserService;
    }

    /**
     * Try to log action if current user is admin
     */
    private function tryLogAction(string $method, ...$args): void
    {
        try {
            $user = $this->sessionUserService->getCurrentUser();
            if ($user) {
                $admin = $this->adminRepository->findByUser($user);
                if ($admin) {
                    $this->auditLogService->$method(...$args);
                }
            }
        } catch (\Exception $e) {
            // Silently fail - logging should not break the main operation
        }
    }

    // Constantes pour les couleurs
    private const COLOR_BLUE = '#3788d8';
    private const COLOR_GREEN = '#4caf50';
    private const COLOR_RED = '#f44336';
    private const COLOR_ORANGE = '#ff9800';

    // Mapping des types frontend -> backend
    private const TYPE_MAPPING = [
        'course' => 'Cours',
        'meeting' => 'Réunion',
        'exam' => 'Examen',
        'administrative' => 'Administratif',
        'training' => 'Formation',
        'other' => 'Autre'
    ];

    // Mapping inverse backend -> frontend
    private const TYPE_MAPPING_REVERSE = [
        'Cours' => 'course',
        'Réunion' => 'meeting',
        'Examen' => 'exam',
        'Administratif' => 'administrative',
        'Formation' => 'training',
        'Autre' => 'other'
    ];

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'API Agenda Partagé',
            'version' => '1.0.0',
            'endpoints' => [
                'GET /api/events' => 'Liste des événements',
                'POST /api/events' => 'Créer un événement',
                'DELETE /api/events/{id}' => 'Supprimer un événement',
                'GET /api/calendars' => 'Liste des calendriers',
            ],
        ]);
    }

    #[Route('/events', name: 'events_list', methods: ['GET'])]
    public function list(
        Request $request,
        EventRepository $eventRepository,
        EntityManagerInterface $entityManager,
        CalendarPermissionRepository $permissionRepository,
        SessionUserService $sessionUserService
    ): JsonResponse {
        $user = $sessionUserService->getCurrentUser();

        // Parse pagination
        $limit = (int) ($request->query->get('limit', 100));
        $limit = max(1, min($limit, 500));
        $offset = max(0, (int) ($request->query->get('offset', 0)));

        // Parse dates
        $startDate = null;
        $endDate = null;
        $startParam = $request->query->get('start');
        $endParam = $request->query->get('end');

        try {
            if ($startParam) {
                $startDate = new \DateTime($startParam);
            }
            if ($endParam) {
                $endDate = new \DateTime($endParam);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], 400);
        }

        // Calendar filter + access check
        $calendar = null;
        $calendarId = $request->query->get('calendarId');
        if ($calendarId) {
            $calendar = $entityManager->getRepository(Calendar::class)->find($calendarId);
            if (!$calendar) {
                return $this->json(['error' => 'Calendar not found'], 404);
            }

            // Access control for non-public calendars
            if (!$calendar->isPublic()) {
                if (!$user instanceof User) {
                    return $this->json(['error' => 'Authentication required for this calendar'], 403);
                }

                $isOwner = $calendar->getOwner() && $calendar->getOwner()->getId() === $user->getId();
                $hasPermission = (bool) $permissionRepository->findPermission($calendar, $user);

                if (!$isOwner && !$hasPermission && !$this->isGranted('ROLE_ADMIN')) {
                    return $this->json(['error' => 'Unauthorized for this calendar'], 403);
                }
            }
        }

        // Récupérer les événements filtrés par accès et plage de dates
        $eventsEntities = $eventRepository->findAccessible(
            $calendar,
            $startDate,
            $endDate,
            $user instanceof User ? $user : null,
            $limit,
            $offset
        );

        $events = [];
        foreach ($eventsEntities as $event) {
            $events[] = $this->eventToArray($event);
        }

        return $this->json($events);
    }

    #[Route('/events', name: 'events_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation basique
            if (!isset($data['title']) || !isset($data['start'])) {
                return $this->json(['error' => 'Title and start date are required'], 400);
            }

            // Créer une nouvelle entité Event
            $event = new Event();
            $event->setTitle($data['title']);
            
            // Parser les dates au format ISO
            $startDate = \DateTime::createFromFormat('Y-m-d\TH:i', $data['start']);
            if (!$startDate) {
                $startDate = new \DateTime($data['start']);
            }
            $event->setStartDate($startDate);
            
            $endDate = \DateTime::createFromFormat('Y-m-d\TH:i', $data['end'] ?? $data['start']);
            if (!$endDate) {
                $endDate = new \DateTime($data['end'] ?? $data['start']);
            }
            $event->setEndDate($endDate);
            
            // Mapper le type frontend -> backend
            $frontendType = $data['type'] ?? 'other';
            $backendType = self::TYPE_MAPPING[$frontendType] ?? 'Autre';
            $event->setType($backendType);
            
            $event->setLocation($data['location'] ?? '');
            $event->setDescription($data['description'] ?? '');
            
            // Si calendarId est fourni, associer l'événement à ce calendrier
            if (isset($data['calendarId']) && !empty($data['calendarId'])) {
                error_log('📌 Cherche calendar avec ID: ' . $data['calendarId']);
                $calendar = $entityManager->getRepository(Calendar::class)->find($data['calendarId']);
                error_log('📌 Calendar trouvé: ' . ($calendar ? 'OUI' : 'NON'));
                if ($calendar) {
                    $event->setCalendar($calendar);
                    error_log('📌 Calendar associé: ' . $calendar->getName());
                    // Utiliser la couleur du calendrier si disponible
                    $event->setColor($calendar->getColor());
                } else {
                    error_log('❌ Calendar NOT FOUND with ID: ' . $data['calendarId']);
                    return $this->json(['error' => 'Calendar not found'], 404);
                }
            } else {
                // Événement général, utiliser la couleur basée sur le type (frontend)
                $colorMap = [
                    'course' => self::COLOR_BLUE,
                    'meeting' => self::COLOR_GREEN,
                    'exam' => self::COLOR_RED,
                    'training' => self::COLOR_ORANGE,
                    'other' => '#9c27b0'
                ];
                $event->setColor($colorMap[$frontendType] ?? self::COLOR_BLUE);
            }

            // Gestion de la récurrence
            $isRecurring = $data['isRecurring'] ?? false;
            $event->setIsRecurrent($isRecurring);
            
            if ($isRecurring) {
                $event->setRecurrenceType($data['recurrenceType'] ?? 'weekly');
                $event->setRecurrenceInterval((int)($data['recurrenceInterval'] ?? 1));
                $event->setRecurrenceDays($data['recurrenceDays'] ?? null);
                
                if (!empty($data['recurrenceEndDate'])) {
                    $recurrenceEndDate = new \DateTime($data['recurrenceEndDate']);
                    $event->setRecurrenceEndDate($recurrenceEndDate);
                }
            }

            // Persister l'événement principal
            $entityManager->persist($event);
            $entityManager->flush();

            // Si récurrent, générer les occurrences
            if ($isRecurring && !empty($data['recurrenceEndDate'])) {
                $this->generateRecurringEvents($event, $entityManager);
            }

            // Log the action if user is admin
            $this->tryLogAction('logEventCreated', $event->getId(), [
                'title' => $event->getTitle(),
                'start' => $event->getStartDate()->format('Y-m-d H:i'),
                'end' => $event->getEndDate()->format('Y-m-d H:i'),
                'type' => $event->getType(),
                'calendarId' => $event->getCalendar()?->getId(),
                'calendarName' => $event->getCalendar()?->getName(),
                'isRecurrent' => $event->isIsRecurrent()
            ]);

            // Retourner l'événement au format FullCalendar
            return $this->json($this->eventToArray($event), 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Génère les occurrences d'un événement récurrent
     */
    private function generateRecurringEvents(Event $parentEvent, EntityManagerInterface $entityManager): void
    {
        $recurrenceType = $parentEvent->getRecurrenceType();
        $interval = $parentEvent->getRecurrenceInterval() ?? 1;
        $recurrenceDays = $parentEvent->getRecurrenceDays() ?? [];
        $endDate = $parentEvent->getRecurrenceEndDate();
        
        if (!$endDate) {
            return;
        }

        // Convertir en DateTime pour pouvoir utiliser modify() et add()
        $currentStart = new \DateTime($parentEvent->getStartDate()->format('Y-m-d H:i:s'));
        $currentEnd = new \DateTime($parentEvent->getEndDate()->format('Y-m-d H:i:s'));
        $recurrenceEndDate = new \DateTime($endDate->format('Y-m-d'));
        $duration = $currentStart->diff($currentEnd);
        
        // Map des jours de la semaine
        $dayMap = [
            'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3,
            'thu' => 4, 'fri' => 5, 'sat' => 6
        ];
        
        // Limiter à 52 occurrences max pour éviter les problèmes de performance
        $maxOccurrences = 52;
        $occurrenceCount = 0;
        
        while ($currentStart <= $recurrenceEndDate && $occurrenceCount < $maxOccurrences) {
            // Calculer la prochaine date selon le type de récurrence
            switch ($recurrenceType) {
                case 'daily':
                    $currentStart->modify("+{$interval} day");
                    break;
                    
                case 'weekly':
                case 'biweekly':
                    $weeksToAdd = $recurrenceType === 'biweekly' ? 2 * $interval : $interval;
                    
                    if (!empty($recurrenceDays)) {
                        // Avancer au prochain jour spécifié
                        $found = false;
                        $daysChecked = 0;
                        while (!$found && $daysChecked < 14) {
                            $currentStart->modify('+1 day');
                            $currentDayNum = (int)$currentStart->format('w');
                            foreach ($recurrenceDays as $day) {
                                if (isset($dayMap[$day]) && $dayMap[$day] === $currentDayNum) {
                                    $found = true;
                                    break;
                                }
                            }
                            $daysChecked++;
                        }
                        if (!$found) {
                            $currentStart->modify("+{$weeksToAdd} week");
                        }
                    } else {
                        $currentStart->modify("+{$weeksToAdd} week");
                    }
                    break;
                    
                case 'monthly':
                    $currentStart->modify("+{$interval} month");
                    break;
                    
                case 'yearly':
                    $currentStart->modify("+{$interval} year");
                    break;
                    
                default:
                    return;
            }
            
            if ($currentStart > $recurrenceEndDate) {
                break;
            }
            
            // Calculer la date de fin
            $currentEnd = clone $currentStart;
            $currentEnd->add($duration);
            
            // Créer l'occurrence
            $occurrence = new Event();
            $occurrence->setTitle($parentEvent->getTitle());
            $occurrence->setDescription($parentEvent->getDescription());
            $occurrence->setStartDate(clone $currentStart);
            $occurrence->setEndDate(clone $currentEnd);
            $occurrence->setLocation($parentEvent->getLocation());
            $occurrence->setType($parentEvent->getType());
            $occurrence->setColor($parentEvent->getColor());
            $occurrence->setCalendar($parentEvent->getCalendar());
            $occurrence->setCreatedBy($parentEvent->getCreatedBy());
            $occurrence->setParentEvent($parentEvent);
            $occurrence->setIsRecurrent(false); // Les occurrences ne sont pas récurrentes elles-mêmes
            
            $entityManager->persist($occurrence);
            $occurrenceCount++;
        }
        
        $entityManager->flush();
    }

    #[Route('/events/{id}', name: 'events_delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager, EventRepository $eventRepository): JsonResponse
    {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            return $this->json(['error' => 'Event not found'], 404);
        }

        // Sauvegarder les données pour le log
        $eventData = [
            'title' => $event->getTitle(),
            'start' => $event->getStartDate()->format('Y-m-d H:i'),
            'end' => $event->getEndDate()->format('Y-m-d H:i'),
            'type' => $event->getType(),
            'calendarId' => $event->getCalendar()?->getId(),
            'calendarName' => $event->getCalendar()?->getName()
        ];
        $eventId = $event->getId();

        // Vérifier si on doit supprimer toute la série
        $deleteSeries = $request->query->get('deleteSeries') === 'true';
        
        if ($deleteSeries) {
            // Si c'est un événement parent (récurrent), supprimer toutes ses occurrences
            if ($event->isIsRecurrent()) {
                // Supprimer toutes les occurrences enfants
                foreach ($event->getChildEvents() as $childEvent) {
                    $entityManager->remove($childEvent);
                }
            }
            
            // Si c'est une occurrence (a un parent), supprimer le parent et toutes les occurrences
            $parentEvent = $event->getParentEvent();
            if ($parentEvent) {
                // Supprimer toutes les occurrences du parent
                foreach ($parentEvent->getChildEvents() as $childEvent) {
                    $entityManager->remove($childEvent);
                }
                // Supprimer le parent lui-même
                $entityManager->remove($parentEvent);
                $entityManager->flush();
                
                // Log deletion of series
                $this->tryLogAction('logEventDeleted', $eventId, array_merge($eventData, ['deletedSeries' => true]));
                
                return $this->json(['message' => 'Event series deleted successfully'], 200);
            }
        } else {
            // Suppression d'un seul événement
            // Si c'est un événement parent récurrent avec des enfants
            if ($event->isIsRecurrent() && count($event->getChildEvents()) > 0) {
                $children = $event->getChildEvents()->toArray();
                
                // Trouver le premier enfant qui deviendra le nouveau parent
                $newParent = array_shift($children);
                
                // Transférer les propriétés de récurrence au nouveau parent
                $newParent->setIsRecurrent(true);
                $newParent->setRecurrenceType($event->getRecurrenceType());
                $newParent->setRecurrenceInterval($event->getRecurrenceInterval());
                $newParent->setRecurrenceDays($event->getRecurrenceDays());
                $newParent->setRecurrenceEndDate($event->getRecurrenceEndDate());
                $newParent->setParentEvent(null); // Il devient le nouveau parent
                
                // Réattribuer les autres enfants au nouveau parent
                foreach ($children as $child) {
                    $child->setParentEvent($newParent);
                }
                
                $entityManager->remove($event);
                $entityManager->flush();
                
                // Log deletion with promotion info
                $this->tryLogAction('logEventDeleted', $eventId, array_merge($eventData, [
                    'promotedChildId' => $newParent->getId()
                ]));
                
                return $this->json([
                    'message' => 'Event deleted, series continues with remaining occurrences',
                    'newParentId' => $newParent->getId()
                ], 200);
            }
        }

        // Supprimer l'événement (simple ou parent sans enfants)
        $entityManager->remove($event);
        $entityManager->flush();

        // Log the deletion
        $this->tryLogAction('logEventDeleted', $eventId, $eventData);

        return $this->json(['message' => 'Event deleted successfully'], 200);
    }

    #[Route('/events/{id}', name: 'events_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, EventRepository $eventRepository): JsonResponse
    {
        try {
            $event = $eventRepository->find($id);
            
            if (!$event) {
                return $this->json(['error' => 'Event not found'], 404);
            }

            // Sauvegarder les anciennes données pour le log
            $oldData = [
                'title' => $event->getTitle(),
                'start' => $event->getStartDate()->format('Y-m-d H:i'),
                'end' => $event->getEndDate()->format('Y-m-d H:i'),
                'type' => $event->getType(),
                'location' => $event->getLocation(),
                'description' => $event->getDescription()
            ];

            $data = json_decode($request->getContent(), true);

            // Mettre à jour les champs si fournis
            if (isset($data['title'])) {
                $event->setTitle($data['title']);
            }
            if (isset($data['start'])) {
                $startDate = \DateTime::createFromFormat('Y-m-d\TH:i', $data['start']);
                if (!$startDate) {
                    $startDate = new \DateTime($data['start']);
                }
                $event->setStartDate($startDate);
            }
            if (isset($data['end'])) {
                $endDate = \DateTime::createFromFormat('Y-m-d\TH:i', $data['end']);
                if (!$endDate) {
                    $endDate = new \DateTime($data['end']);
                }
                $event->setEndDate($endDate);
            }
            if (isset($data['type'])) {
                $frontendType = $data['type'];
                $backendType = self::TYPE_MAPPING[$frontendType] ?? 'Autre';
                $event->setType($backendType);
                
                // Mettre à jour la couleur basée sur le type (frontend)
                $colorMap = [
                    'course' => self::COLOR_BLUE,
                    'meeting' => self::COLOR_GREEN,
                    'exam' => self::COLOR_RED,
                    'training' => self::COLOR_ORANGE,
                    'other' => '#9c27b0'
                ];
                $event->setColor($colorMap[$frontendType] ?? self::COLOR_BLUE);
            }
            if (isset($data['location'])) {
                $event->setLocation($data['location']);
            }
            if (isset($data['description'])) {
                $event->setDescription($data['description']);
            }

            // Persister les modifications
            $entityManager->persist($event);
            $entityManager->flush();

            // Log the update if user is admin
            $newData = [
                'title' => $event->getTitle(),
                'start' => $event->getStartDate()->format('Y-m-d H:i'),
                'end' => $event->getEndDate()->format('Y-m-d H:i'),
                'type' => $event->getType(),
                'location' => $event->getLocation(),
                'description' => $event->getDescription()
            ];
            $this->tryLogAction('logEventUpdated', $event->getId(), $oldData, $newData);

            // Retourner l'événement mis à jour
            return $this->json($this->eventToArray($event), 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Convertir une entité Event au format FullCalendar
     */
    private function eventToArray(Event $event): array
    {
        $startDate = $event->getStartDate() ? $event->getStartDate()->format('Y-m-d\TH:i:s') : null;
        $endDate = $event->getEndDate() ? $event->getEndDate()->format('Y-m-d\TH:i:s') : null;
        
        // Si endDate est absent, le générer à partir de startDate + 1 heure
        if (!$endDate && $startDate) {
            $start = new \DateTime($startDate);
            $start->add(new \DateInterval('PT1H'));
            $endDate = $start->format('Y-m-d\TH:i:s');
        }
        
        $calendar = $event->getCalendar();
        error_log('📊 Event ID ' . $event->getId() . ' - Calendar: ' . ($calendar ? $calendar->getName() : 'NULL'));
        
        // Convertir le type backend -> frontend
        $backendType = $event->getType();
        $frontendType = self::TYPE_MAPPING_REVERSE[$backendType] ?? 'other';
        
        // Informations de récurrence
        $recurrenceInfo = null;
        if ($event->isIsRecurrent()) {
            $recurrenceInfo = [
                'type' => $event->getRecurrenceType(),
                'interval' => $event->getRecurrenceInterval(),
                'days' => $event->getRecurrenceDays(),
                'endDate' => $event->getRecurrenceEndDate() ? $event->getRecurrenceEndDate()->format('Y-m-d') : null
            ];
        }
        
        // Vérifier si c'est une occurrence d'un événement parent
        $parentEventId = $event->getParentEvent() ? $event->getParentEvent()->getId() : null;
        
        return [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'start' => $startDate,
            'end' => $endDate,
            'backgroundColor' => $event->getColor(),
            'borderColor' => $event->getColor(),
            'extendedProps' => [
                'type' => $frontendType,
                'location' => $event->getLocation(),
                'description' => $event->getDescription(),
                'calendarId' => $calendar ? $calendar->getId() : null,
                'calendarName' => $calendar ? $calendar->getName() : 'Événement général',
                'isRecurring' => $event->isIsRecurrent(),
                'recurrence' => $recurrenceInfo,
                'parentEventId' => $parentEventId
            ]
        ];
    }

    #[Route('/debug', name: 'debug', methods: ['GET'])]
    public function debug(
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository
    ): JsonResponse {
        $user = $this->getUser();
        
        // Infos utilisateur
        $debug = [
            'user' => $user instanceof User ? [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'authenticated' => true
            ] : [
                'authenticated' => false
            ]
        ];
        
        // Tous les calendriers
        $allCalendars = $entityManager->getRepository(Calendar::class)->findAll();
        $debug['calendars'] = [];
        foreach ($allCalendars as $cal) {
            $debug['calendars'][] = [
                'id' => $cal->getId(),
                'name' => $cal->getName(),
                'public' => $cal->isPublic(),
                'owner_id' => $cal->getOwner() ? $cal->getOwner()->getId() : null,
                'owner_email' => $cal->getOwner() ? $cal->getOwner()->getEmail() : null
            ];
        }
        
        // Tous les événements (brut)
        $allEvents = $entityManager->getRepository(Event::class)->findAll();
        $debug['total_events_in_db'] = count($allEvents);
        $debug['events'] = [];
        foreach ($allEvents as $evt) {
            $debug['events'][] = [
                'id' => $evt->getId(),
                'title' => $evt->getTitle(),
                'calendar_id' => $evt->getCalendar() ? $evt->getCalendar()->getId() : null,
                'calendar_name' => $evt->getCalendar() ? $evt->getCalendar()->getName() : null,
                'calendar_public' => $evt->getCalendar() ? $evt->getCalendar()->isPublic() : null
            ];
        }
        
        return $this->json($debug);
    }
}
