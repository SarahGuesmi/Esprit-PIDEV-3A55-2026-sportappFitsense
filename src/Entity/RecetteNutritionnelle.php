<?php

namespace App\Entity;

use App\Repository\RecetteNutritionnelleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecetteNutritionnelleRepository::class)]
class RecetteNutritionnelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Title is required.")]
    #[Assert\Length(min: 3, max: 120)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "Description is required.")]
    #[Assert\Length(min: 10)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $kcal = null;

    #[ORM\Column(nullable: true)]
    private ?int $proteins = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(message: "Meal type is required.")]
    #[Assert\Choice(choices: ["BREAKFAST", "LUNCH", "DINNER", "SNACK"])]
    private ?string $typeMeal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "Ingredients are required.")]
    #[Assert\Length(min: 5)]
    private ?string $ingredients = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "Preparation is required.")]
    #[Assert\Length(min: 5)]
    private ?string $preparation = null;

    // ✅ image filename
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'recettes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $coach = null;

    // ✅ Favorites: pivot table recipe_favorites(user_id, recette_nutritionnelle_id)
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'favoriteRecipes')]
    #[ORM\JoinTable(name: 'recipe_favorites')]
    private Collection $favoritedBy;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Assert\Count(min: 1, minMessage: "Please select at least one objective.")]
    private array $objectifs = [];

    public function __construct()
    {
        $this->favoritedBy = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->objectifs = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getKcal(): ?int
    {
        return $this->kcal;
    }

    public function setKcal(?int $kcal): self
    {
        $this->kcal = $kcal;
        return $this;
    }

    public function getProteins(): ?int
    {
        return $this->proteins;
    }

    public function setProteins(?int $proteins): self
    {
        $this->proteins = $proteins;
        return $this;
    }

    public function getTypeMeal(): ?string
    {
        return $this->typeMeal;
    }

    public function setTypeMeal(?string $typeMeal): self
    {
        $this->typeMeal = $typeMeal;
        return $this;
    }

    public function getIngredients(): ?string
    {
        return $this->ingredients;
    }

    public function setIngredients(?string $ingredients): self
    {
        $this->ingredients = $ingredients;
        return $this;
    }

    public function getPreparation(): ?string
    {
        return $this->preparation;
    }

    public function setPreparation(?string $preparation): self
    {
        $this->preparation = $preparation;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
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

    public function getFavoritedBy(): Collection
    {
        return $this->favoritedBy;
    }

    public function addFavoritedBy(User $user): self
    {
        if (!$this->favoritedBy->contains($user)) {
            $this->favoritedBy->add($user);
        }
        return $this;
    }

    public function removeFavoritedBy(User $user): self
    {
        $this->favoritedBy->removeElement($user);
        return $this;
    }

    public function getObjectifs(): array
    {
        return $this->objectifs ?? [];
    }

    public function setObjectifs(?array $objectifs): self
    {
        $this->objectifs = $objectifs ?? [];
        return $this;
    }
}