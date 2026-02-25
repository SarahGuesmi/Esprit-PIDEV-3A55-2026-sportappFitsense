<?php

namespace App\Entity;

use App\Repository\EtatMentalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EtatMentalRepository::class)]
class EtatMental
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'etatMentals')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
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

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
