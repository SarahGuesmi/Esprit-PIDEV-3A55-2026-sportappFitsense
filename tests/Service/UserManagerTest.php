<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testValidUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $manager = new UserManager();
        $this->assertTrue($manager->validate($user, 'password123'));
    }

    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'adresse email est invalide.");

        $user = new User();
        $user->setEmail('invalid-email');

        $manager = new UserManager();
        $manager->validate($user, 'password123');
    }

    public function testUserWithShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le mot de passe doit contenir au moins 8 caractères.");

        $user = new User();
        $user->setEmail('test@example.com');

        $manager = new UserManager();
        $manager->validate($user, 'short');
    }
}
