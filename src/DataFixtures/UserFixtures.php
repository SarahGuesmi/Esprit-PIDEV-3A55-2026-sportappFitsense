<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // COACH
        $coach = new User();
        $coach->setFirstname('Coach');
        $coach->setLastname('Principal');
        $coach->setEmail('coach@test.com');
        $coach->setRoles(['ROLE_COACH']);
        $coach->setAccountStatus('active');
        $coach->setDateCreation(new \DateTimeImmutable());

        $coach->setPassword(
            $this->passwordHasher->hashPassword($coach, 'coach123')
        );

        $manager->persist($coach);

    
    
        $manager->flush();
    }
}
