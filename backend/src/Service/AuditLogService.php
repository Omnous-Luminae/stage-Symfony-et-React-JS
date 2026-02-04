<?php

namespace App\Service;

use App\Entity\Administrator;
use App\Entity\AuditLog;
use App\Entity\User;
use App\Repository\AdministratorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdministratorRepository $adminRepository,
        private RequestStack $requestStack,
        private SessionUserService $sessionUserService
    ) {}

    /**
     * Log an action - now logs ALL users, not just admins
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?Administrator $admin = null,
        ?User $user = null
    ): AuditLog {
        // Get current user from session if not provided
        if (!$user) {
            $user = $this->sessionUserService->getCurrentUser();
        }

        // Get admin from session if not provided (optional now)
        if (!$admin && $user) {
            $admin = $this->adminRepository->findByUser($user);
        }

        // On peut logger même sans admin maintenant, tant qu'on a un user
        $log = new AuditLog();
        $log->setAdmin($admin); // Peut être null
        $log->setUser($user);   // L'utilisateur qui fait l'action
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);

        if ($oldValue !== null) {
            $log->setOldValue(json_encode($oldValue, JSON_UNESCAPED_UNICODE));
        }

        if ($newValue !== null) {
            $log->setNewValue(json_encode($newValue, JSON_UNESCAPED_UNICODE));
        }

        // Get request info
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->em->persist($log);
        $this->em->flush();

        return $log;
    }

    /**
     * Log user creation
     */
    public function logUserCreated(int $userId, array $userData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATE,
            AuditLog::ENTITY_USER,
            $userId,
            null,
            $userData
        );
    }

    /**
     * Log user update
     */
    public function logUserUpdated(int $userId, array $oldData, array $newData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::ENTITY_USER,
            $userId,
            $oldData,
            $newData
        );
    }

    /**
     * Log user deletion
     */
    public function logUserDeleted(int $userId, array $userData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DELETE,
            AuditLog::ENTITY_USER,
            $userId,
            $userData,
            null
        );
    }

    /**
     * Log admin promotion
     */
    public function logAdminPromotion(int $userId, array $adminData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_PROMOTE,
            AuditLog::ENTITY_ADMIN,
            $userId,
            null,
            $adminData
        );
    }

    /**
     * Log admin demotion
     */
    public function logAdminDemotion(int $userId, array $adminData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DEMOTE,
            AuditLog::ENTITY_ADMIN,
            $userId,
            $adminData,
            null
        );
    }

    /**
     * Log permission change
     */
    public function logPermissionChange(int $adminId, array $oldPermissions, array $newPermissions): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_PERMISSION_CHANGE,
            AuditLog::ENTITY_ADMIN,
            $adminId,
            $oldPermissions,
            $newPermissions
        );
    }

    /**
     * Log calendar creation
     */
    public function logCalendarCreated(int $calendarId, array $calendarData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATE,
            AuditLog::ENTITY_CALENDAR,
            $calendarId,
            null,
            $calendarData,
            null,
            $user
        );
    }

    /**
     * Log calendar update
     */
    public function logCalendarUpdated(int $calendarId, array $oldData, array $newData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::ENTITY_CALENDAR,
            $calendarId,
            $oldData,
            $newData,
            null,
            $user
        );
    }

    /**
     * Log calendar deletion
     */
    public function logCalendarDeleted(int $calendarId, array $calendarData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DELETE,
            AuditLog::ENTITY_CALENDAR,
            $calendarId,
            $calendarData,
            null,
            null,
            $user
        );
    }

    /**
     * Log event creation
     */
    public function logEventCreated(int $eventId, array $eventData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATE,
            AuditLog::ENTITY_EVENT,
            $eventId,
            null,
            $eventData,
            null,
            $user
        );
    }

    /**
     * Log event update
     */
    public function logEventUpdated(int $eventId, array $oldData, array $newData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::ENTITY_EVENT,
            $eventId,
            $oldData,
            $newData,
            null,
            $user
        );
    }

    /**
     * Log event deletion
     */
    public function logEventDeleted(int $eventId, array $eventData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DELETE,
            AuditLog::ENTITY_EVENT,
            $eventId,
            $eventData,
            null,
            null,
            $user
        );
    }

    // ========================================
    // INCIDENT LOGGING METHODS
    // ========================================

    /**
     * Log incident creation
     */
    public function logIncidentCreated(int $incidentId, array $incidentData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATE,
            AuditLog::ENTITY_INCIDENT,
            $incidentId,
            null,
            $incidentData,
            null,
            $user
        );
    }

    /**
     * Log incident update
     */
    public function logIncidentUpdated(int $incidentId, array $oldData, array $newData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::ENTITY_INCIDENT,
            $incidentId,
            $oldData,
            $newData,
            null,
            $user
        );
    }

    /**
     * Log incident status change
     */
    public function logIncidentStatusChanged(int $incidentId, string $oldStatus, string $newStatus, ?User $user = null): AuditLog
    {
        return $this->log(
            'status_change',
            AuditLog::ENTITY_INCIDENT,
            $incidentId,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            null,
            $user
        );
    }

    /**
     * Log incident deletion
     */
    public function logIncidentDeleted(int $incidentId, array $incidentData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DELETE,
            AuditLog::ENTITY_INCIDENT,
            $incidentId,
            $incidentData,
            null,
            null,
            $user
        );
    }

    /**
     * Log incident comment added
     */
    public function logIncidentCommentAdded(int $incidentId, int $commentId, array $commentData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATE,
            AuditLog::ENTITY_INCIDENT_COMMENT,
            $commentId,
            null,
            array_merge(['incident_id' => $incidentId], $commentData),
            null,
            $user
        );
    }

    // ========================================
    // INCIDENT CATEGORY LOGGING METHODS
    // ========================================

    /**
     * Log incident category creation
     */
    public function logIncidentCategoryCreated(int $categoryId, array $categoryData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATE,
            AuditLog::ENTITY_INCIDENT_CATEGORY,
            $categoryId,
            null,
            $categoryData,
            null,
            $user
        );
    }

    /**
     * Log incident category update
     */
    public function logIncidentCategoryUpdated(int $categoryId, array $oldData, array $newData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::ENTITY_INCIDENT_CATEGORY,
            $categoryId,
            $oldData,
            $newData,
            null,
            $user
        );
    }

    /**
     * Log incident category deletion
     */
    public function logIncidentCategoryDeleted(int $categoryId, array $categoryData, ?User $user = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DELETE,
            AuditLog::ENTITY_INCIDENT_CATEGORY,
            $categoryId,
            $categoryData,
            null,
            null,
            $user
        );
    }
}
