<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\DoctorAvailabilityRepository;
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
    normalizationContext: ['groups' => ['doctor_availability:read']],
    denormalizationContext: ['groups' => ['doctor_availability:write']]
)]
#[ORM\Entity(repositoryClass: DoctorAvailabilityRepository::class)]
#[ORM\UniqueConstraint(name: 'doctor_date_unique', columns: ['doctor_id', 'available_date'])]
class DoctorAvailability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['doctor_availability:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['doctor_availability:read', 'doctor_availability:write'])]
    private ?Doctor $doctor = null;

    #[ORM\Column(name: 'available_date', type: 'date')]
    #[Groups(['doctor_availability:read', 'doctor_availability:write'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    #[Groups(['doctor_availability:read', 'doctor_availability:write'])]
    private bool $available = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDoctor(): ?Doctor
    {
        return $this->doctor;
    }

    public function setDoctor(Doctor $doctor): static
    {
        $this->doctor = $doctor;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): static
    {
        $this->available = $available;
        return $this;
    }
}


