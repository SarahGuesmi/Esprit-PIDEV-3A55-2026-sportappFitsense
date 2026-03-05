<?php

namespace App\Entity;

use App\Repository\EtatMentalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: EtatMentalRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EtatMental
{
    use TimestampableTrait, BlameableTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'etatMentals')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Please rate your stress level.")]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $stressLevel = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Please rate your sleep quality.")]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $sleepQuality = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Please rate your mood.")]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $mood = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Please rate your motivation.")]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $motivation = null;

    #[ORM\Column(type: 'integer')]
    private ?int $totalScore = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $status = null;



    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStressLevel(): ?int
    {
        return $this->stressLevel;
    }

    public function setStressLevel(int $stressLevel): static
    {
        $this->stressLevel = $stressLevel;

        return $this;
    }

    public function getSleepQuality(): ?int
    {
        return $this->sleepQuality;
    }

    public function setSleepQuality(int $sleepQuality): static
    {
        $this->sleepQuality = $sleepQuality;

        return $this;
    }

    public function getMood(): ?int
    {
        return $this->mood;
    }

    public function setMood(int $mood): static
    {
        $this->mood = $mood;

        return $this;
    }

    public function getMotivation(): ?int
    {
        return $this->motivation;
    }

    public function setMotivation(int $motivation): static
    {
        $this->motivation = $motivation;

        return $this;
    }

    public function getTotalScore(): ?int
    {
        return $this->totalScore;
    }

    public function setTotalScore(int $totalScore): static
    {
        $this->totalScore = $totalScore;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\NotBlank(message: "Please rate your mental fatigue.")]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $mentalFatigue = null;

    // ... existing properties ...

    public function getMentalFatigue(): ?int
    {
        return $this->mentalFatigue;
    }

    public function setMentalFatigue(int $mentalFatigue): static
    {
        $this->mentalFatigue = $mentalFatigue;

        return $this;
    }



    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
