<?php

namespace App\Controller\Api;

use App\Entity\Calendar;
use App\Entity\CalendarPermission;
use App\Entity\User;
use App\Repository\AdministratorRepository;
use App\Repository\CalendarRepository;
use App\Repository\CalendarPermissionRepository;
use App\Repository\UserRepository;
use App\Service\AuditLogService;
use App\Service\SessionUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/calendars', name: 'api_calendars_')]
class CalendarController extends AbstractController
{
    public function __construct(
        private AuditLogService $auditLogService,
        private AdministratorRepository $adminRepository,
        private SessionUserService $sessionUserService
    ) {}

    private function tryLogAction(string $method, ...$args): void
    {
        try {
            $user = $this->sessionUserService->getCurrentUser();
            if ($user && $this->adminRepository->findByUser($user)) {
                $this->auditLogService->$method(...$args);
            }
        } catch (\Exception $e) {
            // Log silently fails
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(CalendarRepository $calendarRepository, CalendarPermissionRepository $permissionRepository, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->sessionUserService->getCurrentUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        error_log('📅 CalendarController::list - User: ' . $user->getEmail() . ' (ID: ' . $user->getId() . ')');

        // Get user's own calendars
        $ownedCalendars = $calendarRepository->findBy(['owner' => $user]);
        error_log('📅 Owned calendars: ' . count($ownedCalendars));

        // Get shared calendars (where user has permission but is NOT the owner)
        $sharedPermissions = $permissionRepository->findBy(['user' => $user]);
        error_log('📅 Shared permissions: ' . count($sharedPermissions));
        
        $sharedCalendars = [];
        foreach ($sharedPermissions as $permission) {
            $calendar = $permission->getCalendar();
            // Ne pas inclure les calendriers dont l'utilisateur est propriétaire
            if ($calendar && $calendar->getOwner() && $calendar->getOwner()->getId() !== $user->getId()) {
                $sharedCalendars[] = [
                    'calendar' => $calendar,
                    'permission' => $permission->getPermission(),
                    'ownerName' => $calendar->getOwner()->getFirstName() . ' ' . $calendar->getOwner()->getLastName()
                ];
            }
        }

        // Get public calendars (calendrier général)
        $publicCalendars = $calendarRepository->findBy(['type' => Calendar::TYPE_PUBLIC]);
        error_log('📅 Public calendars: ' . count($publicCalendars));

        // Check if user is admin
        $isAdmin = $this->adminRepository->findByUser($user) !== null;

        // Build response with ownership info
        $result = [];
        
        // Add owned calendars
        foreach ($ownedCalendars as $calendar) {
            $result[] = [
                'id' => $calendar->getId(),
                'name' => $calendar->getName(),
                'description' => $calendar->getDescription(),
                'color' => $calendar->getColor(),
                'type' => 'personal',
                'isOwner' => true,
                'owner_id' => $user->getId()
            ];
        }
        
        // Add shared calendars
        foreach ($sharedCalendars as $shared) {
            $calendar = $shared['calendar'];
            $result[] = [
                'id' => $calendar->getId(),
                'name' => $calendar->getName(),
                'description' => $calendar->getDescription(),
                'color' => $calendar->getColor(),
                'type' => 'shared',
                'isOwner' => false,
                'permission' => $shared['permission'],
                'ownerName' => $shared['ownerName'],
                'owner_id' => $calendar->getOwner()->getId()
            ];
        }

        // Add public calendars (calendrier général)
        foreach ($publicCalendars as $calendar) {
            // Éviter les doublons si le calendrier public est aussi possédé par l'utilisateur
            $alreadyAdded = false;
            foreach ($result as $r) {
                if ($r['id'] === $calendar->getId()) {
                    $alreadyAdded = true;
                    break;
                }
            }
            if (!$alreadyAdded) {
                $result[] = [
                    'id' => $calendar->getId(),
                    'name' => $calendar->getName(),
                    'description' => $calendar->getDescription(),
                    'color' => $calendar->getColor(),
                    'type' => 'public',
                    'isOwner' => false,
                    'isPublic' => true,
                    'canEdit' => $isAdmin, // Seuls les admins peuvent modifier
                    'owner_id' => $calendar->getOwner()?->getId()
                ];
            }
        }

        error_log('📅 Total calendars returned: ' . count($result));

        return $this->json($result);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (empty($data['name'])) {
                return $this->json(['error' => 'Name is required'], 400);
            }

            // Vérifier que l'utilisateur est authentifié et de type User
            $user = $this->sessionUserService->getCurrentUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'Not authenticated'], 401);
            }

