<?php

namespace App\Service;

use App\Entity\Administrator;
use App\Entity\AuditLog;
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
     * Log an action
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?Administrator $admin = null
    ): AuditLog {
        // Get admin from session if not provided
        if (!$admin) {
            $user = $this->sessionUserService->getCurrentUser();
            if ($user) {
                $admin = $this->adminRepository->findByUser($user);
            }
        }

        // If still no admin, we can't log (only admins can create logs)
        if (!$admin) {
            throw new \RuntimeException('Cannot log action: no admin context');
        }

        $log = new AuditLog();
        $log->setAdmin($admin);
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
    public function logCalendarCreated(int $calendarId, array $calendarData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATE,
            AuditLog::ENTITY_CALENDAR,
            $calendarId,
            null,
            $calendarData
        );
    }

    /**
     * Log calendar update
     */
    public function logCalendarUpdated(int $calendarId, array $oldData, array $newData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::ENTITY_CALENDAR,
            $calendarId,
            $oldData,
            $newData
        );
    }

    /**
     * Log calendar deletion
     */
    public function logCalendarDeleted(int $calendarId, array $calendarData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DELETE,
            AuditLog::ENTITY_CALENDAR,
            $calendarId,
            $calendarData,
            null
        );
    }

    /**
     * Log event creation
     */
    public function logEventCreated(int $eventId, array $eventData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATE,
            AuditLog::ENTITY_EVENT,
            $eventId,
            null,
            $eventData
        );
    }

    /**
     * Log event update
     */
    public function logEventUpdated(int $eventId, array $oldData, array $newData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATE,
            AuditLog::ENTITY_EVENT,
            $eventId,
            $oldData,
            $newData
        );
    }

    /**
     * Log event deletion
     */
    public function logEventDeleted(int $eventId, array $eventData): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DELETE,
            AuditLog::ENTITY_EVENT,
            $eventId,
            $eventData,
            null
        );
    }
}
