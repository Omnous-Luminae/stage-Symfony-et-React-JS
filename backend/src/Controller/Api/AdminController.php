<?php

namespace App\Controller\Api;

use App\Entity\Administrator;
use App\Entity\AuditLog;
use App\Entity\User;
use App\Repository\AdministratorRepository;
use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use App\Repository\CalendarRepository;
use App\Repository\EventRepository;
use App\Service\AuditLogService;
use App\Service\SessionUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin', name: 'api_admin_')]
class AdminController extends AbstractController
{
    public function __construct(
        private SessionUserService $sessionUserService,
        private AdministratorRepository $adminRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private AuditLogService $auditLogService,
        private AuditLogRepository $auditLogRepository
    ) {}

    /**
     * Check if current user is admin
     */
    #[Route('/check', name: 'check', methods: ['GET'])]
    public function checkAdmin(): JsonResponse
    {
        $user = $this->sessionUserService->getCurrentUser();
        if (!$user) {
            return $this->json(['isAdmin' => false, 'message' => 'Non connecté'], 401);
        }

        $admin = $this->adminRepository->findByUser($user);
        
        if (!$admin) {
            return $this->json(['isAdmin' => false]);
        }

        return $this->json([
            'isAdmin' => true,
            'permissionLevel' => $admin->getPermissionLevel(),
            'permissions' => [
                'canManageUsers' => $admin->canManageUsers(),
                'canManageCalendars' => $admin->canManageCalendars(),
                'canManagePermissions' => $admin->canManagePermissions(),
                'canViewAuditLogs' => $admin->canViewAuditLogs(),
            ]
        ]);
    }

    /**
     * Get dashboard statistics
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function getStats(
        CalendarRepository $calendarRepository,
        EventRepository $eventRepository
    ): JsonResponse {
        $this->requireAdmin();

        $totalUsers = count($this->userRepository->findAll());
        $totalCalendars = count($calendarRepository->findAll());
        $totalEvents = count($eventRepository->findAll());
        $totalAdmins = count($this->adminRepository->findAll());

        // Users by status
        $activeUsers = count($this->userRepository->findBy(['status' => 'Actif']));
        $inactiveUsers = $totalUsers - $activeUsers;

        // Users by role
        $usersByRole = $this->em->createQuery(
            'SELECT u.role, COUNT(u.id) as count FROM App\Entity\User u GROUP BY u.role'
        )->getResult();

        return $this->json([
            'totalUsers' => $totalUsers,
            'totalCalendars' => $totalCalendars,
            'totalEvents' => $totalEvents,
            'totalAdmins' => $totalAdmins,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'usersByRole' => $usersByRole
        ]);
    }

    /**
     * List all users
     */
    #[Route('/users', name: 'users_list', methods: ['GET'])]
    public function listUsers(): JsonResponse
    {
        $admin = $this->requireAdmin();
        
        if (!$admin->canManageUsers()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $users = $this->userRepository->findBy([], ['lastName' => 'ASC']);
        
        $data = array_map(function(User $user) {
            $isAdmin = $this->adminRepository->findByUser($user);
            return [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'status' => $user->getStatus(),
                'isAdmin' => $isAdmin !== null,
                'adminLevel' => $isAdmin?->getPermissionLevel(),
                'createdAt' => $user->getCreatedAt()?->format('c'),
                'updatedAt' => $user->getUpdatedAt()?->format('c'),
            ];
        }, $users);

        return $this->json($data);
    }

    /**
     * Create a new user
     */
    #[Route('/users', name: 'users_create', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        
        if (!$admin->canManageUsers()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['email']) || empty($data['firstName']) || empty($data['lastName'])) {
            return $this->json(['error' => 'Email, prénom et nom sont requis'], 400);
        }

