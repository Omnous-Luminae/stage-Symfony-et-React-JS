<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Calendar;
use App\Entity\User;
use App\Repository\CalendarPermissionRepository;
use App\Repository\EventRepository;
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

            // Persister l'événement
            $entityManager->persist($event);
            $entityManager->flush();

            // Retourner l'événement au format FullCalendar
            return $this->json($this->eventToArray($event), 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/events/{id}', name: 'events_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager, EventRepository $eventRepository): JsonResponse
    {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            return $this->json(['error' => 'Event not found'], 404);
        }

        // Supprimer l'événement
        $entityManager->remove($event);
        $entityManager->flush();

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
                'calendarName' => $calendar ? $calendar->getName() : 'Événement général'
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

    #[Route('/calendars', name: 'calendars_list', methods: ['GET'])]
    public function calendars(EntityManagerInterface $entityManager): JsonResponse
    {
        $calendars = $entityManager->getRepository(Calendar::class)->findAll();
        
        $result = [];
        foreach ($calendars as $calendar) {
            $owner = $calendar->getOwner();
            $result[] = [
                'id' => $calendar->getId(),
                'name' => $calendar->getName(),
                'color' => $calendar->getColor(),
                'owner_id' => $owner instanceof User ? $owner->getId() : null
            ];
        }

        return $this->json($result);
    }
}
