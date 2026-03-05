<?php

namespace App\Entity;

use App\Repository\PasskeyCredentialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: PasskeyCredentialRepository::class)]
#[ORM\Table(name: 'passkey_credential')]
#[ORM\HasLifecycleCallbacks]
class PasskeyCredential
{
    use TimestampableTrait, BlameableTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'passkeyCredentials')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** Credential ID (stored as base64url for lookup). */
    #[ORM\Column(type: 'string', length: 512)]
    private ?string $credentialId = null;

    /** Public key PEM for signature verification. */
    #[ORM\Column(type: 'text')]
    private ?string $credentialPublicKey = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $signatureCounter = 0;



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

    public function getCredentialId(): ?string
    {
        return $this->credentialId;
    }

    public function setCredentialId(string $credentialId): self
    {
        $this->credentialId = $credentialId;
        return $this;
    }

    public function getCredentialPublicKey(): ?string
    {
        return $this->credentialPublicKey;
    }

    public function setCredentialPublicKey(string $credentialPublicKey): self
    {
        $this->credentialPublicKey = $credentialPublicKey;
        return $this;
    }

    public function getSignatureCounter(): int
    {
        return $this->signatureCounter;
    }

    public function setSignatureCounter(int $signatureCounter): self
    {
        $this->signatureCounter = $signatureCounter;
        return $this;
    }


}
