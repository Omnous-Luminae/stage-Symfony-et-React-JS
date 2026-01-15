<?php

namespace App\Controller\Api;

use App\Entity\Calendar;
use App\Entity\CalendarPermission;
use App\Entity\User;
use App\Repository\CalendarRepository;
use App\Repository\CalendarPermissionRepository;
use App\Repository\UserRepository;
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
    public function list(CalendarRepository $calendarRepository, SerializerInterface $serializer): JsonResponse
    {
        $calendars = $calendarRepository->findAll();
        $data = $serializer->serialize($calendars, 'json', ['groups' => 'calendar:read']);
        
        return $this->json(json_decode($data, true));
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

            $calendar = new Calendar();
            $calendar->setName($data['name']);
            $calendar->setDescription($data['description'] ?? '');
            $calendar->setColor($data['color'] ?? '#667eea');
            $calendar->setType(Calendar::TYPE_PERSONAL);

            // Associer le propriétaire (utilisateur actuellement authentifié)
            // Pour l'instant, on utilise un utilisateur par défaut
            $user = $userRepository->findOneBy(['email' => 'demo@example.com']);
            if ($user) {
                $calendar->setOwner($user);
            }

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
    public function delete(Calendar $calendar, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
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
        SerializerInterface $serializer
    ): JsonResponse {
        try {
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

            return $this->json(['message' => 'Calendar shared successfully'], 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/permissions', name: 'permissions', methods: ['GET'])]
    public function getPermissions(Calendar $calendar, SerializerInterface $serializer): JsonResponse
    {
        $permissions = $calendar->getPermissions();
        $jsonData = $serializer->serialize($permissions, 'json', ['groups' => 'permission:read']);
        return $this->json(json_decode($jsonData, true));
    }

    #[Route('/{id}/permissions/{permissionId}', name: 'remove_permission', methods: ['DELETE'])]
    public function removePermission(
        int $id,
        int $permissionId,
        EntityManagerInterface $entityManager,
        CalendarPermissionRepository $permissionRepository
    ): JsonResponse {
        try {
            $permission = $permissionRepository->find($permissionId);

            if (!$permission) {
                return $this->json(['error' => 'Permission not found'], 404);
            }

            $entityManager->remove($permission);
            $entityManager->flush();

            return $this->json(['message' => 'Permission removed successfully'], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
