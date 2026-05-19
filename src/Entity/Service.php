<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ServiceRepository;
use Doctrine\DBAL\Types\Types;
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
    normalizationContext: ['groups' => ['service:read']],
    denormalizationContext: ['groups' => ['service:write']]
)]
#[ORM\Entity(repositoryClass: ServiceRepository::class)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['service:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['service:read', 'service:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    #[Groups(['service:read', 'service:write'])]
    private ?string $price = null;

    #[ORM\Column(length: 255)]
    #[Groups(['service:read', 'service:write'])]
    private ?string $doctor = null;

    #[ORM\Column(length: 255)]
    #[Groups(['service:read', 'service:write'])]
    private ?string $text = null;

    #[ORM\Column]
    #[Groups(['service:read', 'service:write'])]
    private ?int $duration = null;

    #[ORM\Column(length: 255)]

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDoctor(): ?string
    {
        return $this->doctor;
    }

    public function setDoctor(string $doctor): static
    {
        $this->doctor = $doctor;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }
}
