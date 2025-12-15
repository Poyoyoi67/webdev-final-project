<?php

namespace App\Entity;

use App\Repository\DoctorAvailabilityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctorAvailabilityRepository::class)]
#[ORM\UniqueConstraint(name: 'doctor_date_unique', columns: ['doctor_id', 'available_date'])]
class DoctorAvailability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Doctor $doctor = null;

    #[ORM\Column(name: 'available_date', type: 'date')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
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


