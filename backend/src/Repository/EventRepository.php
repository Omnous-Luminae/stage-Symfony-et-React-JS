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
                ->setParameter('public', 'public');
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
            ->setParameter('public', 'public')
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
                ->setParameter('public', 'public');
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
                ->setParameter('public', 'public');
        }

        return $qb->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
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
