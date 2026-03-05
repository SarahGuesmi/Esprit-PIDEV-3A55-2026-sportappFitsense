<?php

namespace App\Entity;

use App\Repository\ChatMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ChatMessage
{
    use TimestampableTrait, BlameableTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;



    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $sender = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $receiver = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    /** When the sender chose "delete conversation" (hidden for sender). */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedBySenderAt = null;

    /** When the receiver chose "delete conversation" (hidden for receiver). */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedByReceiverAt = null;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $isDeleted = false;

    public function __construct()
    {
        $this->isDeleted = false;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }



    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): static
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    protected function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getDeletedBySenderAt(): ?\DateTimeImmutable
    {
        return $this->deletedBySenderAt;
    }

    protected function setDeletedBySenderAt(?\DateTimeImmutable $deletedBySenderAt): static
    {
        $this->deletedBySenderAt = $deletedBySenderAt;
        return $this;
    }

    public function getDeletedByReceiverAt(): ?\DateTimeImmutable
    {
        return $this->deletedByReceiverAt;
    }

    protected function setDeletedByReceiverAt(?\DateTimeImmutable $deletedByReceiverAt): static
    {
        $this->deletedByReceiverAt = $deletedByReceiverAt;
        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }
}