            $calendar = new Calendar();
            $calendar->setName($data['name']);
            $calendar->setDescription($data['description'] ?? '');
            $calendar->setColor($data['color'] ?? '#667eea');
            $calendar->setType(Calendar::TYPE_PERSONAL);
            $calendar->setOwner($user);

            $entityManager->persist($calendar);
            $entityManager->flush();

            // Log the action if user is admin
            $this->tryLogAction('logCalendarCreated', $calendar->getId(), [
                'name' => $calendar->getName(),
                'description' => $calendar->getDescription(),
                'color' => $calendar->getColor(),
                'ownerEmail' => $user->getEmail()
            ]);

            $entityManager->persist($calendar);
            $entityManager->flush();

            $jsonData = $serializer->serialize($calendar, 'json', ['groups' => 'calendar:read']);
            return $this->json(json_decode($jsonData, true), 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Calendar $calendar, SerializerInterface $serializer): JsonResponse
    {
        $jsonData = $serializer->serialize($calendar, 'json', ['groups' => 'calendar:detail']);
        return $this->json(json_decode($jsonData, true));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        Calendar $calendar,
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            // Save old values for logging
            $oldData = [
                'name' => $calendar->getName(),
                'description' => $calendar->getDescription(),
                'color' => $calendar->getColor(),
            ];

            if (isset($data['name'])) {
                $calendar->setName($data['name']);
            }
            if (isset($data['description'])) {
                $calendar->setDescription($data['description']);
            }
            if (isset($data['color'])) {
                $calendar->setColor($data['color']);
            }

            $entityManager->persist($calendar);
            $entityManager->flush();

            // Log the action if user is admin
            $this->tryLogAction('logCalendarUpdated', $calendar->getId(), $oldData, [
                'name' => $calendar->getName(),
                'description' => $calendar->getDescription(),
                'color' => $calendar->getColor(),
            ]);

            $jsonData = $serializer->serialize($calendar, 'json', ['groups' => 'calendar:read']);
            return $this->json(json_decode($jsonData, true), 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, CalendarRepository $calendarRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $calendar = $calendarRepository->find($id);
            if (!$calendar) {
                return $this->json(['error' => 'Calendar not found'], 404);
            }

            // Verify user owns this calendar
            $currentUser = $this->sessionUserService->getCurrentUser();
            $currentUserId = $currentUser instanceof User ? $currentUser->getId() : null;
            $calendarOwnerId = $calendar->getOwner() instanceof User ? $calendar->getOwner()->getId() : null;
            if ($calendarOwnerId !== $currentUserId && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'Unauthorized'], 403);
            }

            // Save data for logging
            $calendarData = [
                'name' => $calendar->getName(),
                'description' => $calendar->getDescription(),
                'ownerEmail' => $calendar->getOwner()?->getEmail()
            ];
            $calendarId = $calendar->getId();

            $entityManager->remove($calendar);
            $entityManager->flush();

            // Log the action if user is admin
            $this->tryLogAction('logCalendarDeleted', $calendarId, $calendarData);

