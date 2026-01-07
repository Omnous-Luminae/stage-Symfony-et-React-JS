<?php

namespace App\Repository;

use App\Entity\Calendar;
use App\Entity\CalendarPermission;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CalendarPermission>
 */
class CalendarPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendarPermission::class);
    }

    /**
     * Find permission for a specific user and calendar
     *
     * @param Calendar $calendar
     * @param User $user
     * @return CalendarPermission|null
     */
    public function findPermission(Calendar $calendar, User $user): ?CalendarPermission
    {
        return $this->findOneBy([
            'calendar' => $calendar,
            'user' => $user
        ]);
    }

    /**
     * Find all permissions for a calendar
     *
     * @param Calendar $calendar
     * @return CalendarPermission[]
     */
    public function findByCalendar(Calendar $calendar): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('p.calendar = :calendar')
            ->setParameter('calendar', $calendar)
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all calendars accessible by a user through permissions
     *
     * @param User $user
     * @return CalendarPermission[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.calendar', 'c')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if user has specific permission level on calendar
     *
     * @param Calendar $calendar
     * @param User $user
     * @param string $permissionLevel
     * @return bool
     */
    public function hasPermission(Calendar $calendar, User $user, string $permissionLevel): bool
    {
        $permission = $this->findPermission($calendar, $user);

        if (!$permission) {
            return false;
        }

        return match($permissionLevel) {
            CalendarPermission::PERMISSION_VIEW => $permission->canView(),
            CalendarPermission::PERMISSION_EDIT => $permission->canEdit(),
            CalendarPermission::PERMISSION_ADMIN => $permission->canAdmin(),
            default => false,
        };
    }

    /**
     * Remove all permissions for a user on a calendar
     *
     * @param Calendar $calendar
     * @param User $user
     * @return void
     */
    public function removePermission(Calendar $calendar, User $user): void
    {
        $permission = $this->findPermission($calendar, $user);

        if ($permission) {
            $em = $this->getEntityManager();
            $em->remove($permission);
            $em->flush();
        }
    }

    /**
     * Count users with access to a calendar
     *
     * @param Calendar $calendar
     * @return int
     */
    public function countByCalendar(Calendar $calendar): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.calendar = :calendar')
            ->setParameter('calendar', $calendar)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
