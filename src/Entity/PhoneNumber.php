<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class PhoneNumber
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $number;

    public function __construct(?string $number = null)
    {
        $this->number = $number;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function __toString(): string
    {
        return (string) $this->number;
    }
}
