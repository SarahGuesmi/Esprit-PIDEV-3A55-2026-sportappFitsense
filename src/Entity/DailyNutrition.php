<?php

namespace App\Entity;

use App\Repository\DailyNutritionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use App\Trait\BlameableTrait;

#[ORM\Entity(repositoryClass: DailyNutritionRepository::class)]
#[ORM\Table(name: 'daily_nutrition')]
#[ORM\UniqueConstraint(name: 'uniq_user_day', columns: ['user_id', 'day_date'])]
class DailyNutrition
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    // ✅ Date du jour (sans heure)
    #[ORM\Column(name: 'day_date', type: 'date_immutable')]
    private ?\DateTimeImmutable $dayDate = null;

    // ✅ Totaux nutrition du jour
    #[ORM\Column(type: 'integer')]
    private int $calories = 0;

  
    // ✅ Eau en ml
    #[ORM\Column(type: 'integer')]
    private int $waterMl = 0;

    // ✅ Objectifs journaliers
    #[ORM\Column(type: 'integer')]
    private int $caloriesGoal;

    #[ORM\Column(type: 'integer')]
    private int $waterGoal ;

    // ✅ Lien vers user
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'dailyNutritions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]

    private ?User $user = null;

    // ================= Getters/Setters =================

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDayDate(): ?\DateTimeImmutable
    {
        return $this->dayDate;
    }

    public function setDayDate(\DateTimeImmutable $dayDate): self
    {
        $this->dayDate = $dayDate;
        return $this;
    }

    public function getCalories(): int
    {
        return $this->calories;
    }

    public function setCalories(int $calories): self
    {
        $this->calories = max(0, $calories);
        return $this;
    }

   

    #[ORM\Column(type: 'boolean')]
private bool $overGoalAlertShown = false;

public function isOverGoalAlertShown(): bool
{
    return $this->overGoalAlertShown;
}

public function setOverGoalAlertShown(bool $shown): self
{
    $this->overGoalAlertShown = $shown;
    return $this;
}

#[ORM\Column(type: 'boolean')]
private bool $waterGoalAlertShown = false;

public function isWaterGoalAlertShown(): bool
{
    return $this->waterGoalAlertShown;
}

public function setWaterGoalAlertShown(bool $shown): self
{
    $this->waterGoalAlertShown = $shown;
    return $this;
}


    public function getWaterMl(): int
    {
        return $this->waterMl;
    }
    public function __construct()
{
    $this->dayDate = new \DateTimeImmutable('today');
}

    public function setWaterMl(int $waterMl): self
    {
        $this->waterMl = max(0, $waterMl);
        return $this;
    }

    public function getCaloriesGoal(): int
    {
        return $this->caloriesGoal;
    }

    public function setCaloriesGoal(int $caloriesGoal): self
    {
        $this->caloriesGoal = max(0, $caloriesGoal);
        return $this;
    }

    public function getWaterGoal(): int
    {
        return $this->waterGoal;
    }

    public function setWaterGoal(int $waterGoal): self
    {
        $this->waterGoal = max(0, $waterGoal);
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
}