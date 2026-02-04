<?php

namespace App\Controller;

use App\Entity\EventType;
use App\Repository\AdministratorRepository;
use App\Repository\EventTypeRepository;
use App\Service\SessionUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/event-types')]
class EventTypeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventTypeRepository $eventTypeRepository,
        private AdministratorRepository $adminRepository,
        private SessionUserService $sessionUserService
    ) {}

    /**
     * Vérifie si l'utilisateur actuel est admin
     */
    private function isCurrentUserAdmin(): bool
    {
        $user = $this->sessionUserService->getCurrentUser();
        if (!$user) {
            return false;
        }
        return $this->adminRepository->findByUser($user) !== null;
    }

    /**
     * Liste tous les types d'événements actifs
     */
    #[Route('', name: 'api_event_types_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $eventTypes = $this->eventTypeRepository->findAllActive();

        $data = array_map(function (EventType $type) {
            return [
                'id' => $type->getId(),
                'name' => $type->getName(),
                'code' => $type->getCode(),
                'description' => $type->getDescription(),
                'color' => $type->getColor(),
                'icon' => $type->getIcon(),
                'displayOrder' => $type->getDisplayOrder(),
            ];
        }, $eventTypes);

        return $this->json($data);
    }

    /**
     * Liste TOUS les types (y compris inactifs) - Admin seulement
     */
    #[Route('/all', name: 'api_event_types_list_all', methods: ['GET'])]
    public function listAll(): JsonResponse
    {
        if (!$this->isCurrentUserAdmin()) {
            return $this->json(['error' => 'Accès réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $eventTypes = $this->eventTypeRepository->findBy([], ['displayOrder' => 'ASC']);

        $data = array_map(function (EventType $type) {
            return [
                'id' => $type->getId(),
                'name' => $type->getName(),
                'code' => $type->getCode(),
                'description' => $type->getDescription(),
                'color' => $type->getColor(),
                'icon' => $type->getIcon(),
                'isActive' => $type->isActive(),
                'displayOrder' => $type->getDisplayOrder(),
                'eventsCount' => $this->eventTypeRepository->countEventsForType($type),
                'createdAt' => $type->getCreatedAt()?->format('c'),
                'updatedAt' => $type->getUpdatedAt()?->format('c'),
            ];
        }, $eventTypes);

        return $this->json($data);
    }

    /**
     * Récupère un type par son ID
     */
    #[Route('/{id}', name: 'api_event_types_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        $eventType = $this->eventTypeRepository->find($id);

        if (!$eventType) {
            return $this->json(['error' => 'Type d\'événement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $eventType->getId(),
            'name' => $eventType->getName(),
            'code' => $eventType->getCode(),
            'description' => $eventType->getDescription(),
            'color' => $eventType->getColor(),
            'icon' => $eventType->getIcon(),
            'isActive' => $eventType->isActive(),
            'displayOrder' => $eventType->getDisplayOrder(),
        ]);
    }

    /**
     * Crée un nouveau type d'événement - Admin seulement
     */
    #[Route('', name: 'api_event_types_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if (!$this->isCurrentUserAdmin()) {
            return $this->json(['error' => 'Accès réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom est requis'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['code'])) {
            return $this->json(['error' => 'Le code est requis'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que le code est unique
        if ($this->eventTypeRepository->codeExists($data['code'])) {
            return $this->json(['error' => 'Ce code existe déjà'], Response::HTTP_CONFLICT);
        }

        $eventType = new EventType();
        $eventType->setName($data['name']);
        $eventType->setCode($data['code']);
        $eventType->setDescription($data['description'] ?? null);
        $eventType->setColor($data['color'] ?? '#3788d8');
        $eventType->setIcon($data['icon'] ?? null);
        $eventType->setIsActive($data['isActive'] ?? true);
        $eventType->setDisplayOrder($data['displayOrder'] ?? 0);

        $this->entityManager->persist($eventType);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Type d\'événement créé avec succès',
            'eventType' => [
                'id' => $eventType->getId(),
                'name' => $eventType->getName(),
                'code' => $eventType->getCode(),
                'description' => $eventType->getDescription(),
                'color' => $eventType->getColor(),
                'icon' => $eventType->getIcon(),
                'isActive' => $eventType->isActive(),
                'displayOrder' => $eventType->getDisplayOrder(),
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Met à jour un type d'événement - Admin seulement
     */
    #[Route('/{id}', name: 'api_event_types_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->isCurrentUserAdmin()) {
            return $this->json(['error' => 'Accès réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $eventType = $this->eventTypeRepository->find($id);

        if (!$eventType) {
            return $this->json(['error' => 'Type d\'événement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Vérifier le code s'il est modifié
        if (isset($data['code']) && $data['code'] !== $eventType->getCode()) {
            if ($this->eventTypeRepository->codeExists($data['code'], $id)) {
                return $this->json(['error' => 'Ce code existe déjà'], Response::HTTP_CONFLICT);
            }
            $eventType->setCode($data['code']);
        }

        if (isset($data['name'])) {
            $eventType->setName($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $eventType->setDescription($data['description']);
        }
        if (isset($data['color'])) {
            $eventType->setColor($data['color']);
        }
        if (array_key_exists('icon', $data)) {
            $eventType->setIcon($data['icon']);
        }
        if (isset($data['isActive'])) {
            $eventType->setIsActive($data['isActive']);
        }
        if (isset($data['displayOrder'])) {
            $eventType->setDisplayOrder($data['displayOrder']);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Type d\'événement mis à jour avec succès',
            'eventType' => [
                'id' => $eventType->getId(),
                'name' => $eventType->getName(),
                'code' => $eventType->getCode(),
                'description' => $eventType->getDescription(),
                'color' => $eventType->getColor(),
                'icon' => $eventType->getIcon(),
                'isActive' => $eventType->isActive(),
                'displayOrder' => $eventType->getDisplayOrder(),
            ]
        ]);
    }

    /**
     * Supprime un type d'événement - Admin seulement
     */
    #[Route('/{id}', name: 'api_event_types_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        if (!$this->isCurrentUserAdmin()) {
            return $this->json(['error' => 'Accès réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $eventType = $this->eventTypeRepository->find($id);

        if (!$eventType) {
            return $this->json(['error' => 'Type d\'événement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si des événements utilisent ce type
        $eventsCount = $this->eventTypeRepository->countEventsForType($eventType);
        if ($eventsCount > 0) {
            return $this->json([
                'error' => "Impossible de supprimer ce type car $eventsCount événement(s) l'utilisent. Désactivez-le plutôt."
            ], Response::HTTP_CONFLICT);
        }

        $this->entityManager->remove($eventType);
        $this->entityManager->flush();

        return $this->json(['message' => 'Type d\'événement supprimé avec succès']);
    }

    /**
     * Réorganise l'ordre des types - Admin seulement
     */
    #[Route('/reorder', name: 'api_event_types_reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        if (!$this->isCurrentUserAdmin()) {
            return $this->json(['error' => 'Accès réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['orderedIds']) || !is_array($data['orderedIds'])) {
            return $this->json(['error' => 'Liste des IDs requise'], Response::HTTP_BAD_REQUEST);
        }

        $this->eventTypeRepository->reorder($data['orderedIds']);

        return $this->json(['message' => 'Ordre mis à jour avec succès']);
    }

    /**
     * Active/désactive un type - Admin seulement
     */
    #[Route('/{id}/toggle-active', name: 'api_event_types_toggle', methods: ['POST'])]
    public function toggleActive(int $id): JsonResponse
    {
        if (!$this->isCurrentUserAdmin()) {
            return $this->json(['error' => 'Accès réservé aux administrateurs'], Response::HTTP_FORBIDDEN);
        }

        $eventType = $this->eventTypeRepository->find($id);

        if (!$eventType) {
            return $this->json(['error' => 'Type d\'événement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $eventType->setIsActive(!$eventType->isActive());
        $this->entityManager->flush();

        return $this->json([
            'message' => $eventType->isActive() ? 'Type activé' : 'Type désactivé',
            'isActive' => $eventType->isActive()
        ]);
    }
}
