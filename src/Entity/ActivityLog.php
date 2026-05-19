<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ActivityLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ],
    normalizationContext: ['groups' => ['activity_log:read']],
    denormalizationContext: ['groups' => ['activity_log:write']]
)]
#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['activity_log:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Groups(['activity_log:read', 'activity_log:write'])]
    private ?string $action = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['activity_log:read', 'activity_log:write'])]
    private ?string $details = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['activity_log:read', 'activity_log:write'])]
    private ?string $username = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['activity_log:read', 'activity_log:write'])]
    private ?string $role = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['activity_log:read', 'activity_log:write'])]
    private ?string $targetData = null;

    #[ORM\Column]
    #[Groups(['activity_log:read', 'activity_log:write'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getTargetData(): ?string
    {
        return $this->targetData;
    }

    public function setTargetData(?string $targetData): static
    {
        $this->targetData = $targetData;
        return $this;
    }
}


