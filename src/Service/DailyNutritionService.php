<?php

namespace App\Service;

use App\Entity\DailyNutrition;
use App\Entity\User;
use App\Entity\ProfilePhysique;
use App\Entity\Notification;
use App\Repository\DailyNutritionRepository;
use Doctrine\ORM\EntityManagerInterface;

class DailyNutritionService
{
    private EntityManagerInterface $em;
    private DailyNutritionRepository $dailyRepo;

    public function __construct(EntityManagerInterface $em, DailyNutritionRepository $dailyRepo)
    {
        $this->em = $em;
        $this->dailyRepo = $dailyRepo;
    }

    public function getOrCreateDaily(User $user): ?DailyNutrition
    {
        $today = new \DateTimeImmutable('today');
        $daily = $this->dailyRepo->findTodayForUser($user);
        
        if ($daily) {
            return $daily;
        }

        $profileRepo = $this->em->getRepository(ProfilePhysique::class);
        $profile = $profileRepo->findOneBy(['user' => $user], ['id' => 'DESC']);
        $weight = $profile?->getWeight() ?: 70;

        $daily = new DailyNutrition();
        $daily->setUser($user);
        $daily->setDayDate($today->setTime(0, 0, 0));
        $daily->setCalories(0);
        $daily->setWaterMl(0);
        
        // Dynamic goals based on weight
        $daily->setCaloriesGoal((int)($weight * 28)); 
        $daily->setWaterGoal((int)($weight * 30));

        try {
            $this->em->persist($daily);
            $this->em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return $this->dailyRepo->findOneBy([
                'user' => $user,
                'dayDate' => new \DateTimeImmutable('today')
            ]);
        }

        return $daily;
    }

    public function addWater(User $user, int $amountMl): ?DailyNutrition
    {
        $daily = $this->getOrCreateDaily($user);
        if (!$daily) return null;
        $daily->setWaterMl($daily->getWaterMl() + $amountMl);
        
        $this->checkWaterGoal($daily, $user);
        
        $this->em->flush();
        return $daily;
    }

    private function checkWaterGoal(DailyNutrition $daily, User $user): void
    {
        if ($daily->getWaterMl() >= $daily->getWaterGoal() && !$daily->isWaterGoalAlertShown()) {
            $notification = new Notification();
            $notification->setMessage("Good job! You've reached your hydration goal for today! 💧");
            $notification->setType('water_goal_reached');
            $notification->setRelatedUser($user);
            
            $this->em->persist($notification);
            $daily->setWaterGoalAlertShown(true);
        }
    }
}
