<?php

namespace App\Repository;

use App\Entity\Calendar;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Calendar>
 */
class CalendarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Calendar::class);
    }

    /**
     * Find calendars accessible by a user (owned + shared + public)
     *
     * @param User $user
     * @return Calendar[]
     */
    public function findAccessibleByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.permissions', 'p')
            ->where('c.owner = :user')
            ->orWhere('p.user = :user')
            ->orWhere('c.type = :public')
            ->setParameter('user', $user)
            ->setParameter('public', Calendar::TYPE_PUBLIC)
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Find calendars owned by a user
     *
     * @param User $user
     * @return Calendar[]
     */
    public function findOwnedByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find shared calendars where user has access
     *
     * @param User $user
     * @return Calendar[]
     */
    public function findSharedWithUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.permissions', 'p')
            ->where('p.user = :user')
            ->andWhere('c.owner != :user')
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find public calendars
     *
     * @return Calendar[]
     */
    public function findPublic(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.type = :public')
            ->setParameter('public', Calendar::TYPE_PUBLIC)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search calendars by name
     *
     * @param string $search
     * @param User|null $user Filter by accessible calendars for this user
     * @return Calendar[]
     */
    public function search(string $search, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.name LIKE :search OR c.description LIKE :search')
            ->setParameter('search', '%' . $search . '%');

        if ($user) {
            $qb->leftJoin('c.permissions', 'p')
                ->andWhere('c.owner = :user OR p.user = :user OR c.type = :public')
                ->setParameter('user', $user)
                ->setParameter('public', Calendar::TYPE_PUBLIC)
                ->distinct();
        }

        return $qb->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
