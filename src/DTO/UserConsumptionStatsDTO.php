<?php

namespace App\DTO;

class UserConsumptionStatsDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $email,
        public readonly float $totalKcal,
        public readonly float $totalProteins,
        public readonly int $recipeCount
    ) {
    }
}
