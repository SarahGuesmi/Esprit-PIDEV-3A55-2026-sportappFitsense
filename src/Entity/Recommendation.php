<?php

namespace App\Entity;

use App\Repository\RecommendationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: RecommendationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Recommendation
{
    use TimestampableTrait, BlameableTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'coachRecommendations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $coach = null;


    #[ORM\ManyToOne(inversedBy: 'userRecommendations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;


    #[ORM\OneToMany(mappedBy: 'recommendation', targetEntity: RecommendedExercise::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $recommendedExercises;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;



    public function __construct()
    {
        $this->recommendedExercises = new ArrayCollection();
    }

    public function getId(): ?Uuid
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


}
