<?php

namespace App\Repository;

use App\Entity\UserExerciseProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserExerciseProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserExerciseProgress::class);
    }

    public function findOneByUserAndExercise($user, $exercise): ?UserExerciseProgress
    {
        return $this->findOneBy([
            'user'     => $user,
            'exercise' => $exercise,
        ]);
    }
}