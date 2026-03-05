<?php

namespace App\Service;

use App\Entity\Workout;

class WorkoutManager
{
    /**
     * Validates a workout based on business rules.
     * Rule 1: Duration must be positive.
     * Rule 2: Level (Niveau) must not be empty.
     */
    public function validate(Workout $workout): bool
    {
        if ($workout->getDuree() <= 0) {
            throw new \InvalidArgumentException("La durée d'un entraînement doit être positive.");
        }

        if (empty($workout->getNiveau())) {
            throw new \InvalidArgumentException("Le niveau de l'entraînement est obligatoire.");
        }

        return true;
    }
}
