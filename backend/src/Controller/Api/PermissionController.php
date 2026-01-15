<?php

namespace App\Controller\Api;

use App\Entity\CalendarPermission;
use App\Repository\CalendarPermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/permissions', name: 'api_permissions_')]
class PermissionController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(CalendarPermissionRepository $permissionRepository, SerializerInterface $serializer): JsonResponse
    {
        $permissions = $permissionRepository->findAll();
        $jsonData = $serializer->serialize($permissions, 'json', ['groups' => 'permission:read']);
        return $this->json(json_decode($jsonData, true));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(CalendarPermission $permission, SerializerInterface $serializer): JsonResponse
    {
        $jsonData = $serializer->serialize($permission, 'json', ['groups' => 'permission:read']);
        return $this->json(json_decode($jsonData, true));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        CalendarPermission $permission,
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (isset($data['permission'])) {
                $permission->setPermission($data['permission']);
            }
            if (isset($data['roleName'])) {
                $permission->setRoleName($data['roleName']);
            }

            $entityManager->persist($permission);
            $entityManager->flush();

            $jsonData = $serializer->serialize($permission, 'json', ['groups' => 'permission:read']);
            return $this->json(json_decode($jsonData, true), 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(CalendarPermission $permission, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityManager->remove($permission);
            $entityManager->flush();

            return $this->json(['message' => 'Permission deleted successfully'], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
