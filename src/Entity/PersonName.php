<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class PersonName
{
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "First name is required")]
    #[Assert\Regex(pattern: "/^[a-zA-Z]+$/", message: "First name cannot contain numbers")]
    private ?string $firstname;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Last name is required")]
    #[Assert\Regex(pattern: "/^[a-zA-Z]+$/", message: "Last name cannot contain numbers")]
    private ?string $lastname;

    public function __construct(?string $firstname = null, ?string $lastname = null)
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstname, $this->lastname);
    }
}
