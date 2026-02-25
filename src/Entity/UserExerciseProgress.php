<?php

namespace App\Entity;

use App\Repository\UserExerciseProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserExerciseProgressRepository::class)]
#[ORM\UniqueConstraint(name: 'user_exercise_unique', columns: ['user_id', 'exercise_id'])]
class UserExerciseProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercise $exercise = null;

    #[ORM\Column(length: 50)]
    private string $status = 'pending'; // pending | in_progress | done

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $elapsedTime = null; // temps réel passé en secondes

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getExercise(): ?Exercise { return $this->exercise; }
    public function setExercise(?Exercise $exercise): static { $this->exercise = $exercise; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getElapsedTime(): ?int { return $this->elapsedTime; }
    public function setElapsedTime(?int $elapsedTime): static { $this->elapsedTime = $elapsedTime; return $this; }

    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function setCompletedAt(?\DateTimeImmutable $completedAt): static { $this->completedAt = $completedAt; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}