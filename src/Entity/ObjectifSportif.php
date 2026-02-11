<?php
namespace App\Entity;

use App\Repository\ObjectifSportifRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ObjectifSportifRepository::class)]
class ObjectifSportif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: ProfilePhysique::class, inversedBy:"objectifs")]
    #[ORM\JoinColumn(nullable:false)]
    private ?ProfilePhysique $profilePhysique = null;

    #[ORM\ManyToMany(targetEntity: Workout::class, mappedBy: 'objectifs')]
    private Collection $workouts;

    public function __construct()
    {
        $this->workouts = new ArrayCollection();
    }

    // Getters & Setters
    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getProfilePhysique(): ?ProfilePhysique { return $this->profilePhysique; }
    public function setProfilePhysique(?ProfilePhysique $profile): self { $this->profilePhysique = $profile; return $this; }

    /**
     * @return Collection<int, Workout>
     */
    public function getWorkouts(): Collection
    {
        return $this->workouts;
    }

    public function addWorkout(Workout $workout): self
    {
        if (!$this->workouts->contains($workout)) {
            $this->workouts->add($workout);
            $workout->addObjectif($this);
        }
        return $this;
    }

    public function removeWorkout(Workout $workout): self
    {
        if ($this->workouts->removeElement($workout)) {
            $workout->removeObjectif($this);
        }
        return $this;
    }
}