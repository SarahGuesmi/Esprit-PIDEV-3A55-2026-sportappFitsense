<?php

namespace App\Repository;

use App\Entity\Workout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workout>
 */
class WorkoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workout::class);
    }

    /**
     * Finds workouts that match the objectives of a given user.
     */
    public function findByUserObjectifs(User $user): array
    {
        $objectifs = $user->getObjectifs();
        if ($objectifs->isEmpty()) {
            return [];
        }

        return $this->createQueryBuilder('w')
            ->innerJoin('w.objectifs', 'o')
            ->andWhere('o.id IN (:objectifs)')
            ->setParameter('objectifs', $objectifs)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds workouts for a specific objective.
     */
    public function findByObjectif($objectif): array
    {
        return $this->createQueryBuilder('w')
            ->innerJoin('w.objectifs', 'o')
            ->andWhere('o.id = :objectif')
            ->setParameter('objectif', $objectif)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Workout[] Returns an array of Workout objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('w.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Workout
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
