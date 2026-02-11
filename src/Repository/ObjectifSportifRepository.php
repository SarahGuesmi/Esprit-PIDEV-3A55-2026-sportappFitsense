<?php

namespace App\Repository;

use App\Entity\ObjectifSportif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ObjectifSportif>
 */
class ObjectifSportifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ObjectifSportif::class);
    }

    /**
     * Exemple : récupérer les objectifs d’un profil physique
     */
    public function findByProfilePhysique(int $profileId): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.profilePhysique = :profile')
            ->setParameter('profile', $profileId)
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
