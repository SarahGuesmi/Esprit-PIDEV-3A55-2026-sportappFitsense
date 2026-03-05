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
        // 1. ADMIN (Sarah Guesmi)
        $admin = new User();
        $admin->setFirstname('Sarah');
        $admin->setLastname('Guesmi');
        $admin->setEmail('sarahguesmi223@gmail.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setAccountStatus('active');
        $admin->setDateCreation(new \DateTimeImmutable());
        $admin->setPassword($this->passwordHasher->hashPassword($admin, '123456'));
        $manager->persist($admin);

        // 2. COACH (General Coach)
        $coach = new User();
        $coach->setFirstname('Coach');
        $coach->setLastname('Fitsense');
        $coach->setEmail('coach@fitsense.app');
        $coach->setRoles(['ROLE_COACH']);
        $coach->setAccountStatus('active');
        $coach->setDateCreation(new \DateTimeImmutable());
        $coach->setPassword($this->passwordHasher->hashPassword($coach, 'password123'));
        $manager->persist($coach);

        // 3. REGULAR USER (Demo User)
        $user = new User();
        $user->setFirstname('Demo');
        $user->setLastname('User');
        $user->setEmail('user@test.com');
        $user->setRoles(['ROLE_USER']);
        $user->setAccountStatus('active');
        $user->setDateCreation(new \DateTimeImmutable());
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $manager->persist($user);

        $manager->flush();
    }
}
