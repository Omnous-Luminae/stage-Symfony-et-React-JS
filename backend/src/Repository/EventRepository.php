<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Calendar;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    private const USER_ACCESS_CONDITION = 'c.owner = :user OR p.user = :user OR c.type = :public';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Find events by date range
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param User|null $user Filter by accessible calendars for this user
     * @return Event[]
     */
    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.calendar', 'c')
            ->where('e.startDate <= :end')
            ->andWhere('e.endDate >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($user) {
            $qb->leftJoin('c.permissions', 'p')
                ->andWhere(self::USER_ACCESS_CONDITION)
                ->setParameter('user', $user)
                ->setParameter('public', Calendar::TYPE_PUBLIC);
        }

        return $qb->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by calendar
     *
     * @param Calendar $calendar
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @return Event[]
     */
    public function findByCalendar(
        Calendar $calendar,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->where('e.calendar = :calendar')
            ->setParameter('calendar', $calendar);

        if ($startDate) {
            $qb->andWhere('e.endDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('e.startDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming events for a user
     *
     * @param User $user
     * @param int $limit
     * @return Event[]
     */
    public function findUpcomingForUser(User $user, int $limit = 10): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('e')
            ->join('e.calendar', 'c')
            ->leftJoin('c.permissions', 'p')
            ->where('e.startDate >= :now')
            ->andWhere(self::USER_ACCESS_CONDITION)
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->setParameter('public', Calendar::TYPE_PUBLIC)
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by type
     *
     * @param string $type
     * @param User|null $user
     * @return Event[]
     */
    public function findByType(string $type, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.type = :type')
            ->setParameter('type', $type);

        if ($user) {
            $qb->join('e.calendar', 'c')
                ->leftJoin('c.permissions', 'p')
                ->andWhere(self::USER_ACCESS_CONDITION)
                ->setParameter('user', $user)
                ->setParameter('public', Calendar::TYPE_PUBLIC);
        }

        return $qb->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search events by title or description
     *
     * @param string $search
     * @param User|null $user
     * @return Event[]
     */
    public function search(string $search, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search')
            ->setParameter('search', '%' . $search . '%');

        if ($user) {
            $qb->join('e.calendar', 'c')
                ->leftJoin('c.permissions', 'p')
                ->andWhere(self::USER_ACCESS_CONDITION)
                ->setParameter('user', $user)
                ->setParameter('public', Calendar::TYPE_PUBLIC);
        }

        return $qb->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find accessible events with optional calendar and date filters
     *
     * @param Calendar|null $calendar
     * @param \DateTimeInterface|null $start
     * @param \DateTimeInterface|null $end
     * @param User|null $user
     * @param int $limit
     * @param int $offset
     * @return Event[]
     */
    public function findAccessible(
        ?Calendar $calendar,
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end,
        ?User $user,
        int $limit = 100,
        int $offset = 0
    ): array {
        error_log('🔍 findAccessible called - User: ' . ($user ? $user->getEmail() : 'NULL'));
        error_log('📆 Date range: ' . ($start ? $start->format('Y-m-d H:i:s') : 'NULL') . ' to ' . ($end ? $end->format('Y-m-d H:i:s') : 'NULL'));
        
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.calendar', 'c');

        if ($calendar) {
            error_log('📅 Filter by calendar: ' . $calendar->getName());
            $qb->andWhere('e.calendar = :calendar')
                ->setParameter('calendar', $calendar);
        }

        if ($start) {
            error_log('📆 Filter start: ' . $start->format('Y-m-d H:i:s'));
            $qb->andWhere('e.endDate >= :start')
                ->setParameter('start', $start);
        }

        if ($end) {
            error_log('📆 Filter end: ' . $end->format('Y-m-d H:i:s'));
            $qb->andWhere('e.startDate <= :end')
                ->setParameter('end', $end);
        }

        if ($user) {
            error_log('🔐 User connected - applying access control');
            error_log('🔐 User ID: ' . $user->getId());
            $qb->leftJoin('c.permissions', 'p')
                ->andWhere('(c.owner = :user OR p.user = :user OR c.type = :public)')
                ->setParameter('user', $user)
                ->setParameter('public', Calendar::TYPE_PUBLIC);
            
            // Debug: show the DQL
            error_log('📝 DQL: ' . $qb->getDQL());
        } else {
            error_log('❌ No user - only public calendars');
            // Utilisateur non connecté : on ne retourne que les agendas publics
            $qb->andWhere('c.type = :public')
                ->setParameter('public', Calendar::TYPE_PUBLIC);
        }

        $result = $qb
            ->orderBy('e.startDate', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        error_log('📊 findAccessible returned ' . count($result) . ' events');
        foreach ($result as $event) {
            error_log('  - Event: ' . $event->getTitle() . ' (Calendar: ' . ($event->getCalendar() ? $event->getCalendar()->getName() : 'NULL') . ')');
        }
        
        return $result;
    }

    /**
     * Count events for a calendar
     *
     * @param Calendar $calendar
     * @return int
     */
    public function countByCalendar(Calendar $calendar): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.calendar = :calendar')
            ->setParameter('calendar', $calendar)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
