<?php

namespace App\Repository;

use App\Entity\FeedbackResponse;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackResponse>
 *
 * @method FeedbackResponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackResponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackResponse[]    findAll()
 * @method FeedbackResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackResponse::class);
    }

    /**
     * @return FeedbackResponse[] Returns feedback responses linked to a given coach
     *
     * We now rely on the explicit FeedbackResponse.coach association instead of
     * the Workout.coach field, so that feedback is shown even if some workouts
     * don't have their coach set.
     */
    public function findByCoachWorkouts(User $coach): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.coach = :coach')
            ->setParameter('coach', $coach)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return FeedbackResponse[] Returns all feedback responses for a specific workout
     */
    public function findByWorkout(int $workoutId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.workout = :workoutId')
            ->setParameter('workoutId', $workoutId)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return FeedbackResponse[] Returns all feedback responses
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
