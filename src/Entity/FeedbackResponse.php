<?php

namespace App\Entity;

use App\Repository\FeedbackResponseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: FeedbackResponseRepository::class)]
#[ORM\Table(name: 'feedback_response')]
#[ORM\HasLifecycleCallbacks]
class FeedbackResponse
{
    use TimestampableTrait, BlameableTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'feedbacks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;


    #[ORM\ManyToOne(targetEntity: Workout::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workout $workout = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $rating;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;



    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'coachFeedbacks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $coach = null;


    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $sentiment = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $keywords = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiSummary = null;

    public function __construct()
    {
    }

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

    public function getWorkout(): ?Workout
    {
        return $this->workout;
    }

    public function setWorkout(?Workout $workout): self
    {
        $this->workout = $workout;
        return $this;
    }

    public function getRating(): ?string
    {
        return $this->rating;
    }

    public function setRating(string $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
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

    public function getUserName(): string
    {
        return trim(($this->user?->getFirstname() ?? '') . ' ' . ($this->user?->getLastname() ?? ''));
    }

    public function getSentiment(): ?string
    {
        return $this->sentiment;
    }

    public function setSentiment(?string $sentiment): self
    {
        $this->sentiment = $sentiment;
        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): self
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getAiSummary(): ?string
    {
        return $this->aiSummary;
    }

    public function setAiSummary(?string $aiSummary): self
    {
        $this->aiSummary = $aiSummary;
        return $this;
    }
}
