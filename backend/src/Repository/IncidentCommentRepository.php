<?php

namespace App\Repository;

use App\Entity\Incident;
use App\Entity\IncidentComment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IncidentCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncidentComment::class);
    }

    /**
     * Récupère les commentaires d'un incident
     * Les commentaires internes sont masqués pour le reporter (sauf s'il est assigné)
     */
    public function findByIncident(Incident $incident, ?User $currentUser = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.author', 'a')
            ->addSelect('a')
            ->where('c.incident = :incident')
            ->setParameter('incident', $incident)
            ->orderBy('c.createdAt', 'ASC');

        // Si l'utilisateur actuel est le reporter mais pas assigné, on masque les commentaires internes
        if ($currentUser && $incident->getReporter()->getId() === $currentUser->getId()) {
            $isAssigned = $incident->getAssignee() && $incident->getAssignee()->getId() === $currentUser->getId();
            $isRoleAssigned = $incident->getAssigneeRole() && $currentUser->getRole() === $incident->getAssigneeRole();
            
            if (!$isAssigned && !$isRoleAssigned) {
                $qb->andWhere('c.isInternal = :internal')
                   ->setParameter('internal', false);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les commentaires d'un incident
     */
    public function countByIncident(Incident $incident): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.incident = :incident')
            ->setParameter('incident', $incident)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
