<?php

namespace App\Entity;

use App\Repository\RecommendedExerciseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecommendedExerciseRepository::class)]
class RecommendedExercise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null;

    #[ORM\ManyToOne(inversedBy: 'recommendedExercises')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recommendation $recommendation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getRecommendation(): ?Recommendation
    {
        return $this->recommendation;
    }

    public function setRecommendation(?Recommendation $recommendation): static
    {
        $this->recommendation = $recommendation;
        return $this;
    }
}
