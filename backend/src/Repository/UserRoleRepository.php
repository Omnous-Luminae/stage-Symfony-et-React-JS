<?php

namespace App\Repository;

use App\Entity\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRole>
 */
class UserRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRole::class);
    }

    /**
     * Find all active roles ordered by display order
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.displayOrder', 'ASC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all roles ordered by display order
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.displayOrder', 'ASC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find role by code
     */
    public function findByCode(string $code): ?UserRole
    {
        return $this->createQueryBuilder('r')
            ->where('r.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find role by name
     */
    public function findByName(string $name): ?UserRole
    {
        return $this->createQueryBuilder('r')
            ->where('r.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if code exists (excluding given id)
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.code = :code')
            ->setParameter('code', $code);

        if ($excludeId !== null) {
            $qb->andWhere('r.id != :id')
               ->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Check if name exists (excluding given id)
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.name = :name')
            ->setParameter('name', $name);

        if ($excludeId !== null) {
            $qb->andWhere('r.id != :id')
               ->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Get count of users per role
     */
    public function getUsersCountPerRole(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT r.id_role, r.name, r.code, COUNT(u.id_user) as users_count
            FROM user_roles r
            LEFT JOIN users u ON u.role = r.name
            GROUP BY r.id_role, r.name, r.code
            ORDER BY r.display_order ASC, r.name ASC
        ';
        
        return $conn->executeQuery($sql)->fetchAllAssociative();
    }
}
