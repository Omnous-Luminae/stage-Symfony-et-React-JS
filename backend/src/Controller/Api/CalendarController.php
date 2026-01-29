<?php

namespace App\Controller\Api;

use App\Entity\Calendar;
use App\Entity\CalendarPermission;
use App\Entity\User;
use App\Repository\CalendarRepository;
use App\Repository\CalendarPermissionRepository;
use App\Repository\UserRepository;
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
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(CalendarRepository $calendarRepository, CalendarPermissionRepository $permissionRepository, SerializerInterface $serializer, SessionUserService $sessionUserService): JsonResponse
    {
        $user = $sessionUserService->getCurrentUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        // Get user's own calendars
        $ownedCalendars = $calendarRepository->findBy(['owner' => $user]);

        // Get shared calendars
        $sharedPermissions = $permissionRepository->findBy(['user' => $user]);
        $sharedCalendars = [];
        foreach ($sharedPermissions as $permission) {
            $calendar = $permission->getCalendar();
            if ($calendar) {
                $sharedCalendars[] = $calendar;
            }
        }

        $allCalendars = array_unique(array_merge($ownedCalendars, $sharedCalendars), SORT_REGULAR);

        $data = $serializer->serialize($allCalendars, 'json', ['groups' => 'calendar:read']);
        
        return $this->json(json_decode($data, true));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        SessionUserService $sessionUserService
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (empty($data['name'])) {
                return $this->json(['error' => 'Name is required'], 400);
            }

            // Vérifier que l'utilisateur est authentifié et de type User
            $user = $sessionUserService->getCurrentUser();
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

            $jsonData = $serializer->serialize($calendar, 'json', ['groups' => 'calendar:read']);
            return $this->json(json_decode($jsonData, true), 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, CalendarRepository $calendarRepository, EntityManagerInterface $entityManager, SessionUserService $sessionUserService): JsonResponse
    {
        try {
            $calendar = $calendarRepository->find($id);
            if (!$calendar) {
                return $this->json(['error' => 'Calendar not found'], 404);
            }

            // Verify user owns this calendar
            $currentUser = $sessionUserService->getCurrentUser();
            $currentUserId = $currentUser instanceof User ? $currentUser->getId() : null;
            $calendarOwnerId = $calendar->getOwner() instanceof User ? $calendar->getOwner()->getId() : null;
            if ($calendarOwnerId !== $currentUserId && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'Unauthorized'], 403);
            }

            $entityManager->remove($calendar);
            $entityManager->flush();

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
}
