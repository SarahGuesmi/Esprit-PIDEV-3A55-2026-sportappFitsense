<?php

namespace App\Tests\Service;

use App\Entity\Workout;
use App\Service\WorkoutManager;
use PHPUnit\Framework\TestCase;

class WorkoutManagerTest extends TestCase
{
    public function testValidWorkout(): void
    {
        $workout = new Workout();
        $workout->setDuree(30);
        $workout->setNiveau('Intermédiaire');

        $manager = new WorkoutManager();
        $this->assertTrue($manager->validate($workout));
    }

    public function testWorkoutWithNegativeDuration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La durée d'un entraînement doit être positive.");

        $workout = new Workout();
        $workout->setDuree(-5);
        $workout->setNiveau('Débutant');

        $manager = new WorkoutManager();
        $manager->validate($workout);
    }

    public function testWorkoutWithoutLevel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le niveau de l'entraînement est obligatoire.");

        $workout = new Workout();
        $workout->setDuree(45);
        // No niveau set

        $manager = new WorkoutManager();
        $manager->validate($workout);
    }
}
