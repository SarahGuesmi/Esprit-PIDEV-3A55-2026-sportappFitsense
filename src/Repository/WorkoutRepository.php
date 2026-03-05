<?php

namespace App\Repository;

use App\Entity\Workout;
use App\Entity\User;
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
        $names = $user->getObjectifNames();
        if (empty($names)) {
            return [];
        }

        return $this->createQueryBuilder('w')
            ->innerJoin('w.objectifs', 'o')
            ->andWhere('o.name IN (:names)')
            ->setParameter('names', $names)
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

    public function findByUserObjectifsFiltered(User $user, ?string $niveau, ?int $dureeMax): array
{
    // Get the user's objectif names (since each profile has its own ObjectifSportif rows,
    // we match by name rather than by entity ID)
    $names = $user->getObjectifNames();

    if (empty($names)) {
        return [];
    }

    $qb = $this->createQueryBuilder('w')
        ->leftJoin('w.exercises', 'e')->addSelect('e')
        ->join('w.objectifs', 'o')
        ->where('o.name IN (:names)')
        ->setParameter('names', $names)
        ->orderBy('w.nom', 'ASC');

    if ($niveau) {
        $qb->andWhere('w.niveau = :niveau')
           ->setParameter('niveau', $niveau);
    }

    if ($dureeMax) {
        $qb->andWhere('w.duree <= :duree')
           ->setParameter('duree', $dureeMax);
    }

    return $qb->getQuery()->getResult();
}

    /**
     * Find a workout with its exercises loaded
     */
    public function findWithExercises(string $id): ?Workout
    {
        // Use find() first to correctly resolve the UUID string to binary
        $workout = $this->find($id);
        if (!$workout) {
            return null;
        }

        // Eagerly initialize the lazy collections
        $workout->getExercises()->toArray();
        $workout->getObjectifs()->toArray();

        return $workout;
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
