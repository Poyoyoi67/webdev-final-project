<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\AppointmentPaymentRepository;
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
    normalizationContext: ['groups' => ['appointment_payment:read']],
    denormalizationContext: ['groups' => ['appointment_payment:write']]
)]
#[ORM\Entity(repositoryClass: AppointmentPaymentRepository::class)]
class AppointmentPayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['appointment_payment:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'payment', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['appointment_payment:read', 'appointment_payment:write'])]
    private ?Appointment $appointment = null;

    #[ORM\Column]
    #[Groups(['appointment_payment:read', 'appointment_payment:write'])]
    private float $amount = 0;

    #[ORM\Column(nullable: true)]
    #[Groups(['appointment_payment:read', 'appointment_payment:write'])]
    private ?float $changeAmount = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['appointment_payment:read', 'appointment_payment:write'])]
    private ?string $paymentMethod = null;

    #[ORM\Column]
    #[Groups(['appointment_payment:read', 'appointment_payment:write'])]
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

