<?php

namespace App\Tests\Service;

use App\Entity\RecetteNutritionnelle;
use App\Service\RecetteManager;
use PHPUnit\Framework\TestCase;

class RecetteManagerTest extends TestCase
{
    public function testValidRecette(): void
    {
        $recette = new RecetteNutritionnelle();
        $recette->setTitle('Salade César');
        $recette->setKcal(350);

        $manager = new RecetteManager();
        $this->assertTrue($manager->validate($recette));
    }

    public function testRecetteWithNegativeCalories(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Les calories d'une recette doivent être positives.");

        $recette = new RecetteNutritionnelle();
        $recette->setTitle('Soupe');
        $recette->setKcal(-50);

        $manager = new RecetteManager();
        $manager->validate($recette);
    }

    public function testRecetteWithoutTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le titre de la recette est obligatoire.");

        $recette = new RecetteNutritionnelle();
        $recette->setKcal(200);
        // No title set

        $manager = new RecetteManager();
        $manager->validate($recette);
    }
}
