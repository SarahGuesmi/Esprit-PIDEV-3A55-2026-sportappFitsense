<?php

namespace App\Service;

use App\Entity\Exercise;

class ExerciseManager
{
    /**
     * Validates an exercise based on business rules.
     * Rule 1: Name (Nom) must not be empty.
     * Rule 2: Type must not be empty.
     */
    public function validate(Exercise $exercise): bool
    {
        if (empty($exercise->getNom())) {
            throw new \InvalidArgumentException("Le nom de l'exercice est obligatoire.");
        }

        if (empty($exercise->getType())) {
            throw new \InvalidArgumentException("Le type de l'exercice est obligatoire.");
        }

        return true;
    }
}
