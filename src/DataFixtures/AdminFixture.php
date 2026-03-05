<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Empty fixture to avoid duplication with UserFixtures.
 */
class AdminFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Everything moves to UserFixtures for consolidation.
    }
}