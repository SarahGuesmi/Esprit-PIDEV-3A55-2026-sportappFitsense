<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email.email'], message: 'This email address already exists.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface, \Serializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Embedded(class: EmailAddress::class)]
    private EmailAddress $email;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(message: "Password is required")]
    #[Assert\Length(min: 6, minMessage: "Password must be at least 6 characters")]
    #[Ignore]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Embedded(class: PersonName::class)]
    private PersonName $name;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $accountStatus = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: EtatMental::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $etatMentals;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ProfilePhysique::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $profilesPhysiques;

    #[ORM\OneToMany(mappedBy: 'coach', targetEntity: RecetteNutritionnelle::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $recettes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: RecetteConsommee::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $recettesConsommees;


    #[ORM\ManyToMany(targetEntity: RecetteNutritionnelle::class, mappedBy: 'favoritedBy')]
    private Collection $favoriteRecipes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PasskeyCredential::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $passkeyCredentials;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Ignore]
    private ?string $googleAuthenticatorSecret = null;

    #[ORM\Embedded(class: PhoneNumber::class)]
    private PhoneNumber $phone;

    /** Stored filename in uploads/profiles/ (user-uploaded photo). */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    private ?string $username = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LoginAttempt::class, cascade: ['persist'])]
    private Collection $loginAttempts;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Recommendation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userRecommendations;

    #[ORM\OneToMany(mappedBy: 'coach', targetEntity: Recommendation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $coachRecommendations;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserExerciseProgress::class, cascade: ['persist'])]
    private Collection $exerciseProgress;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ResetPasswordRequest::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $resetPasswordRequests;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: FeedbackResponse::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $feedbacks;

    #[ORM\OneToMany(mappedBy: 'coach', targetEntity: FeedbackResponse::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $coachFeedbacks;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: DailyNutrition::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $dailyNutritions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Questionnaire::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $questionnaires;


    public function __construct()
    {
        $this->email = new EmailAddress();
        $this->name = new PersonName();
        $this->phone = new PhoneNumber();
        $this->etatMentals = new ArrayCollection();
        $this->profilesPhysiques = new ArrayCollection();
        $this->recettes = new ArrayCollection();
        $this->recettesConsommees = new ArrayCollection();
        $this->favoriteRecipes = new ArrayCollection();
        $this->passkeyCredentials = new ArrayCollection();
        $this->loginAttempts = new ArrayCollection();
        $this->userRecommendations = new ArrayCollection();
        $this->coachRecommendations = new ArrayCollection();
        $this->exerciseProgress = new ArrayCollection();
        $this->resetPasswordRequests = new ArrayCollection();
        $this->feedbacks = new ArrayCollection();
        $this->coachFeedbacks = new ArrayCollection();
        $this->dailyNutritions = new ArrayCollection();
        $this->questionnaires = new ArrayCollection();
    }


    // -------------------------
    // Getters & Setters
    // -------------------------

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email->getEmail();
    }

    public function setEmail(string $email): self
    {
        $this->email = new EmailAddress($email);
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->getEmail();
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

    public function setPassword(#[\SensitiveParameter] string $password): self
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
        return $this->name->getFirstname();
    }

    public function setFirstname(string $firstname): self
    {
        $this->name = new PersonName($firstname, $this->getLastname());
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->name->getLastname();
    }

    public function setLastname(string $lastname): self
    {
        $this->name = new PersonName($this->getFirstname(), $lastname);
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

    /**
     * @return Collection<int, RecetteNutritionnelle>
     */
    public function getFavoriteRecipes(): Collection
    {
        return $this->favoriteRecipes;
    }

    public function addFavoriteRecipe(RecetteNutritionnelle $recipe): self
    {
        if (!$this->favoriteRecipes->contains($recipe)) {
            $this->favoriteRecipes->add($recipe);
            $recipe->addFavoritedBy($this);
        }

        return $this;
    }

    public function removeFavoriteRecipe(RecetteNutritionnelle $recipe): self
    {
        if ($this->favoriteRecipes->removeElement($recipe)) {
            $recipe->removeFavoritedBy($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, PasskeyCredential>
     */
    public function getPasskeyCredentials(): Collection
    {
        return $this->passkeyCredentials;
    }

    /**
     * @return Collection<int, LoginAttempt>
     */
    public function getLoginAttempts(): Collection
    {
        return $this->loginAttempts;
    }

    // --------------- Two-Factor (TOTP / Google Authenticator) ---------------

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return $this->googleAuthenticatorSecret !== null && $this->googleAuthenticatorSecret !== '';
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return (string) $this->getEmail();
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(#[\SensitiveParameter] ?string $googleAuthenticatorSecret): void
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    }

    // --------------- Profile (phone, photo) ---------------

    public function getPhone(): ?string
    {
        return $this->phone->getNumber();
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = new PhoneNumber($phone);
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    /**
     * Alias for getPhoto() to ensure compatibility.
     */
    public function getAvatar(): ?string
    {
        return $this->getPhoto();
    }

    /**
     * Path for displaying profile photo. Relative to web root, e.g. "uploads/profiles/user_1_abc.jpg".
     */
    public function getPhotoPath(): ?string
    {
        if ($this->photo === null || $this->photo === '') {
            return null;
        }

        // If it's a full URL (like DiceBear), return it directly
        if (str_starts_with($this->photo, 'http')) {
            return $this->photo;
        }

        return 'uploads/profiles/' . $this->photo;
    }

    /**
     * Alias for getPhotoPath() to ensure compatibility.
     */
    public function getAvatarPath(): ?string
    {
        return $this->getPhotoPath();
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email->getEmail(),
            'password' => $this->password,
            'roles' => $this->roles,
            'firstname' => $this->name->getFirstname(),
            'lastname' => $this->name->getLastname(),
            'accountStatus' => $this->accountStatus,
            'photo' => $this->photo,
            'phone' => $this->phone->getNumber(),
            'googleAuthenticatorSecret' => $this->googleAuthenticatorSecret,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->email = new EmailAddress($data['email'] ?? null);
        $this->password = $data['password'] ?? null;
        $this->roles = $data['roles'] ?? [];
        $this->name = new PersonName($data['firstname'] ?? null, $data['lastname'] ?? null);
        $this->accountStatus = $data['accountStatus'] ?? null;
        $this->photo = $data['photo'] ?? null;
        $this->phone = new PhoneNumber($data['phone'] ?? null);
        $this->googleAuthenticatorSecret = $data['googleAuthenticatorSecret'] ?? null;
    }

    public function serialize()
    {
        return serialize($this->__serialize());
    }

    public function unserialize($data)
    {
        $this->__unserialize(unserialize($data));
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }
}
