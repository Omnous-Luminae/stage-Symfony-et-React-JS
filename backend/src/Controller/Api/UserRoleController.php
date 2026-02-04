<?php

namespace App\Controller\Api;

use App\Entity\UserRole;
use App\Repository\UserRoleRepository;
use App\Repository\AdministratorRepository;
use App\Service\SessionUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user-roles', name: 'api_user_roles_')]
class UserRoleController extends AbstractController
{
    public function __construct(
        private UserRoleRepository $roleRepository,
        private AdministratorRepository $adminRepository,
        private SessionUserService $sessionUserService,
        private EntityManagerInterface $em
    ) {}

    /**
     * Vérifie si l'utilisateur courant est admin
     */
    private function requireAdmin(): ?\App\Entity\Administrator
    {
        $user = $this->sessionUserService->getCurrentUser();
        if (!$user) {
            return null;
        }
        return $this->adminRepository->findByUser($user);
    }

    /**
     * Liste tous les rôles actifs (API publique)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $roles = $this->roleRepository->findAllActive();

        $data = array_map(function(UserRole $role) {
            return [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'code' => $role->getCode(),
                'description' => $role->getDescription(),
                'color' => $role->getColor(),
                'icon' => $role->getIcon(),
            ];
        }, $roles);

        return $this->json($data);
    }

    /**
     * Liste tous les rôles avec infos complètes (API admin)
     */
    #[Route('/admin', name: 'admin_list', methods: ['GET'])]
    public function adminList(): JsonResponse
    {
        $admin = $this->requireAdmin();
        if (!$admin) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $rolesWithCount = $this->roleRepository->getUsersCountPerRole();
        $roles = $this->roleRepository->findAllOrdered();

        // Créer un map pour les comptages
        $countMap = [];
        foreach ($rolesWithCount as $rc) {
            $countMap[$rc['id_role']] = (int) $rc['users_count'];
        }

        $data = array_map(function(UserRole $role) use ($countMap) {
            return [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'code' => $role->getCode(),
                'description' => $role->getDescription(),
                'color' => $role->getColor(),
                'icon' => $role->getIcon(),
                'isActive' => $role->isActive(),
                'isSystem' => $role->isSystem(),
                'displayOrder' => $role->getDisplayOrder(),
                'canCreateEvents' => $role->canCreateEvents(),
                'canCreatePublicEvents' => $role->canCreatePublicEvents(),
                'canShareCalendars' => $role->canShareCalendars(),
                'usersCount' => $countMap[$role->getId()] ?? 0,
                'createdAt' => $role->getCreatedAt()?->format('c'),
                'updatedAt' => $role->getUpdatedAt()?->format('c'),
            ];
        }, $roles);

        return $this->json($data);
    }