        // Check if email already exists
        $existing = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existing) {
            return $this->json(['error' => 'Cet email existe déjà'], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setRole($data['role'] ?? 'Intervenant');
        $user->setStatus($data['status'] ?? 'Actif');

        // Set password (default or provided)
        $password = $data['password'] ?? 'ChangeMe123!';
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        // Log the action
        try {
            $this->auditLogService->logUserCreated($user->getId(), [
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'role' => $user->getRole(),
            ]);
        } catch (\Exception $e) {
            // Log silently fails, don't block the action
        }

        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'role' => $user->getRole(),
                'status' => $user->getStatus(),
            ]
        ], 201);
    }

    /**
     * Update a user
     */
    #[Route('/users/{id}', name: 'users_update', methods: ['PUT', 'PATCH'])]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        
        if (!$admin->canManageUsers()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Save old values for logging
        $oldData = [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'status' => $user->getStatus(),
        ];

        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['email'])) {
            // Check if email is not taken by another user
            $existing = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json(['error' => 'Cet email est déjà utilisé'], 400);
            }
            $user->setEmail($data['email']);
        }
        if (isset($data['role'])) {
            $user->setRole($data['role']);
        }
        if (isset($data['status'])) {
            $user->setStatus($data['status']);
        }
        if (!empty($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $this->em->flush();

        // Log the action
        try {
            $newData = [
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'status' => $user->getStatus(),
            ];
            $this->auditLogService->logUserUpdated($user->getId(), $oldData, $newData);
        } catch (\Exception $e) {
            // Log silently fails
        }

        return $this->json([
            'message' => 'Utilisateur mis à jour',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'role' => $user->getRole(),
                'status' => $user->getStatus(),
            ]
        ]);
    }

    /**
     * Delete a user
     */
    #[Route('/users/{id}', name: 'users_delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $admin = $this->requireAdmin();
        
        if (!$admin->canManageUsers()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Cannot delete yourself
        $currentUser = $this->sessionUserService->getCurrentUser();
        if ($user->getId() === $currentUser->getId()) {
            return $this->json(['error' => 'Vous ne pouvez pas supprimer votre propre compte'], 400);
        }

        // Check if user is a super admin (protection)
        $targetAdmin = $this->adminRepository->findByUser($user);
        if ($targetAdmin && $targetAdmin->isSuperAdmin() && !$admin->isSuperAdmin()) {
            return $this->json(['error' => 'Seul un Super Admin peut supprimer un autre Super Admin'], 403);
        }

        // Save data for logging before deletion
        $userData = [
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'role' => $user->getRole(),
        ];
        $userId = $user->getId();

        $this->em->remove($user);
        $this->em->flush();

        // Log the action
        try {
            $this->auditLogService->logUserDeleted($userId, $userData);
        } catch (\Exception $e) {
            // Log silently fails
        }
        return $this->json(['message' => 'Utilisateur supprimé']);
    }

    /**
     * Promote user to admin
     */
    #[Route('/users/{id}/promote', name: 'users_promote', methods: ['POST'])]
    public function promoteToAdmin(int $id, Request $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        
        if (!$admin->canManagePermissions()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Check if already admin
        $existingAdmin = $this->adminRepository->findByUser($user);
        if ($existingAdmin) {
            return $this->json(['error' => 'Cet utilisateur est déjà administrateur'], 400);
        }

        $data = json_decode($request->getContent(), true);

        $newAdmin = new Administrator();
        $newAdmin->setUser($user);
        $newAdmin->setPermissionLevel($data['permissionLevel'] ?? Administrator::LEVEL_ADMIN);
        $newAdmin->setCanManageUsers($data['canManageUsers'] ?? true);
        $newAdmin->setCanManageCalendars($data['canManageCalendars'] ?? true);
        $newAdmin->setCanManagePermissions($data['canManagePermissions'] ?? false);
        $newAdmin->setCanViewAuditLogs($data['canViewAuditLogs'] ?? true);

        // Only super admin can create another super admin
        if (($data['permissionLevel'] ?? '') === Administrator::LEVEL_SUPER_ADMIN && !$admin->isSuperAdmin()) {
            return $this->json(['error' => 'Seul un Super Admin peut créer un autre Super Admin'], 403);
        }

        $this->em->persist($newAdmin);
        $this->em->flush();

        // Log the action
        try {
            $this->auditLogService->logAdminPromotion($user->getId(), [
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'permissionLevel' => $newAdmin->getPermissionLevel(),
                'canManageUsers' => $newAdmin->canManageUsers(),
                'canManageCalendars' => $newAdmin->canManageCalendars(),
                'canManagePermissions' => $newAdmin->canManagePermissions(),
                'canViewAuditLogs' => $newAdmin->canViewAuditLogs(),
            ]);
        } catch (\Exception $e) {
            // Log silently fails
        }

        return $this->json([
            'message' => 'Utilisateur promu administrateur',
            'admin' => [
                'id' => $newAdmin->getId(),
                'userId' => $user->getId(),
                'permissionLevel' => $newAdmin->getPermissionLevel(),
            ]
        ]);
    }

    /**
     * Demote admin to regular user
     */
    #[Route('/users/{id}/demote', name: 'users_demote', methods: ['POST'])]
    public function demoteAdmin(int $id): JsonResponse
    {
        $admin = $this->requireAdmin();
        
        if (!$admin->canManagePermissions()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $targetAdmin = $this->adminRepository->findByUser($user);
        if (!$targetAdmin) {
            return $this->json(['error' => "Cet utilisateur n'est pas administrateur"], 400);
        }

        // Cannot demote yourself
        $currentUser = $this->sessionUserService->getCurrentUser();
        if ($user->getId() === $currentUser->getId()) {
            return $this->json(['error' => 'Vous ne pouvez pas rétrograder votre propre compte'], 400);
        }

        // Only super admin can demote another super admin
        if ($targetAdmin->isSuperAdmin() && !$admin->isSuperAdmin()) {
            return $this->json(['error' => 'Seul un Super Admin peut rétrograder un autre Super Admin'], 403);
        }

        // Save data for logging before deletion
        $adminData = [
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'permissionLevel' => $targetAdmin->getPermissionLevel(),
        ];
        $userId = $user->getId();

        $this->em->remove($targetAdmin);
        $this->em->flush();

        // Log the action
        try {
            $this->auditLogService->logAdminDemotion($userId, $adminData);
        } catch (\Exception $e) {
            // Log silently fails
        }

        return $this->json(['message' => 'Administrateur rétrogradé']);
    }

    /**
     * List all admins
     */
    #[Route('/administrators', name: 'admins_list', methods: ['GET'])]
    public function listAdmins(): JsonResponse
    {
        $this->requireAdmin();

        $admins = $this->adminRepository->findAllWithUsers();
        
        $data = array_map(function(Administrator $admin) {
            return [
                'id' => $admin->getId(),
                'user' => [
                    'id' => $admin->getUser()->getId(),
                    'firstName' => $admin->getUser()->getFirstName(),
                    'lastName' => $admin->getUser()->getLastName(),
                    'email' => $admin->getUser()->getEmail(),
                ],
                'permissionLevel' => $admin->getPermissionLevel(),
                'canManageUsers' => $admin->canManageUsers(),
                'canManageCalendars' => $admin->canManageCalendars(),
                'canManagePermissions' => $admin->canManagePermissions(),
                'canViewAuditLogs' => $admin->canViewAuditLogs(),
                'lastLogin' => $admin->getLastLogin()?->format('c'),
                'createdAt' => $admin->getCreatedAt()?->format('c'),
            ];
        }, $admins);

        return $this->json($data);
    }

    /**
     * Update admin permissions
     */
    #[Route('/administrators/{id}', name: 'admins_update', methods: ['PUT', 'PATCH'])]
    public function updateAdmin(int $id, Request $request): JsonResponse
    {
        $currentAdmin = $this->requireAdmin();
        
        if (!$currentAdmin->canManagePermissions()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $admin = $this->adminRepository->find($id);
        if (!$admin) {
            return $this->json(['error' => 'Administrateur non trouvé'], 404);
        }

        // Cannot modify your own permissions (except super admin)
        $currentUser = $this->sessionUserService->getCurrentUser();
        if ($admin->getUser()->getId() === $currentUser->getId() && !$currentAdmin->isSuperAdmin()) {
            return $this->json(['error' => 'Vous ne pouvez pas modifier vos propres permissions'], 400);
        }

        // Only super admin can modify another super admin
        if ($admin->isSuperAdmin() && !$currentAdmin->isSuperAdmin()) {
            return $this->json(['error' => 'Seul un Super Admin peut modifier un autre Super Admin'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // Save old permissions for logging
        $oldPermissions = [
            'permissionLevel' => $admin->getPermissionLevel(),
            'canManageUsers' => $admin->canManageUsers(),
            'canManageCalendars' => $admin->canManageCalendars(),
            'canManagePermissions' => $admin->canManagePermissions(),
            'canViewAuditLogs' => $admin->canViewAuditLogs(),
        ];

        if (isset($data['permissionLevel'])) {
            // Only super admin can change permission level to/from Super_Admin
            if ($data['permissionLevel'] === Administrator::LEVEL_SUPER_ADMIN && !$currentAdmin->isSuperAdmin()) {
                return $this->json(['error' => 'Seul un Super Admin peut définir ce niveau'], 403);
            }
            $admin->setPermissionLevel($data['permissionLevel']);
        }
        if (isset($data['canManageUsers'])) {
            $admin->setCanManageUsers($data['canManageUsers']);
        }
        if (isset($data['canManageCalendars'])) {
            $admin->setCanManageCalendars($data['canManageCalendars']);
        }
        if (isset($data['canManagePermissions'])) {
            $admin->setCanManagePermissions($data['canManagePermissions']);
        }
        if (isset($data['canViewAuditLogs'])) {
            $admin->setCanViewAuditLogs($data['canViewAuditLogs']);
        }

        $this->em->flush();

        // Log the permission change
        try {
            $this->auditLogService->logPermissionChange($admin->getId(), $oldPermissions, [
                'permissionLevel' => $admin->getPermissionLevel(),
                'canManageUsers' => $admin->canManageUsers(),
                'canManageCalendars' => $admin->canManageCalendars(),
                'canManagePermissions' => $admin->canManagePermissions(),
                'canViewAuditLogs' => $admin->canViewAuditLogs(),
            ]);
        } catch (\Exception $e) {
            // Log silently fails
        }

        return $this->json(['message' => 'Permissions mises à jour']);
    }

    // ==================== AUDIT LOGS ====================

    /**
     * List audit logs
     */
    #[Route('/logs', name: 'logs_list', methods: ['GET'])]
    public function listLogs(Request $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        
        if (!$admin->canViewAuditLogs()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(10, (int) $request->query->get('limit', 50)));
        $action = $request->query->get('action');
        $entityType = $request->query->get('entityType');
        $adminId = $request->query->get('adminId') ? (int) $request->query->get('adminId') : null;
        $dateFrom = $request->query->get('dateFrom') ? new \DateTime($request->query->get('dateFrom')) : null;
        $dateTo = $request->query->get('dateTo') ? new \DateTime($request->query->get('dateTo') . ' 23:59:59') : null;

        $logs = $this->auditLogRepository->findWithFilters(
            $action,
            $entityType,
            $adminId,
            $dateFrom,
            $dateTo,
            $page,
            $limit
        );

        $total = $this->auditLogRepository->countWithFilters(
            $action,
            $entityType,
            $adminId,
            $dateFrom,
            $dateTo
        );

        $data = array_map(function(AuditLog $log) {
            return [
                'id' => $log->getId(),
                'admin' => $log->getAdmin() ? [
                    'id' => $log->getAdmin()->getId(),
                    'user' => [
                        'id' => $log->getAdmin()->getUser()?->getId(),
                        'firstName' => $log->getAdmin()->getUser()?->getFirstName(),
                        'lastName' => $log->getAdmin()->getUser()?->getLastName(),
                        'email' => $log->getAdmin()->getUser()?->getEmail(),
                    ]
                ] : null,
                'action' => $log->getAction(),
                'actionLabel' => $log->getActionLabel(),
                'entityType' => $log->getEntityType(),
                'entityTypeLabel' => $log->getEntityTypeLabel(),
                'entityId' => $log->getEntityId(),
                'oldValue' => $log->getOldValueDecoded(),
                'newValue' => $log->getNewValueDecoded(),
                'ipAddress' => $log->getIpAddress(),
                'createdAt' => $log->getCreatedAt()?->format('c'),
            ];
        }, $logs);

        return $this->json([
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => ceil($total / $limit),
            ]
        ]);
    }

    /**
     * Get log statistics
     */
    #[Route('/logs/stats', name: 'logs_stats', methods: ['GET'])]
    public function getLogStats(): JsonResponse
    {
        $admin = $this->requireAdmin();
        
        if (!$admin->canViewAuditLogs()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $stats = $this->auditLogRepository->getStatistics();

        return $this->json($stats);
    }

    /**
     * Get recent logs
     */
    #[Route('/logs/recent', name: 'logs_recent', methods: ['GET'])]
    public function getRecentLogs(Request $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        
        if (!$admin->canViewAuditLogs()) {
            return $this->json(['error' => 'Permission refusée'], 403);
        }

        $limit = min(50, max(5, (int) $request->query->get('limit', 20)));
        $logs = $this->auditLogRepository->findRecent($limit);

        $data = array_map(function(AuditLog $log) {
            return [
                'id' => $log->getId(),
                'admin' => $log->getAdmin() ? [
                    'id' => $log->getAdmin()->getId(),
                    'user' => [
                        'firstName' => $log->getAdmin()->getUser()?->getFirstName(),
                        'lastName' => $log->getAdmin()->getUser()?->getLastName(),
                    ]
                ] : null,
                'action' => $log->getAction(),
                'actionLabel' => $log->getActionLabel(),
                'entityType' => $log->getEntityType(),
                'entityTypeLabel' => $log->getEntityTypeLabel(),
                'entityId' => $log->getEntityId(),
                'createdAt' => $log->getCreatedAt()?->format('c'),
            ];
        }, $logs);

        return $this->json($data);
    }

    /**
     * Helper: Require current user to be admin
     */
    private function requireAdmin(): Administrator
    {
        $user = $this->sessionUserService->getCurrentUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Non connecté');
        }

        $admin = $this->adminRepository->findByUser($user);
        if (!$admin) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs');
        }

        return $admin;
    }
}
