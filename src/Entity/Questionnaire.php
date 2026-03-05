<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\QuestionnaireRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Questionnaire
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;
 
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'questionnaires')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Workout::class)]
    #[ORM\JoinTable(name: 'questionnaire_workout')]
    private Collection $workouts;

    public function __construct()
    {
        $this->workouts = new ArrayCollection();
    }

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
        }
        return $this;
    }

    public function removeWorkout(Workout $workout): self
    {
        $this->workouts->removeElement($workout);
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $noteGlobale = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $satisfaction = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $intensite = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $exercicesCompris = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $duree = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ressentiPhysique = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stress = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $motivation = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $progression = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rapprocheObjectifs = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $options = [];

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $coach = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'response'])]
    private string $type = 'response';

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateSoumission = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userName = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getCoach(): ?User
    {
        return $this->coach;
    }

    public function setCoach(?User $coach): self
    {
        $this->coach = $coach;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    // Getters and Setters

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }


    public function getNoteGlobale(): ?int
    {
        return $this->noteGlobale;
    }

    public function setNoteGlobale(?int $noteGlobale): self
    {
        $this->noteGlobale = $noteGlobale;
        return $this;
    }

    public function getSatisfaction(): ?int
    {
        return $this->satisfaction;
    }

    public function setSatisfaction(?int $satisfaction): self
    {
        $this->satisfaction = $satisfaction;
        return $this;
    }

    public function getIntensite(): ?string
    {
        return $this->intensite;
    }

    public function setIntensite(?string $intensite): self
    {
        $this->intensite = $intensite;
        return $this;
    }

    public function getExercicesCompris(): ?string
    {
        return $this->exercicesCompris;
    }

    public function setExercicesCompris(?string $exercicesCompris): self
    {
        $this->exercicesCompris = $exercicesCompris;
        return $this;
    }

    public function getDuree(): ?string
    {
        return $this->duree;
    }

    public function setDuree(?string $duree): self
    {
        $this->duree = $duree;
        return $this;
    }

    public function getRessentiPhysique(): ?string
    {
        return $this->ressentiPhysique;
    }

    public function setRessentiPhysique(?string $ressentiPhysique): self
    {
        $this->ressentiPhysique = $ressentiPhysique;
        return $this;
    }

    public function getStress(): ?string
    {
        return $this->stress;
    }

    public function setStress(?string $stress): self
    {
        $this->stress = $stress;
        return $this;
    }

    public function getMotivation(): ?string
    {
        return $this->motivation;
    }

    public function setMotivation(?string $motivation): self
    {
        $this->motivation = $motivation;
        return $this;
    }

    public function getProgression(): ?string
    {
        return $this->progression;
    }

    public function setProgression(?string $progression): self
    {
        $this->progression = $progression;
        return $this;
    }

    public function getRapprocheObjectifs(): ?int
    {
        return $this->rapprocheObjectifs;
    }

    public function setRapprocheObjectifs(?int $rapprocheObjectifs): self
    {
        $this->rapprocheObjectifs = $rapprocheObjectifs;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getDateSoumission(): ?\DateTimeImmutable
    {
        return $this->dateSoumission;
    }

    protected function setDateSoumission(?\DateTimeImmutable $dateSoumission): self
    {
        $this->dateSoumission = $dateSoumission;
        return $this;
    }

    #[ORM\PrePersist]
    public function setDateSoumissionValue(): void
    {
        if ($this->dateSoumission === null && $this->type === 'response') {
            $this->dateSoumission = new \DateTimeImmutable();
        }
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

}
