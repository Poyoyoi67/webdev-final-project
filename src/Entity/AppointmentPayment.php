<?php

namespace App\Entity;

use App\Repository\AppointmentPaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentPaymentRepository::class)]
class AppointmentPayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'payment', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Appointment $appointment = null;

    #[ORM\Column]
    private float $amount = 0;

    #[ORM\Column(nullable: true)]
    private ?float $changeAmount = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $paidAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(Appointment $appointment): static
    {
        $this->appointment = $appointment;
        if ($appointment->getPayment() !== $this) {
            $appointment->setPayment($this);
        }
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

    public function getChangeAmount(): ?float
    {
        return $this->changeAmount;
    }

    public function setChangeAmount(?float $changeAmount): static
    {
        $this->changeAmount = $changeAmount;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }
}

