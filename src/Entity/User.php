<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity()]
#[UniqueEntity(fields: ['email'], message: 'This email address already exists.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "Please enter a valid email")]
    private ?string $email = null;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(message: "Password is required")]
    #[Assert\Length(min: 6, max: 50, minMessage: "Password must be at least 6 characters")]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "First name is required")]
    #[Assert\Regex(pattern: "/^[a-zA-Z]+$/", message: "First name cannot contain numbers")]
    private ?string $firstname = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Last name is required")]
    #[Assert\Regex(pattern: "/^[a-zA-Z]+$/", message: "Last name cannot contain numbers")]
    private ?string $lastname = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $accountStatus = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: EtatMental::class)]
    private Collection $etatMentals;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ProfilePhysique::class, cascade: ['remove'])]
    private Collection $profilesPhysiques;

    #[ORM\OneToMany(mappedBy: 'coach', targetEntity: RecetteNutritionnelle::class, cascade: ['remove'])]
    private Collection $recettes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: RecetteConsommee::class, orphanRemoval: true)]
    private Collection $recettesConsommees;

    public function __construct()
    {
        $this->etatMentals = new ArrayCollection();
        $this->profilesPhysiques = new ArrayCollection();
        $this->recettes = new ArrayCollection();
        $this->recettesConsommees = new ArrayCollection();
    }

    // -------------------------
    // Getters & Setters
    // -------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials()
    {
        // If you store temporary sensitive data, clear it here
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getAccountStatus(): ?string
    {
        return $this->accountStatus;
    }

    public function setAccountStatus(string $accountStatus): self
    {
        $this->accountStatus = $accountStatus;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    /**
     * @return Collection<int, EtatMental>
     */
    public function getEtatMentals(): Collection
    {
        return $this->etatMentals;
    }

    public function addEtatMental(EtatMental $etatMental): self
    {
        if (!$this->etatMentals->contains($etatMental)) {
            $this->etatMentals->add($etatMental);
            $etatMental->setUser($this);
        }

        return $this;
    }

    public function removeEtatMental(EtatMental $etatMental): self
    {
        if ($this->etatMentals->removeElement($etatMental)) {
            if ($etatMental->getUser() === $this) {
                $etatMental->setUser(null);
            }
        }

        return $this;
    }

    public function getProfilesPhysiques(): Collection
    {
        return $this->profilesPhysiques;
    }

    /**
     * Helper method to get the user's sports objectives from their physical profile.
     * @return Collection<int, ObjectifSportif>
     */
    public function getObjectifs(): Collection
    {
        $profile = $this->profilesPhysiques->first();
        return $profile ? $profile->getObjectifs() : new ArrayCollection();
    }

    /**
     * @return string[]
     */
    public function getObjectifNames(): array
    {
        return $this->getObjectifs()->map(fn($o) => $o->getName())->toArray();
    }

    /**
     * Checks if the user has at least one objective matching the given workout.
     */
    public function hasMatchingObjectif(Workout $workout): bool
    {
        $userNames = $this->getObjectifNames();
        foreach ($workout->getObjectifs() as $workoutObjectif) {
            if (in_array($workoutObjectif->getName(), $userNames)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Collection<int, RecetteNutritionnelle>
     */
    public function getRecettes(): Collection
    {
        return $this->recettes;
    }

    public function addRecette(RecetteNutritionnelle $recette): self
    {
        if (!$this->recettes->contains($recette)) {
            $this->recettes->add($recette);
            $recette->setCoach($this);
        }

        return $this;
    }

    public function removeRecette(RecetteNutritionnelle $recette): self
    {
        if ($this->recettes->removeElement($recette)) {
            // set the owning side to null (unless already changed)
            if ($recette->getCoach() === $this) {
                $recette->setCoach(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RecetteConsommee>
     */
    public function getRecettesConsommees(): Collection
    {
        return $this->recettesConsommees;
    }

    public function addRecettesConsommee(RecetteConsommee $recettesConsommee): static
    {
        if (!$this->recettesConsommees->contains($recettesConsommee)) {
            $this->recettesConsommees->add($recettesConsommee);
            $recettesConsommee->setUser($this);
        }

        return $this;
    }

    public function removeRecettesConsommee(RecetteConsommee $recettesConsommee): static
    {
        if ($this->recettesConsommees->removeElement($recettesConsommee)) {
            // set the owning side to null (unless already changed)
            if ($recettesConsommee->getUser() === $this) {
                $recettesConsommee->setUser(null);
            }
        }

        return $this;
    }
}
