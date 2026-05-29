<?php

namespace App\Service;

use App\AppointmentStatus;
use App\Entity\Appointment;
use App\Repository\AppointmentPaymentRepository;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorRepository;
use App\Repository\ServiceRepository;
use DateTimeImmutable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AppointmentRealtimePayloadFactory
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly DoctorRepository $doctorRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly AppointmentPaymentRepository $paymentRepository,
        private readonly AppointmentRealtimeVersionStore $versionStore,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function staffDashboard(): array
    {
        $trackedStatuses = AppointmentStatus::TRACKED;
        $statusCounts = $this->appointmentRepository->countByStatuses($trackedStatuses);

        $upcoming = $this->appointmentRepository->createQueryBuilder('a')
            ->leftJoin('a.doctor', 'd')
            ->leftJoin('a.service', 's')
            ->addSelect('d', 's')
            ->andWhere('a.appointmentDate >= :today')
            ->setParameter('today', new DateTimeImmutable('today'))
            ->orderBy('a.appointmentDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return [
            'version' => $this->versionStore->getVersion(),
            'stats' => [
                'appointments' => $this->appointmentRepository->countAll(),
                'doctors' => $this->doctorRepository->count([]),
                'services' => $this->serviceRepository->count([]),
                'payments' => $this->paymentRepository->count([]),
            ],
            'trackedStatuses' => $trackedStatuses,
            'statusCounts' => $statusCounts,
            'upcoming' => array_map(fn (Appointment $a) => $this->serializeAppointmentRow($a), $upcoming),
        ];
    }

    public function staffList(bool $canManage): array
    {
        $appointments = $this->appointmentRepository->findAllOrderedForStaff();

        return [
            'version' => $this->versionStore->getVersion(),
            'appointments' => array_map(
                fn (Appointment $a) => $this->serializeStaffListRow($a, $canManage),
                $appointments,
            ),
        ];
    }

    public function patientBookings(string $patientIdentifier): array
    {
        $appointments = $this->appointmentRepository->findByPatientIdentifier($patientIdentifier);

        return [
            'version' => $this->versionStore->getVersion(),
            'appointments' => array_map(fn (Appointment $a) => $this->serializePatientBooking($a), $appointments),
        ];
    }

    private function serializeAppointmentRow(Appointment $appt): array
    {
        $status = (string) $appt->getStatus();
        $slug = strtolower(str_replace(' ', '-', $status));

        return [
            'id' => $appt->getId(),
            'dateLabel' => $appt->getAppointmentDate()?->format('M j, Y · g:i A') ?? '—',
            'patientName' => $appt->getPatientName() ?? '—',
            'doctorName' => $appt->getDoctor()?->getName() ?? '—',
            'serviceName' => $appt->getService()?->getName() ?? '—',
            'status' => $status,
            'statusSlug' => $slug,
            'statusBadgeClass' => \in_array($slug, ['confirmed', 'finished', 'cancelled'], true)
                ? 'badge-'.$slug
                : 'badge-generic',
        ];
    }

    private function serializeStaffListRow(Appointment $appointment, bool $canManage): array
    {
        $id = $appointment->getId();
        $status = (string) $appointment->getStatus();
        $statusLower = strtolower($status);

        $row = [
            'id' => $id,
            'patientName' => $appointment->getPatientName() ?? '',
            'appointmentDate' => $appointment->getAppointmentDate()?->format('Y-m-d H:i') ?? '',
            'status' => $status,
            'statusLower' => $statusLower,
            'notes' => $appointment->getNotes() ?? '',
            'showUrl' => $this->urlGenerator->generate('app_appointment_show', ['id' => $id]),
            'editUrl' => $this->urlGenerator->generate('app_appointment_edit', ['id' => $id]),
            'isPending' => AppointmentStatus::isPending($status),
        ];

        if ($canManage && AppointmentStatus::isPending($status)) {
            $row['confirmUrl'] = $this->urlGenerator->generate('app_appointment_confirm', ['id' => $id]);
            $row['rejectUrl'] = $this->urlGenerator->generate('app_appointment_reject', ['id' => $id]);
            $row['confirmToken'] = $this->csrfTokenManager->getToken('confirm'.$id)->getValue();
            $row['rejectToken'] = $this->csrfTokenManager->getToken('reject'.$id)->getValue();
        }

        return $row;
    }

    private function serializePatientBooking(Appointment $appt): array
    {
        $status = (string) $appt->getStatus();
        $slug = strtolower($status);

        return [
            'id' => $appt->getId(),
            'serviceName' => $appt->getService()?->getName() ?? 'Service',
            'doctorName' => $appt->getDoctor()?->getName() ?? '—',
            'dateLabel' => $appt->getAppointmentDate()?->format('M j, Y · g:i A') ?? '—',
            'notes' => $appt->getNotes(),
            'status' => $status,
            'statusSlug' => $slug,
            'directionsUrl' => $this->urlGenerator->generate('app_location_index'),
        ];
    }
}
