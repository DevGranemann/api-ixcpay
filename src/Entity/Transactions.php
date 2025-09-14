<?php

namespace App\Entity;

use App\Repository\TransactionsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionsRepository::class)]
class Transactions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserAccounts::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserAccounts $fromUser = null;

    #[ORM\ManyToOne(targetEntity: UserAccounts::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserAccounts $toUser = null;

    #[ORM\Column(type: 'float')]
    private float $amount;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromUser(): ?UserAccounts
    {
        return $this->fromUser;
    }

    public function setFromUser(UserAccounts $fromUser): static
    {
        $this->fromUser = $fromUser;
        return $this;
    }

    public function getToUser(): ?UserAccounts
    {
        return $this->toUser;
    }

    public function setToUser(UserAccounts $toUser): static
    {
        $this->toUser = $toUser;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
