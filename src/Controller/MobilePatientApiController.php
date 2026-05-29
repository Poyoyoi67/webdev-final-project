<?php

namespace App\Controller;

use App\AppointmentStatus;
use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorAvailabilityRepository;
use App\Repository\DoctorRepository;
use App\Repository\ServiceRepository;
use App\Service\AppointmentRealtimeVersionStore;
use App\Service\FcmNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mobile/patient')]
final class MobilePatientApiController extends AbstractController
{
    #[Route('/home', name: 'api_mobile_patient_home', methods: ['GET'])]
    public function home(
        ServiceRepository $serviceRepository,
        DoctorRepository $doctorRepository,
        DoctorAvailabilityRepository $availabilityRepository,
    ): JsonResponse {
        $this->denyStaffArea();

        $today = new \DateTimeImmutable('today');
        $availableDoctorIds = $availabilityRepository->findAvailableDoctorIdsForDate($today);

        return $this->json([
            'services' => array_map($this->serializeService(...), $serviceRepository->findBy([], ['name' => 'ASC'])),
            'doctors' => array_map(
                fn ($d) => $this->serializeDoctor($d, \in_array($d->getId(), $availableDoctorIds, true)),
                $doctorRepository->findBy([], ['name' => 'ASC'])
            ),
            'availabilityDate' => $today->format('Y-m-d'),
            'availableDoctorIds' => $availableDoctorIds,
        ]);
    }

