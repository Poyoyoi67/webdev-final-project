<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\AppointmentPayment;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $patientName = null;

    #[ORM\ManyToOne]
    private ?Doctor $doctor = null;

    #[ORM\ManyToOne]
    private ?Service $service = null;

    #[ORM\Column]
    private ?\DateTime $appointmentDate = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToOne(mappedBy: 'appointment', cascade: ['remove'])]
    private ?AppointmentPayment $payment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatientName(): ?string
    {
        return $this->patientName;
    }

    public function setPatientName(string $patientName): static
    {
        $this->patientName = $patientName;

        return $this;
    }

    public function getDoctor(): ?Doctor
    {
        return $this->doctor;
    }

    public function setDoctor(?Doctor $doctor): static
    {
        $this->doctor = $doctor;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getAppointmentDate(): ?\DateTime
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(\DateTime $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getPayment(): ?AppointmentPayment
    {
        return $this->payment;
    }

    public function setPayment(?AppointmentPayment $payment): static
    {
        $this->payment = $payment;
        return $this;
    }
}