    /**
     * Créer un nouveau rôle
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        if (!$admin || !$admin->canManageUsers()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom est obligatoire'], 400);
        }

        if (empty($data['code'])) {
            // Générer le code à partir du nom
            $data['code'] = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $data['name']));
        }

        // Vérifier unicité
        if ($this->roleRepository->nameExists($data['name'])) {
            return $this->json(['error' => 'Un rôle avec ce nom existe déjà'], 400);
        }

        if ($this->roleRepository->codeExists($data['code'])) {
            return $this->json(['error' => 'Un rôle avec ce code existe déjà'], 400);
        }

        $role = new UserRole();
        $role->setName($data['name']);
        $role->setCode($data['code']);
        $role->setDescription($data['description'] ?? null);
        $role->setColor($data['color'] ?? '#6366f1');
        $role->setIcon($data['icon'] ?? null);
        $role->setIsActive($data['isActive'] ?? true);
        $role->setDisplayOrder($data['displayOrder'] ?? 0);
        $role->setCanCreateEvents($data['canCreateEvents'] ?? true);
        $role->setCanCreatePublicEvents($data['canCreatePublicEvents'] ?? false);
        $role->setCanShareCalendars($data['canShareCalendars'] ?? true);

        $this->em->persist($role);
        $this->em->flush();

        return $this->json([
            'message' => 'Rôle créé avec succès',
            'role' => [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'code' => $role->getCode(),
                'description' => $role->getDescription(),
                'color' => $role->getColor(),
                'icon' => $role->getIcon(),
                'isActive' => $role->isActive(),
                'isSystem' => $role->isSystem(),
                'displayOrder' => $role->getDisplayOrder(),
                'canCreateEvents' => $role->canCreateEvents(),
                'canCreatePublicEvents' => $role->canCreatePublicEvents(),
                'canShareCalendars' => $role->canShareCalendars(),
            ]
        ], 201);
    }

    /**
     * Mettre à jour un rôle
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        if (!$admin || !$admin->canManageUsers()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $role = $this->roleRepository->find($id);
        if (!$role) {
            return $this->json(['error' => 'Rôle non trouvé'], 404);
        }

        // Ne pas permettre la modification des rôles système
        if ($role->isSystem()) {
            return $this->json(['error' => 'Les rôles système ne peuvent pas être modifiés'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // Validation unicité si nom/code changé
        if (!empty($data['name']) && $data['name'] !== $role->getName()) {
            if ($this->roleRepository->nameExists($data['name'], $id)) {
                return $this->json(['error' => 'Un rôle avec ce nom existe déjà'], 400);
            }
            $role->setName($data['name']);
        }

        if (!empty($data['code']) && $data['code'] !== $role->getCode()) {
            if ($this->roleRepository->codeExists($data['code'], $id)) {
                return $this->json(['error' => 'Un rôle avec ce code existe déjà'], 400);
            }
            $role->setCode($data['code']);
        }

        if (array_key_exists('description', $data)) {
            $role->setDescription($data['description']);
        }
        if (isset($data['color'])) {
            $role->setColor($data['color']);
        }
        if (array_key_exists('icon', $data)) {
            $role->setIcon($data['icon']);
        }
        if (isset($data['isActive'])) {
            $role->setIsActive($data['isActive']);
        }
        if (isset($data['displayOrder'])) {
            $role->setDisplayOrder($data['displayOrder']);
        }
        if (isset($data['canCreateEvents'])) {
            $role->setCanCreateEvents($data['canCreateEvents']);
        }
        if (isset($data['canCreatePublicEvents'])) {
            $role->setCanCreatePublicEvents($data['canCreatePublicEvents']);
        }
        if (isset($data['canShareCalendars'])) {
            $role->setCanShareCalendars($data['canShareCalendars']);
        }

        $role->updateTimestamp();
        $this->em->flush();

        return $this->json([
            'message' => 'Rôle mis à jour avec succès',
            'role' => [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'code' => $role->getCode(),
                'description' => $role->getDescription(),
                'color' => $role->getColor(),
                'icon' => $role->getIcon(),
                'isActive' => $role->isActive(),
                'isSystem' => $role->isSystem(),
                'displayOrder' => $role->getDisplayOrder(),
                'canCreateEvents' => $role->canCreateEvents(),
                'canCreatePublicEvents' => $role->canCreatePublicEvents(),
                'canShareCalendars' => $role->canShareCalendars(),
            ]
        ]);
    }

    /**
     * Supprimer un rôle
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $admin = $this->requireAdmin();
        if (!$admin || !$admin->canManageUsers()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $role = $this->roleRepository->find($id);
        if (!$role) {
            return $this->json(['error' => 'Rôle non trouvé'], 404);
        }

        // Ne pas permettre la suppression des rôles système
        if ($role->isSystem()) {
            return $this->json(['error' => 'Les rôles système ne peuvent pas être supprimés'], 403);
        }

        // Vérifier si des utilisateurs utilisent ce rôle
        $conn = $this->em->getConnection();
        $count = $conn->executeQuery(
            'SELECT COUNT(*) FROM users WHERE role = ?',
            [$role->getName()]
        )->fetchOne();

        if ($count > 0) {
            return $this->json([
                'error' => "Ce rôle est utilisé par {$count} utilisateur(s). Réassignez-les d'abord."
            ], 400);
        }

        $this->em->remove($role);
        $this->em->flush();

        return $this->json(['message' => 'Rôle supprimé avec succès']);
    }

    /**
     * Réordonner les rôles
     */
    #[Route('/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        if (!$admin || !$admin->canManageUsers()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (empty($data['order']) || !is_array($data['order'])) {
            return $this->json(['error' => 'Ordre invalide'], 400);
        }

        foreach ($data['order'] as $index => $roleId) {
            $role = $this->roleRepository->find($roleId);
            if ($role) {
                $role->setDisplayOrder($index);
            }
        }

        $this->em->flush();

        return $this->json(['message' => 'Ordre mis à jour avec succès']);
    }
}
