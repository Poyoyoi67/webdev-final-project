<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\DoctorRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ],
    normalizationContext: ['groups' => ['doctor:read']],
    denormalizationContext: ['groups' => ['doctor:write']]
)]
#[ORM\Entity(repositoryClass: DoctorRepository::class)]
class Doctor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['doctor:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['doctor:read', 'doctor:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['doctor:read', 'doctor:write'])]
    private ?string $specialization = null;

    #[ORM\Column(length: 255)]
    #[Groups(['doctor:read', 'doctor:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Groups(['doctor:read', 'doctor:write'])]
    private ?string $contactNumber = null;

    #[ORM\Column(length: 255)]
    #[Groups(['doctor:read', 'doctor:write'])]
    private ?string $description = null;

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

    public function getSpecialization(): ?string
    {
        return $this->specialization;
    }

    public function setSpecialization(string $specialization): static
    {
        $this->specialization = $specialization;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getContactNumber(): ?string
    {
        return $this->contactNumber;
    }

    public function setContactNumber(string $contactNumber): static
    {
        $this->contactNumber = $contactNumber;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    #[Assert\Regex(
        pattern: "/^[0-9]+$/",
        message: "Contact number must contain only numbers."
    )]
    private ?string $ContactNumber = null;
}