    #[Route('/services/{id}', name: 'api_mobile_patient_service', methods: ['GET'])]
    public function service(int $id, ServiceRepository $serviceRepository): JsonResponse
    {
        $this->denyStaffArea();
        $service = $serviceRepository->find($id);
        if (!$service) {
            return $this->json(['message' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeService($service));
    }

    #[Route('/doctors/{id}', name: 'api_mobile_patient_doctor', methods: ['GET'])]
    public function doctor(
        int $id,
        DoctorRepository $doctorRepository,
        DoctorAvailabilityRepository $availabilityRepository,
    ): JsonResponse {
        $this->denyStaffArea();
        $doctor = $doctorRepository->find($id);
        if (!$doctor) {
            return $this->json(['message' => 'Doctor not found'], Response::HTTP_NOT_FOUND);
        }

        $today = new \DateTimeImmutable('today');
        $availableIds = $availabilityRepository->findAvailableDoctorIdsForDate($today);

        return $this->json($this->serializeDoctor($doctor, \in_array($doctor->getId(), $availableIds, true)));
    }

    #[Route('/available-doctors', name: 'api_mobile_patient_available_doctors', methods: ['GET'])]
    public function availableDoctors(
        Request $request,
        DoctorRepository $doctorRepository,
        DoctorAvailabilityRepository $availabilityRepository,
    ): JsonResponse {
        $this->denyStaffArea();

        $dateParam = $request->query->get('date', (new \DateTime())->format('Y-m-d'));
        try {
            $date = new \DateTime($dateParam);
        } catch (\Exception) {
            return $this->json(['message' => 'Invalid date'], Response::HTTP_BAD_REQUEST);
        }

        $ids = $availabilityRepository->findAvailableDoctorIdsForDate($date);
        $doctors = $ids === [] ? [] : $doctorRepository->findBy(['id' => $ids], ['name' => 'ASC']);

        return $this->json([
            'date' => $date->format('Y-m-d'),
            'doctors' => array_map(fn ($d) => $this->serializeDoctor($d, true), $doctors),
        ]);
    }

    #[Route('/appointments/version', name: 'api_mobile_patient_appointments_version', methods: ['GET'])]
    public function appointmentsVersion(AppointmentRealtimeVersionStore $versionStore): JsonResponse
    {
        $this->denyStaffArea();

        return $this->json(['version' => $versionStore->getVersion()]);
    }

    #[Route('/appointments', name: 'api_mobile_patient_appointments_list', methods: ['GET'])]
    public function appointments(
        AppointmentRepository $appointmentRepository,
        AppointmentRealtimeVersionStore $versionStore,
    ): JsonResponse {
        $this->denyStaffArea();
        $patientId = $this->getUser()?->getUserIdentifier() ?? '';

        $items = $appointmentRepository->findByPatientIdentifier($patientId);

        return $this->json([
            'version' => $versionStore->getVersion(),
            'appointments' => array_map($this->serializeAppointment(...), $items),
        ]);
    }

    #[Route('/appointments', name: 'api_mobile_patient_appointments_create', methods: ['POST'])]
    public function createAppointment(
        Request $request,
        EntityManagerInterface $entityManager,
        ServiceRepository $serviceRepository,
        DoctorRepository $doctorRepository,
        DoctorAvailabilityRepository $availabilityRepository,
        FcmNotificationService $fcmNotificationService,
        AppointmentRealtimeVersionStore $realtimeVersionStore,
    ): JsonResponse {
        $this->denyStaffArea();

        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return $this->json(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $serviceId = (int) ($payload['serviceId'] ?? 0);
        $doctorId = (int) ($payload['doctorId'] ?? 0);
        $dateRaw = $payload['appointmentDate'] ?? null;
        $notes = isset($payload['notes']) ? (string) $payload['notes'] : null;

        $service = $serviceRepository->find($serviceId);
        $doctor = $doctorRepository->find($doctorId);

        if (!$service || !$doctor || !$dateRaw) {
            return $this->json(['message' => 'Service, doctor, and appointmentDate are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $appointmentDate = new \DateTime($dateRaw);
        } catch (\Exception) {
            return $this->json(['message' => 'Invalid appointmentDate'], Response::HTTP_BAD_REQUEST);
        }

        if ($appointmentDate < new \DateTime()) {
            return $this->json(['message' => 'Appointment must be in the future'], Response::HTTP_BAD_REQUEST);
        }

        $availableIds = $availabilityRepository->findAvailableDoctorIdsForDate($appointmentDate);
        if (!\in_array($doctor->getId(), $availableIds, true)) {
            return $this->json(['message' => 'Doctor is not available on the selected date'], Response::HTTP_BAD_REQUEST);
        }

        $appointment = new Appointment();
        $appointment->setPatientName($this->getUser()?->getUserIdentifier() ?? 'Patient');
        $appointment->setService($service);
        $appointment->setDoctor($doctor);
        $appointment->setAppointmentDate($appointmentDate);
        $appointment->setStatus(AppointmentStatus::PENDING);
        $appointment->setNotes($notes ?: null);

        $entityManager->persist($appointment);
        $entityManager->flush();
        $realtimeVersionStore->bump();

        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $serviceName = $service->getName() ?? 'your service';
            $fcmNotificationService->notifyUser(
                $user,
                'Booking submitted',
                sprintf('Your request for %s is pending staff approval.', $serviceName),
                [
                    'type' => 'booking_created',
                    'appointmentId' => (string) $appointment->getId(),
                    'status' => AppointmentStatus::PENDING,
                ],
            );
        }

        return $this->json([
            'message' => 'Booking request submitted',
            'appointment' => $this->serializeAppointment($appointment),
        ], Response::HTTP_CREATED);
    }

    #[Route('/clinic', name: 'api_mobile_patient_clinic', methods: ['GET'])]
    public function clinic(AppointmentRepository $appointmentRepository): JsonResponse
    {
        $this->denyStaffArea();
        $patientId = $this->getUser()?->getUserIdentifier() ?? '';

        return $this->json([
            'name' => 'Health Care Clinic',
            'address' => '123 Main Street, Manila, Philippines',
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'canUseDirections' => $appointmentRepository->hasConfirmedBookingForPatient($patientId),
        ]);
    }

    private function denyStaffArea(): void
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Patient mobile API only.');
        }
    }

    private function serializeService(\App\Entity\Service $service): array
    {
        return [
            'id' => $service->getId(),
            'name' => $service->getName(),
            'price' => $service->getPrice(),
            'doctor' => $service->getDoctor(),
            'text' => $service->getText(),
            'duration' => $service->getDuration(),
        ];
    }

    private function serializeDoctor(\App\Entity\Doctor $doctor, bool $availableToday): array
    {
        return [
            'id' => $doctor->getId(),
            'name' => $doctor->getName(),
            'specialization' => $doctor->getSpecialization(),
            'email' => $doctor->getEmail(),
            'contactNumber' => $doctor->getContactNumber(),
            'description' => $doctor->getDescription(),
            'availableToday' => $availableToday,
        ];
    }

    private function serializeAppointment(Appointment $appointment): array
    {
        $doctor = $appointment->getDoctor();
        $service = $appointment->getService();

        return [
            'id' => $appointment->getId(),
            'patientName' => $appointment->getPatientName(),
            'status' => $appointment->getStatus(),
            'notes' => $appointment->getNotes(),
            'appointmentDate' => $appointment->getAppointmentDate()?->format(\DateTimeInterface::ATOM),
            'doctor' => $doctor ? [
                'id' => $doctor->getId(),
                'name' => $doctor->getName(),
                'specialization' => $doctor->getSpecialization(),
            ] : null,
            'service' => $service ? [
                'id' => $service->getId(),
                'name' => $service->getName(),
                'price' => $service->getPrice(),
                'duration' => $service->getDuration(),
            ] : null,
        ];
    }
}
