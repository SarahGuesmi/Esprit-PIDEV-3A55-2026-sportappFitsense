<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class EmailAddress
{
    #[ORM\Column(type: 'string', length: 180)]
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "Please enter a valid email")]
    private ?string $email;

    public function __construct(?string $email = null)
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function __toString(): string
    {
        return (string) $this->email;
    }
}