            return $this->json(['message' => 'Calendar deleted successfully'], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/share', name: 'share', methods: ['POST'])]
    public function share(
        Calendar $calendar,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        SessionUserService $sessionUserService
    ): JsonResponse {
        try {
            $currentUser = $sessionUserService->getCurrentUser();
            if (!$currentUser instanceof User) {
                return $this->json(['error' => 'Not authenticated'], 401);
            }

            $isOwner = $calendar->getOwner() instanceof User && $calendar->getOwner()->getId() === $currentUser->getId();
            if (!$isOwner && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'Unauthorized'], 403);
            }

            $data = json_decode($request->getContent(), true);

            if (empty($data['email'])) {
                return $this->json(['error' => 'Email is required'], 400);
            }

            $user = $userRepository->findOneBy(['email' => $data['email']]);
            if (!$user) {
                return $this->json(['error' => 'User not found'], 404);
            }

            // Vérifier si la permission existe déjà
            $permission = $entityManager->getRepository(CalendarPermission::class)
                ->findOneBy(['calendar' => $calendar, 'user' => $user]);

            if ($permission) {
                return $this->json(['error' => 'User already has access to this calendar'], 400);
            }

            $permission = new CalendarPermission();
            $permission->setCalendar($calendar);
            $permission->setUser($user);
            $permission->setPermission($data['permission'] ?? CalendarPermission::PERMISSION_CONSULTATION);

            $entityManager->persist($permission);
            $entityManager->flush();

            $jsonData = $serializer->serialize($permission, 'json', ['groups' => 'permission:read']);

            return $this->json(json_decode($jsonData, true), 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/permissions', name: 'permissions', methods: ['GET'])]
    public function getPermissions(Calendar $calendar, SerializerInterface $serializer, SessionUserService $sessionUserService): JsonResponse
    {
        $currentUser = $sessionUserService->getCurrentUser();
        $isOwner = $calendar->getOwner() instanceof User && $currentUser instanceof User && $calendar->getOwner()->getId() === $currentUser->getId();
        if (!$isOwner && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $permissions = $calendar->getPermissions();
        $jsonData = $serializer->serialize($permissions, 'json', ['groups' => 'permission:read']);
        return $this->json(json_decode($jsonData, true));
    }

    #[Route('/{id}/permissions/{permissionId}', name: 'remove_permission', methods: ['DELETE'])]
    public function removePermission(
        int $id,
        int $permissionId,
        EntityManagerInterface $entityManager,
        CalendarPermissionRepository $permissionRepository,
        SessionUserService $sessionUserService
    ): JsonResponse {
        try {
            $currentUser = $sessionUserService->getCurrentUser();
            if (!$currentUser && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'Not authenticated'], 401);
            }

            $permission = $permissionRepository->find($permissionId);

            if (!$permission) {
                return $this->json(['error' => 'Permission not found'], 404);
            }

            $calendar = $permission->getCalendar();
            $isOwner = $calendar && $calendar->getOwner() instanceof User && $currentUser instanceof User && $calendar->getOwner()->getId() === $currentUser->getId();
            if (!$isOwner && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'Unauthorized'], 403);
            }

            $entityManager->remove($permission);
            $entityManager->flush();

            return $this->json(['message' => 'Permission removed successfully'], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/general/init', name: 'init_general', methods: ['POST'])]
    public function initGeneralCalendar(
        CalendarRepository $calendarRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifier que l'utilisateur est admin
        $user = $this->sessionUserService->getCurrentUser();
        if (!$user || !$this->adminRepository->findByUser($user)) {
            return $this->json(['error' => 'Admin access required'], 403);
        }

        // Vérifier si un calendrier général existe déjà
        $existingPublic = $calendarRepository->findOneBy(['type' => Calendar::TYPE_PUBLIC]);
        if ($existingPublic) {
            return $this->json([
                'message' => 'Le calendrier général existe déjà',
                'calendar' => [
                    'id' => $existingPublic->getId(),
                    'name' => $existingPublic->getName(),
                    'description' => $existingPublic->getDescription(),
                    'color' => $existingPublic->getColor(),
                    'type' => 'public'
                ]
            ]);
        }

        // Créer le calendrier général
        $calendar = new Calendar();
        $calendar->setName('📢 Calendrier Général');
        $calendar->setDescription('Calendrier commun visible par tous les utilisateurs. Seuls les administrateurs peuvent y ajouter des événements.');
        $calendar->setColor('#ef4444'); // Rouge pour le distinguer
        $calendar->setType(Calendar::TYPE_PUBLIC);
        $calendar->setOwner(null); // Pas de propriétaire spécifique

        $entityManager->persist($calendar);
        $entityManager->flush();

        // Log l'action
        $this->tryLogAction('logCalendarCreated', $calendar->getId(), [
            'name' => $calendar->getName(),
            'description' => $calendar->getDescription(),
            'color' => $calendar->getColor(),
            'type' => 'public'
        ]);

        return $this->json([
            'message' => 'Calendrier général créé avec succès',
            'calendar' => [
                'id' => $calendar->getId(),
                'name' => $calendar->getName(),
                'description' => $calendar->getDescription(),
                'color' => $calendar->getColor(),
                'type' => 'public'
            ]
        ], 201);
    }

    #[Route('/general', name: 'get_general', methods: ['GET'])]
    public function getGeneralCalendar(CalendarRepository $calendarRepository): JsonResponse
    {
        $user = $this->sessionUserService->getCurrentUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $calendar = $calendarRepository->findOneBy(['type' => Calendar::TYPE_PUBLIC]);
        
        if (!$calendar) {
            return $this->json(['error' => 'Calendrier général non trouvé', 'exists' => false], 404);
        }

        $isAdmin = $this->adminRepository->findByUser($user) !== null;

        return $this->json([
            'exists' => true,
            'calendar' => [
                'id' => $calendar->getId(),
                'name' => $calendar->getName(),
                'description' => $calendar->getDescription(),
                'color' => $calendar->getColor(),
                'type' => 'public',
                'canEdit' => $isAdmin
            ]
        ]);
    }
}
