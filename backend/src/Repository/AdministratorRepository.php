<?php

namespace App\Repository;

use App\Entity\Administrator;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Administrator>
 */
class AdministratorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Administrator::class);
    }

    /**
     * Find administrator by user
     */
    public function findByUser(User $user): ?Administrator
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * Find administrator by user ID
     */
    public function findByUserId(int $userId): ?Administrator
    {
        return $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if a user is an administrator
     */
    public function isAdmin(User $user): bool
    {
        return $this->findByUser($user) !== null;
    }

    /**
     * Find all administrators with their user data
     */
    public function findAllWithUsers(): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->addSelect('u')
            ->orderBy('a.permissionLevel', 'DESC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find super admins
     */
    public function findSuperAdmins(): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->addSelect('u')
            ->where('a.permissionLevel = :level')
            ->setParameter('level', Administrator::LEVEL_SUPER_ADMIN)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count administrators by permission level
     */
    public function countByPermissionLevel(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.permissionLevel, COUNT(a.id) as count')
            ->groupBy('a.permissionLevel')
            ->getQuery()
            ->getResult();
    }
}
