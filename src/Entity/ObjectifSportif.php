<?php
namespace App\Entity;

use App\Repository\ObjectifSportifRepository;
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

    // Getters & Setters
    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getProfilePhysique(): ?ProfilePhysique { return $this->profilePhysique; }
    public function setProfilePhysique(?ProfilePhysique $profile): self { $this->profilePhysique = $profile; return $this; }
}