<?php

namespace App\Service;

use App\Entity\ProfilePhysique;

class ProfilePhysiqueManager
{
    /**
     * Validates a physical profile based on business rules.
     * Rule 1: Weight must be between 30 and 300 kg.
     * Rule 2: Height must be between 100 and 250 cm.
     */
    public function validate(ProfilePhysique $profile): bool
    {
        $weight = $profile->getWeight();
        if ($weight !== null && ($weight < 30 || $weight > 300)) {
            throw new \InvalidArgumentException("Le poids doit être compris entre 30 et 300 kg.");
        }

        $height = $profile->getHeight();
        if ($height !== null && ($height < 100 || $height > 250)) {
            throw new \InvalidArgumentException("La taille doit être comprise entre 100 et 250 cm.");
        }

        return true;
    }
}
