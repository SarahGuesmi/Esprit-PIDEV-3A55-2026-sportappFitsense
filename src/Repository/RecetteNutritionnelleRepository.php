<?php

namespace App\Repository;

use App\Entity\RecetteNutritionnelle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RecetteNutritionnelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecetteNutritionnelle::class);
    }

   public function searchForCoach($coach, ?string $q, ?int $kcal, ?int $proteins)
{
    $qb = $this->createQueryBuilder('r')
        ->andWhere('r.coach = :coach')
        ->setParameter('coach', $coach)
        ->orderBy('r.id', 'DESC');

    if ($q) {
        $qb->andWhere('LOWER(r.title) LIKE :q')
           ->setParameter('q', '%'.mb_strtolower($q).'%');
    }

    if ($kcal !== null && $kcal !== '') {
   $qb->andWhere('r.kcal <= :kcal')->setParameter('kcal', (int)$kcal);
}

if ($proteins !== null && $proteins !== '') {
   $qb->andWhere('r.proteins >= :proteins')->setParameter('proteins', (int)$proteins);
}


    return $qb->getQuery()->getResult();
}
}