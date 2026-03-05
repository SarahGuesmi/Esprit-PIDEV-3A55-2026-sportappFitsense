<?php
namespace App\Entity;

use App\Repository\ObjectifSportifRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ObjectifSportifRepository::class)]
class ObjectifSportif
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type:"string", length:255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: ProfilePhysique::class, inversedBy:"objectifs")]
    #[ORM\JoinColumn(nullable:false, onDelete: 'CASCADE')]
    private ?ProfilePhysique $profilePhysique = null;

    #[ORM\ManyToMany(targetEntity: Workout::class, mappedBy: 'objectifs')]
    private Collection $workouts;

    public function __construct()
    {
        $this->workouts = new ArrayCollection();
    }

    // Getters & Setters
    public function getId(): ?Uuid
    {
        return $this->id;
    }

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