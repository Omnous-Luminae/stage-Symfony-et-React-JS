<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Repository\EventRepository;
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
    public function list(EventRepository $eventRepository): JsonResponse
    {
        // Récupérer les événements de la base de données
        $dbEvents = $eventRepository->findAll();
        
        // Convertir les entités en format FullCalendar
        $events = [];
        foreach ($dbEvents as $event) {
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
            
            $event->setType($data['type'] ?? 'other');
            $event->setLocation($data['location'] ?? '');
            $event->setDescription($data['description'] ?? '');
            
            // Définir la couleur basée sur le type
            $colorMap = [
                'course' => self::COLOR_BLUE,
                'meeting' => self::COLOR_GREEN,
                'exam' => self::COLOR_RED,
                'training' => self::COLOR_ORANGE,
                'other' => '#9c27b0'
            ];
            $event->setColor($colorMap[$event->getType()] ?? self::COLOR_BLUE);

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
                $event->setType($data['type']);
            }
            if (isset($data['location'])) {
                $event->setLocation($data['location']);
            }
            if (isset($data['description'])) {
                $event->setDescription($data['description']);
            }

            // Mettre à jour la couleur basée sur le type si le type a changé
            if (isset($data['type'])) {
                $colorMap = [
                    'course' => self::COLOR_BLUE,
                    'meeting' => self::COLOR_GREEN,
                    'exam' => self::COLOR_RED,
                    'training' => self::COLOR_ORANGE,
                    'other' => '#9c27b0'
                ];
                $event->setColor($colorMap[$event->getType()] ?? self::COLOR_BLUE);
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
        return [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'start' => $event->getStartDate()->format('Y-m-d\TH:i:s'),
            'end' => $event->getEndDate()->format('Y-m-d\TH:i:s'),
            'backgroundColor' => $event->getColor(),
            'borderColor' => $event->getColor(),
            'extendedProps' => [
                'type' => $event->getType(),
                'location' => $event->getLocation(),
                'description' => $event->getDescription()
            ]
        ];
    }

    #[Route('/calendars', name: 'calendars_list', methods: ['GET'])]
    public function calendars(): JsonResponse
    {
        $calendars = [
            [
                'id' => 1,
                'name' => 'Mon Agenda Personnel',
                'type' => 'personal',
                'color' => self::COLOR_BLUE
            ],
            [
                'id' => 2,
                'name' => 'Professeurs Mathématiques',
                'type' => 'shared',
                'color' => self::COLOR_GREEN
            ],
            [
                'id' => 3,
                'name' => 'Réunions Lycée',
                'type' => 'shared',
                'color' => self::COLOR_RED
            ]
        ];

        return $this->json($calendars);
    }
}
