<?php

namespace App\Entity;

use App\Repository\UserExerciseProgressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: UserExerciseProgressRepository::class)]
#[ORM\Table(name: 'user_exercise_progression')]
#[ORM\UniqueConstraint(name: 'user_exercise_unique', columns: ['user_id', 'exercise_id'])]
#[ORM\HasLifecycleCallbacks]
class UserExerciseProgress
{
    use TimestampableTrait, BlameableTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'exerciseProgress')]
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



    public function __construct()
    {
    }

    public function getId(): ?Uuid { return $this->id; }

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


}