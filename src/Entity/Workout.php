<?php

namespace App\Entity;

use App\Repository\WorkoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkoutRepository::class)]
class Workout
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ================= Nom =================
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    // ================= Niveau =================
    #[Assert\NotBlank(message: "Le niveau est obligatoire.")]
    #[ORM\Column(length: 50)]
    private ?string $niveau = null;

    // ================= Durée =================
    #[Assert\NotBlank(message: "La durée est obligatoire.")]
    #[Assert\Positive(message: "La durée doit être un nombre positif.")]
    #[ORM\Column]
    private ?int $duree = null;

    // ================= Description =================
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // ================= Exercises =================
    #[Assert\Count(
        min: 1,
        minMessage: "Vous devez sélectionner au moins un exercice."
    )]
    #[ORM\ManyToMany(targetEntity: Exercise::class, inversedBy: 'workouts')]
    private Collection $exercises;

    // ================= Objectifs =================
    #[Assert\Count(
        min: 1,
        minMessage: "Vous devez sélectionner au moins un objectif."
    )]
    #[ORM\ManyToMany(targetEntity: ObjectifSportif::class, inversedBy: 'workouts')]
    #[ORM\JoinTable(name: 'workout_objectif')]
    private Collection $objectifs;

    public function __construct()
    {
        $this->exercises = new ArrayCollection();
        $this->objectifs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

   public function setDuree(?int $duree): static
{
    $this->duree = $duree;
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

    // ================= Exercises =================
    public function getExercises(): Collection
    {
        return $this->exercises;
    }

    public function addExercise(Exercise $exercise): static
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
        }
        return $this;
    }

    public function removeExercise(Exercise $exercise): static
    {
        $this->exercises->removeElement($exercise);
        return $this;
    }

    // ================= Objectifs =================
    public function getObjectifs(): Collection
    {
        return $this->objectifs;
    }

    public function addObjectif(ObjectifSportif $objectif): static
    {
        if (!$this->objectifs->contains($objectif)) {
            $this->objectifs->add($objectif);
            $objectif->addWorkout($this);
        }
        return $this;
    }

    public function removeObjectif(ObjectifSportif $objectif): static
    {
        if ($this->objectifs->removeElement($objectif)) {
            $objectif->removeWorkout($this);
        }
        return $this;
    }
}
