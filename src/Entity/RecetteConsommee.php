<?php

namespace App\Entity;

use App\Repository\RecetteConsommeeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RecetteConsommeeRepository::class)]
class RecetteConsommee
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'recettesConsommees')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?RecetteNutritionnelle $recette = null;


    #[ORM\Column]
    private ?\DateTimeImmutable $dateConsommation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?int $kcal = null;

    #[ORM\Column]
    private ?int $proteins = null;

    public function __construct()
    {
        $this->dateConsommation = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getRecette(): ?RecetteNutritionnelle
    {
        return $this->recette;
    }

    public function setRecette(?RecetteNutritionnelle $recette): static
    {
        $this->recette = $recette;
        return $this;
    }

    public function getDateConsommation(): ?\DateTimeImmutable
    {
        return $this->dateConsommation;
    }

    protected function setDateConsommation(\DateTimeImmutable $dateConsommation): static
    {
        $this->dateConsommation = $dateConsommation;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getKcal(): ?int
    {
        return $this->kcal;
    }

    public function setKcal(int $kcal): static
    {
        $this->kcal = $kcal;
        return $this;
    }

    public function getProteins(): ?int
    {
        return $this->proteins;
    }

    public function setProteins(int $proteins): static
    {
        $this->proteins = $proteins;
        return $this;
    }
}
