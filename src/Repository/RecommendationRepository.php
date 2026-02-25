<?php

namespace App\Repository;

use App\Entity\Recommendation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recommendation>
 *
 * @method Recommendation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recommendation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recommendation[]    findAll()
 * @method Recommendation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecommendationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recommendation::class);
    }
}
