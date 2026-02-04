<?php

namespace App\Repository;

use App\Entity\EventType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventType>
 *
 * @method EventType|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventType|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventType[]    findAll()
 * @method EventType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventType::class);
    }

    /**
     * Trouve tous les types actifs triés par ordre d'affichage
     *
     * @return EventType[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('et')
            ->where('et.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('et.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un type par son code
     */
    public function findByCode(string $code): ?EventType
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Trouve un type par son nom (pour compatibilité avec les anciennes données)
     */
    public function findByName(string $name): ?EventType
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Vérifie si un code existe déjà
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('et')
            ->select('COUNT(et.id)')
            ->where('et.code = :code')
            ->setParameter('code', $code);

        if ($excludeId !== null) {
            $qb->andWhere('et.id != :id')
               ->setParameter('id', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Compte le nombre d'événements utilisant ce type
     */
    public function countEventsForType(EventType $eventType): int
    {
        return $this->createQueryBuilder('et')
            ->select('COUNT(e.id)')
            ->leftJoin('et.events', 'e')
            ->where('et.id = :id')
            ->setParameter('id', $eventType->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Réorganise l'ordre d'affichage
     *
     * @param array $orderedIds Liste des IDs dans le nouvel ordre
     */
    public function reorder(array $orderedIds): void
    {
        $em = $this->getEntityManager();
        
        foreach ($orderedIds as $index => $id) {
            $em->createQuery('UPDATE App\Entity\EventType et SET et.displayOrder = :order WHERE et.id = :id')
               ->setParameter('order', $index + 1)
               ->setParameter('id', $id)
               ->execute();
        }
    }

    public function save(EventType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EventType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
