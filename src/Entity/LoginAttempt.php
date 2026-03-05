<?php

namespace App\Entity;

use App\Enum\Country;
use App\Enum\LoginStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
class LoginAttempt
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 45)]
    private ?string $ipAddress = null;

    #[ORM\Embedded(class: EmailAddress::class)]
    private EmailAddress $email;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $status = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $isp = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $timestamp = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'loginAttempts')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    public function __construct()
    {
        $this->email = new EmailAddress();
        $this->timestamp = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
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

    public function getStatus(): ?LoginStatus
    {
        return $this->status ? LoginStatus::from($this->status) : null;
    }

    public function setStatus(LoginStatus $status): self
    {
        $this->status = $status->value;
        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country ? Country::from($this->country) : null;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country?->value;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;
        return $this;
    }

    public function getIsp(): ?string
    {
        return $this->isp;
    }

    public function setIsp(?string $isp): self
    {
        $this->isp = $isp;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeImmutable
    {
        return $this->timestamp;
    }

    protected function setTimestamp(\DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
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
}
