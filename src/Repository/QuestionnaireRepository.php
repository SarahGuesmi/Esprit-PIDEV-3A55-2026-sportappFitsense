<?php

namespace App\Repository;

use App\Entity\Questionnaire;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Questionnaire>
 */
class QuestionnaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Questionnaire::class);
    }

    /**
     * @return Questionnaire[] Returns an array of Questionnaire objects matching the user name
     */
    public function findByUserNameLike(string $search): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.userName LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('q.dateSoumission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Questionnaire[] Returns an array of Questionnaire by workout ID
     */
    public function findByWorkoutId(int $workoutId): array
    {
        return $this->createQueryBuilder('q')
            ->innerJoin('q.workouts', 'w')
            ->andWhere('w.id = :workoutId')
            ->setParameter('workoutId', $workoutId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Questionnaire[] Returns an array of Questionnaire by workout title
     */
    public function findByWorkoutTitle(string $workoutTitle): array
    {
        return $this->createQueryBuilder('q')
            ->innerJoin('q.workouts', 'w')
            ->andWhere('LOWER(w.nom) LIKE LOWER(:workoutTitle)')
            ->setParameter('workoutTitle', '%' . trim($workoutTitle) . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Questionnaire[] Returns an array of user response questionnaires
     */
    public function findAllUserResponses(): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.type = :type')
            ->setParameter('type', 'response')
            ->orderBy('q.dateSoumission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Questionnaire[] Returns an array of user responses filtered by coach's templates
     */
    public function findUserResponsesForCoach(User $coach): array
    {
        // First get all workout IDs linked to coach's templates
        $templateWorkoutIds = $this->createQueryBuilder('q')
            ->select('w.id')
            ->innerJoin('q.workouts', 'w')
            ->andWhere('q.type = :templateType')
            ->andWhere('q.coach = :coach')
            ->setParameter('templateType', 'template')
            ->setParameter('coach', $coach)
            ->getQuery()
            ->getResult();

        $workoutIds = [];
        foreach ($templateWorkoutIds as $row) {
            if (isset($row['id'])) {
                $workoutIds[] = $row['id'];
            }
        }

        // If no templates exist, return all user responses
        if (empty($workoutIds)) {
            return $this->createQueryBuilder('q')
                ->andWhere('q.type = :responseType')
                ->setParameter('responseType', 'response')
                ->orderBy('q.dateSoumission', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // Then get all user responses for these workouts
        return $this->createQueryBuilder('q')
            ->innerJoin('q.workouts', 'w')
            ->andWhere('q.type = :responseType')
            ->andWhere('w.id IN (:workoutIds)')
            ->setParameter('responseType', 'response')
            ->setParameter('workoutIds', $workoutIds)
            ->orderBy('q.dateSoumission', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
