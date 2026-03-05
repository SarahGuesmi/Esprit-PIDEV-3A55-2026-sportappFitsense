<?php

namespace App\Service;

use App\Entity\RecetteNutritionnelle;

class RecetteManager
{
    /**
     * Validates a recipe based on business rules.
     * Rule 1: Calories (Kcal) must be positive.
     * Rule 2: Title must not be empty.
     */
    public function validate(RecetteNutritionnelle $recette): bool
    {
        if ($recette->getKcal() !== null && $recette->getKcal() <= 0) {
            throw new \InvalidArgumentException("Les calories d'une recette doivent être positives.");
        }

        if (empty($recette->getTitle())) {
            throw new \InvalidArgumentException("Le titre de la recette est obligatoire.");
        }

        return true;
    }
}
