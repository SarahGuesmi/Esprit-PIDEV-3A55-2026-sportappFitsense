<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixture extends Fixture
{
    private $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setFirstname('Admin');
        $admin->setLastname('Principal');
        $admin->setEmail('sarahguesmi223@gmail.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'adminpass123')); // Change le MDP
        $admin->setAccountStatus('active');
        $admin->setDateCreation(new \DateTimeImmutable());

        $manager->persist($admin);
        $manager->flush();
    }
}