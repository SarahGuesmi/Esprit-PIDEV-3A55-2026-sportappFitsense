<?php

namespace App\Tests\Service;

use App\Entity\ProfilePhysique;
use App\Service\ProfilePhysiqueManager;
use PHPUnit\Framework\TestCase;

class ProfilePhysiqueManagerTest extends TestCase
{
    public function testValidProfile(): void
    {
        $profile = new ProfilePhysique();
        $profile->setWeight(75.5);
        $profile->setHeight(180.0);

        $manager = new ProfilePhysiqueManager();
        $this->assertTrue($manager->validate($profile));
    }

    public function testProfileWithInvalidWeight(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le poids doit être compris entre 30 et 300 kg.");

        $profile = new ProfilePhysique();
        $profile->setWeight(20.0);

        $manager = new ProfilePhysiqueManager();
        $manager->validate($profile);
    }

    public function testProfileWithInvalidHeight(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La taille doit être comprise entre 100 et 250 cm.");

        $profile = new ProfilePhysique();
        $profile->setHeight(300.0);

        $manager = new ProfilePhysiqueManager();
        $manager->validate($profile);
    }
}
