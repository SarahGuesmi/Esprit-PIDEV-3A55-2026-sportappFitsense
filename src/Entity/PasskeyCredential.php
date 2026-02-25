<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'passkey_credential')]
class PasskeyCredential
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

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

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
