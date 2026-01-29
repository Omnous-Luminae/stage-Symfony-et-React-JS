<?php

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * Find logs with pagination and filters
     */
    public function findWithFilters(
        ?string $action = null,
        ?string $entityType = null,
        ?int $adminId = null,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
        int $page = 1,
        int $limit = 50
    ): array {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.admin', 'a')
            ->leftJoin('a.user', 'u')
            ->orderBy('l.createdAt', 'DESC');

        if ($action) {
            $qb->andWhere('l.action = :action')
               ->setParameter('action', $action);
        }

        if ($entityType) {
            $qb->andWhere('l.entityType = :entityType')
               ->setParameter('entityType', $entityType);
        }

        if ($adminId) {
            $qb->andWhere('a.id = :adminId')
               ->setParameter('adminId', $adminId);
        }

        if ($dateFrom) {
            $qb->andWhere('l.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('l.createdAt <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        $offset = ($page - 1) * $limit;

        return $qb->setFirstResult($offset)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Count logs with filters
     */
    public function countWithFilters(
        ?string $action = null,
        ?string $entityType = null,
        ?int $adminId = null,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null
    ): int {
        $qb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->leftJoin('l.admin', 'a');

        if ($action) {
            $qb->andWhere('l.action = :action')
               ->setParameter('action', $action);
        }

        if ($entityType) {
            $qb->andWhere('l.entityType = :entityType')
               ->setParameter('entityType', $entityType);
        }

        if ($adminId) {
            $qb->andWhere('a.id = :adminId')
               ->setParameter('adminId', $adminId);
        }

        if ($dateFrom) {
            $qb->andWhere('l.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('l.createdAt <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find recent logs
     */
    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.admin', 'a')
            ->leftJoin('a.user', 'u')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs by entity
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.entityType = :entityType')
            ->andWhere('l.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get log statistics
     */
    public function getStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Actions count
        $actionsResult = $conn->executeQuery(
            'SELECT action, COUNT(*) as count FROM audit_logs GROUP BY action ORDER BY count DESC'
        )->fetchAllAssociative();

        // Entity types count
        $entitiesResult = $conn->executeQuery(
            'SELECT entity_type, COUNT(*) as count FROM audit_logs GROUP BY entity_type ORDER BY count DESC'
        )->fetchAllAssociative();

        // Logs per day (last 30 days)
        $dailyResult = $conn->executeQuery(
            'SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM audit_logs 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC'
        )->fetchAllAssociative();

        // Total count
        $total = $this->count([]);

        return [
            'total' => $total,
            'byAction' => $actionsResult,
            'byEntityType' => $entitiesResult,
            'daily' => $dailyResult
        ];
    }

    /**
     * Delete old logs (retention policy)
     */
    public function deleteOlderThan(\DateTimeInterface $date): int
    {
        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
