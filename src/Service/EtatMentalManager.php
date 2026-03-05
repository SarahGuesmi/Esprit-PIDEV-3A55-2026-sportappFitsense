<?php

namespace App\Service;

use App\Entity\EtatMental;

class EtatMentalManager
{
    /**
     * Validates a mental state based on business rules.
     * Rule 1: Stress level must be between 1 and 5.
     * Rule 2: Mood must be between 1 and 5.
     */
    public function validate(EtatMental $etat): bool
    {
        $stress = $etat->getStressLevel();
        if ($stress === null || $stress < 1 || $stress > 5) {
            throw new \InvalidArgumentException("Le niveau de stress doit être compris entre 1 et 5.");
        }

        $mood = $etat->getMood();
        if ($mood === null || $mood < 1 || $mood > 5) {
            throw new \InvalidArgumentException("L'humeur doit être comprise entre 1 et 5.");
        }

        return true;
    }
}
