<?php

namespace App\Controller;

use App\AppointmentStatus;
use App\Entity\Appointment;
use App\Form\PatientBookingType;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorAvailabilityRepository;
use App\Repository\DoctorRepository;
use App\Repository\ServiceRepository;
use App\Service\ActivityLogger;
use App\Service\AppointmentRealtimeVersionStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/book')]
final class PatientBookingController extends AbstractController
{
    #[Route(name: 'app_book_index', methods: ['GET', 'POST'])]
    public function book(
        Request $request,
        ServiceRepository $serviceRepository,
        DoctorRepository $doctorRepository,
        DoctorAvailabilityRepository $availabilityRepository,
        EntityManagerInterface $entityManager,
        ActivityLogger $logger,
        AppointmentRealtimeVersionStore $realtimeVersionStore,
    ): Response {
        $this->denyPatientOnly();

        $selectedDate = $this->resolveBookingDate($request);
        $availableDoctors = $this->loadAvailableDoctors($doctorRepository, $availabilityRepository, $selectedDate);

        $appointment = new Appointment();
        $preselectedServiceId = $request->query->getInt('service');
        if ($preselectedServiceId > 0) {
            $service = $serviceRepository->find($preselectedServiceId);
            if ($service) {
                $appointment->setService($service);
            }
        }

        $form = $this->createForm(PatientBookingType::class, $appointment, [
            'available_doctors' => $availableDoctors,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $patientId = $user?->getUserIdentifier() ?? 'Patient';

            if (!$this->isDoctorAvailableOnDate($availabilityRepository, $appointment->getDoctor(), $appointment->getAppointmentDate())) {
                $this->addFlash('error', 'The selected doctor is not available on that date. Please pick another date or doctor.');
            } elseif ($appointment->getAppointmentDate() < new \DateTime()) {
                $this->addFlash('error', 'Please choose a future date and time.');
            } else {
                $appointment->setPatientName($patientId);
                $appointment->setStatus(AppointmentStatus::PENDING);

                $entityManager->persist($appointment);
                $entityManager->flush();
                $realtimeVersionStore->bump();

                $logger->log(
                    'appointment_requested',
                    sprintf('Booking request #%d from %s', $appointment->getId(), $patientId),
                    sprintf(
                        'Appointment ID: %d, Service: %s, Doctor: %s, Date: %s',
                        $appointment->getId(),
                        $appointment->getService()?->getName() ?? 'N/A',
                        $appointment->getDoctor()?->getName() ?? 'N/A',
                        $appointment->getAppointmentDate()?->format('Y-m-d H:i') ?? 'N/A'
                    )
                );

                $this->addFlash('success', 'Your booking request was submitted. Staff will confirm or reject it shortly.');

                return $this->redirectToRoute('app_book_my');
            }
        }

        return $this->render('booking/index.html.twig', [
            'form' => $form,
            'services' => $serviceRepository->findBy([], ['name' => 'ASC']),
            'selectedDate' => $selectedDate,
            'availableDoctorCount' => \count($availableDoctors),
        ]);
    }

    #[Route('/my', name: 'app_book_my', methods: ['GET'])]
    public function myBookings(AppointmentRepository $appointmentRepository): Response
    {
        $this->denyPatientOnly();

        $patientId = $this->getUser()?->getUserIdentifier() ?? '';

        return $this->render('booking/my.html.twig', [
            'appointments' => $appointmentRepository->findByPatientIdentifier($patientId),
        ]);
    }

    #[Route('/available-doctors', name: 'app_book_available_doctors', methods: ['GET'])]
    public function availableDoctors(
        Request $request,
        DoctorRepository $doctorRepository,
        DoctorAvailabilityRepository $availabilityRepository,
    ): JsonResponse {
        $this->denyPatientOnly();

        $date = $this->parseBookingDate($request->query->get('date'));
        $doctors = $this->loadAvailableDoctors($doctorRepository, $availabilityRepository, $date);

        $payload = array_map(static fn ($doctor) => [
            'id' => $doctor->getId(),
            'name' => $doctor->getName(),
            'specialization' => $doctor->getSpecialization(),
        ], $doctors);

        return $this->json([
            'date' => $date->format('Y-m-d'),
            'doctors' => $payload,
        ]);
    }

    private function denyPatientOnly(): void
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('This page is for patient accounts only.');
        }
    }

    private function resolveBookingDate(Request $request): \DateTime
    {
        $submitted = $request->request->all('patient_booking');
        if (!empty($submitted['appointmentDate'])) {
            try {
                return new \DateTime($submitted['appointmentDate']);
            } catch (\Exception) {
            }
        }

        return $this->parseBookingDate($request->query->get('date'));
    }

    private function parseBookingDate(?string $value): \DateTime
    {
        if ($value) {
            try {
                return new \DateTime($value);
            } catch (\Exception) {
            }
        }

        return new \DateTime('today');
    }

    /**
     * @return \App\Entity\Doctor[]
     */
    private function loadAvailableDoctors(
        DoctorRepository $doctorRepository,
        DoctorAvailabilityRepository $availabilityRepository,
        \DateTimeInterface $date,
    ): array {
        $ids = $availabilityRepository->findAvailableDoctorIdsForDate($date);
        if ($ids === []) {
            return [];
        }

        return $doctorRepository->findBy(['id' => $ids], ['name' => 'ASC']);
    }

    private function isDoctorAvailableOnDate(
        DoctorAvailabilityRepository $availabilityRepository,
        ?\App\Entity\Doctor $doctor,
        ?\DateTimeInterface $appointmentDate,
    ): bool {
        if (!$doctor || !$appointmentDate) {
            return false;
        }

        $ids = $availabilityRepository->findAvailableDoctorIdsForDate($appointmentDate);

        return \in_array($doctor->getId(), $ids, true);
    }
}
