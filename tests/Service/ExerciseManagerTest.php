<?php

namespace App\Tests\Service;

use App\Entity\Exercise;
use App\Service\ExerciseManager;
use PHPUnit\Framework\TestCase;

class ExerciseManagerTest extends TestCase
{
    public function testValidExercise(): void
    {
        $exercise = new Exercise();
        $exercise->setNom('Pompes');
        $exercise->setType('Force');

        $manager = new ExerciseManager();
        $this->assertTrue($manager->validate($exercise));
    }

    public function testExerciseWithoutName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom de l'exercice est obligatoire.");

        $exercise = new Exercise();
        $exercise->setType('Cardio');
        // No name set

        $manager = new ExerciseManager();
        $manager->validate($exercise);
    }

    public function testExerciseWithoutType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le type de l'exercice est obligatoire.");

        $exercise = new Exercise();
        $exercise->setNom('Squat');
        // No type set

        $manager = new ExerciseManager();
        $manager->validate($exercise);
    }
}
