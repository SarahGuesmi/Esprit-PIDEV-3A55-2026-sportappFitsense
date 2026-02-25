<?php

namespace App\Repository;

use App\Entity\ProfilePhysique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProfilePhysiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfilePhysique::class);
    }

    // Tu peux ajouter tes méthodes personnalisées ici si besoin
}