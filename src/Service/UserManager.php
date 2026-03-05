<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    /**
     * Validates a user based on business rules.
     * Rule 1: Email must be valid.
     * Rule 2: Password must be at least 8 characters long.
     */
    public function validate(User $user, ?string $plainPassword = null): bool
    {
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("L'adresse email est invalide.");
        }

        if ($plainPassword !== null && strlen($plainPassword) < 8) {
            throw new \InvalidArgumentException("Le mot de passe doit contenir au moins 8 caractères.");
        }

        return true;
    }
}
