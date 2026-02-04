<?php

namespace App\Repository;

use App\Entity\Incident;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IncidentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Incident::class);
    }

    /**
     * Récupère les incidents visibles par un utilisateur
     */
    public function findVisibleBy(User $user, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.category', 'c')
            ->leftJoin('i.reporter', 'r')
            ->leftJoin('i.assignee', 'a')
            ->addSelect('c', 'r', 'a');

        // L'utilisateur peut voir:
        // - ses propres incidents (reporter)
        // - les incidents qui lui sont assignés
        // - les incidents assignés à son rôle
        $qb->where('i.reporter = :user')
           ->orWhere('i.assignee = :user')
           ->orWhere('i.assigneeRole = :userRole')
           ->setParameter('user', $user)
           ->setParameter('userRole', $user->getRole());

        // Filtres optionnels
        if (!empty($filters['status'])) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $qb->andWhere('i.priority = :priority')
               ->setParameter('priority', $filters['priority']);
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('i.title LIKE :search OR i.description LIKE :search OR i.location LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Tri par date de création décroissante par défaut
        $qb->orderBy('i.createdAt', 'DESC');

        // Pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les incidents visibles par un utilisateur
     */
    public function countVisibleBy(User $user, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->leftJoin('i.category', 'c');

        $qb->where('i.reporter = :user')
           ->orWhere('i.assignee = :user')
           ->orWhere('i.assigneeRole = :userRole')
           ->setParameter('user', $user)
           ->setParameter('userRole', $user->getRole());

        // Filtres optionnels
        if (!empty($filters['status'])) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $qb->andWhere('i.priority = :priority')
               ->setParameter('priority', $filters['priority']);
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('i.title LIKE :search OR i.description LIKE :search OR i.location LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Récupère les incidents créés par un utilisateur
     */
    public function findByReporter(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.category', 'c')
            ->addSelect('c')
            ->where('i.reporter = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les incidents assignés à un utilisateur ou à son rôle
     */
    public function findAssignedTo(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.category', 'c')
            ->leftJoin('i.reporter', 'r')
            ->addSelect('c', 'r')
            ->where('i.assignee = :user')
            ->orWhere('i.assigneeRole = :userRole')
            ->setParameter('user', $user)
            ->setParameter('userRole', $user->getRole())
            ->andWhere('i.status NOT IN (:closedStatuses)')
            ->setParameter('closedStatuses', [Incident::STATUS_CLOSED, Incident::STATUS_RESOLVED])
            ->orderBy('i.priority', 'DESC')
            ->addOrderBy('i.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des incidents
     */
    public function getStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Comptage par statut
        $statusCounts = $conn->fetchAllAssociative('
            SELECT status, COUNT(*) as count 
            FROM incidents 
            GROUP BY status
        ');

        // Comptage par priorité
        $priorityCounts = $conn->fetchAllAssociative('
            SELECT priority, COUNT(*) as count 
            FROM incidents 
            GROUP BY priority
        ');

        // Incidents des 7 derniers jours
        $recentCount = $conn->fetchOne('
            SELECT COUNT(*) 
            FROM incidents 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ');

        return [
            'byStatus' => array_column($statusCounts, 'count', 'status'),
            'byPriority' => array_column($priorityCounts, 'count', 'priority'),
            'last7Days' => (int) $recentCount
        ];
    }

    /**
     * Tous les incidents (pour admins)
     */
    public function findAllPaginated(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.category', 'c')
            ->leftJoin('i.reporter', 'r')
            ->leftJoin('i.assignee', 'a')
            ->addSelect('c', 'r', 'a');

        // Filtres optionnels
        if (!empty($filters['status'])) {
            $qb->where('i.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $qb->andWhere('i.priority = :priority')
               ->setParameter('priority', $filters['priority']);
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('i.title LIKE :search OR i.description LIKE :search OR i.location LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        $qb->orderBy('i.createdAt', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte tous les incidents (pour admins)
     */
    public function countAll(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->leftJoin('i.category', 'c');

        if (!empty($filters['status'])) {
            $qb->where('i.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $qb->andWhere('i.priority = :priority')
               ->setParameter('priority', $filters['priority']);
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('i.title LIKE :search OR i.description LIKE :search OR i.location LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
