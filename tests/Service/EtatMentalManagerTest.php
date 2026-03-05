<?php

namespace App\Tests\Service;

use App\Entity\EtatMental;
use App\Service\EtatMentalManager;
use PHPUnit\Framework\TestCase;

class EtatMentalManagerTest extends TestCase
{
    public function testValidEtatMental()
    {
        $etat = new EtatMental();
        $etat->setStressLevel(3);
        $etat->setMood(4);

        $manager = new EtatMentalManager();
        $this->assertTrue($manager->validate($etat));
    }

    public function testEtatMentalWithInvalidStress()
    {
        $this->expectException(\InvalidArgumentException::class);
        $etat = new EtatMental();
        $etat->setStressLevel(10);

        $manager = new EtatMentalManager();
        $manager->validate($etat);
    }

    public function testEtatMentalWithInvalidMood()
    {
        $this->expectException(\InvalidArgumentException::class);
        $etat = new EtatMental();
        $etat->setStressLevel(3);
        $etat->setMood(0);

        $manager = new EtatMentalManager();
        $manager->validate($etat);
    }
}
