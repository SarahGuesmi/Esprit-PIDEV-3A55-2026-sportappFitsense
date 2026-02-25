<?php
namespace App\Entity;

use App\Repository\ProfilePhysiqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ProfilePhysique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"float", nullable:true)]
    private ?float $weight = null;

    #[ORM\Column(type:"float", nullable:true)]
    private ?float $height = null;

    #[ORM\Column(type:"string", length:10, nullable:true)]
    private ?string $gender = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'profilesPhysiques')]
    #[ORM\JoinColumn(nullable:false)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy:"profilePhysique", targetEntity:ObjectifSportif::class, cascade:["persist", "remove"])]
    private Collection $objectifs;

    public function __construct()
    {
        $this->objectifs = new ArrayCollection();
    }

    // Getters & Setters
    public function getId(): ?int { return $this->id; }

    public function getWeight(): ?float { return $this->weight; }
    public function setWeight(?float $weight): self { $this->weight = $weight; return $this; }

    public function getHeight(): ?float { return $this->height; }
    public function setHeight(?float $height): self { $this->height = $height; return $this; }

    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): self { $this->gender = $gender; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    /** @return Collection|ObjectifSportif[] */
    public function getObjectifs(): Collection { return $this->objectifs; }

    public function addObjectif(ObjectifSportif $objectif): self
    {
        if (!$this->objectifs->contains($objectif)) {
            $this->objectifs[] = $objectif;
            $objectif->setProfilePhysique($this);
        }
        return $this;
    }

    public function removeObjectif(ObjectifSportif $objectif): self
    {
        if ($this->objectifs->removeElement($objectif)) {
            if ($objectif->getProfilePhysique() === $this) {
                $objectif->setProfilePhysique(null);
            }
        }
        return $this;
    }
}