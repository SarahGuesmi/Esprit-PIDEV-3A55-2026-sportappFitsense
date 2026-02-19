<?php

namespace App\Entity;

use App\Repository\RecommendationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecommendationRepository::class)]
class Recommendation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $coach = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'recommendation', targetEntity: RecommendedExercise::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $recommendedExercises;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->recommendedExercises = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoach(): ?User
    {
        return $this->coach;
    }

    public function setCoach(?User $coach): static
    {
        $this->coach = $coach;
        return $this;
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

    /**
     * @return Collection<int, RecommendedExercise>
     */
    public function getRecommendedExercises(): Collection
    {
        return $this->recommendedExercises;
    }

    public function addRecommendedExercise(RecommendedExercise $recommendedExercise): static
    {
        if (!$this->recommendedExercises->contains($recommendedExercise)) {
            $this->recommendedExercises->add($recommendedExercise);
            $recommendedExercise->setRecommendation($this);
        }
        return $this;
    }

    public function removeRecommendedExercise(RecommendedExercise $recommendedExercise): static
    {
        if ($this->recommendedExercises->removeElement($recommendedExercise)) {
            // set the owning side to null (unless already changed)
            if ($recommendedExercise->getRecommendation() === $this) {
                $recommendedExercise->setRecommendation(null);
            }
        }
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
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
}
