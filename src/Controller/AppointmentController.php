<?php

namespace App\Controller;

use App\AppointmentStatus;
use App\Entity\Appointment;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use App\Entity\AppointmentPayment;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use App\Service\FcmNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/appointment')]
final class AppointmentController extends AbstractController
{
    #[Route(name: 'app_appointment_index', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_book_my');
        }

        return $this->render('appointment/index.html.twig', [
            'appointments' => $appointmentRepository->findAllOrderedForStaff(),
        ]);
    }

    #[Route('/new', name: 'app_appointment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyCrudForRegularUsers();

        $appointment = new Appointment();
        $appointment->setStatus(AppointmentStatus::CONFIRMED);
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($appointment);
            $entityManager->flush();
            $targetData = sprintf('Appointment ID: %d, Patient: %s, Date: %s, Status: %s, Doctor: %s', 
                $appointment->getId(), 
                $appointment->getPatientName(),
                $appointment->getAppointmentDate()?->format('Y-m-d H:i'),
                $appointment->getStatus(),
                $appointment->getDoctor()?->getName() ?? 'N/A'
            );
            $logger->log('appointment_created', sprintf('Appointment #%d created for %s', $appointment->getId(), $appointment->getPatientName()), $targetData);

            // Auto-create a transaction record for the newly booked appointment
            $payment = new AppointmentPayment();
            $payment->setAppointment($appointment);
            $payment->setAmount(0);
            $payment->setChangeAmount(null);
            $payment->setPaymentMethod('pending');
            $payment->setPaidAt(new \DateTimeImmutable());
            $entityManager->persist($payment);
            $entityManager->flush();

            return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appointment/new.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appointment_show', methods: ['GET'])]
    public function show(Appointment $appointment): Response
    {
        return $this->render('appointment/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_appointment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyCrudForRegularUsers();

        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $targetData = sprintf('Appointment ID: %d, Patient: %s, Date: %s, Status: %s, Doctor: %s', 
                $appointment->getId(), 
                $appointment->getPatientName(),
                $appointment->getAppointmentDate()?->format('Y-m-d H:i'),
                $appointment->getStatus(),
                $appointment->getDoctor()?->getName() ?? 'N/A'
            );
            $logger->log('appointment_updated', sprintf('Appointment #%d updated', $appointment->getId()), $targetData);

            return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appointment/edit.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/confirm', name: 'app_appointment_confirm', methods: ['POST'])]
    public function confirm(
        Request $request,
        Appointment $appointment,
        EntityManagerInterface $entityManager,
        ActivityLogger $logger,
        UserRepository $userRepository,
        FcmNotificationService $fcmNotificationService,
    ): Response {
        $this->denyCrudForRegularUsers();

        if ($this->isCsrfTokenValid('confirm'.$appointment->getId(), $request->getPayload()->getString('_token'))) {
            $appointment->setStatus(AppointmentStatus::CONFIRMED);
            $entityManager->flush();
            $logger->log(
                'appointment_confirmed',
                sprintf('Appointment #%d confirmed', $appointment->getId()),
                sprintf('Patient: %s', $appointment->getPatientName())
            );

            $patient = $userRepository->findOneBy(['email' => $appointment->getPatientName()]);
            if ($patient !== null) {
                $serviceName = $appointment->getService()?->getName() ?? 'your appointment';
                $dateLabel = $appointment->getAppointmentDate()?->format('M j, Y g:i A') ?? '';
                $fcmNotificationService->notifyUser(
                    $patient,
                    'Appointment confirmed',
                    sprintf('%s is confirmed for %s.', $serviceName, $dateLabel),
                    [
                        'type' => 'booking_confirmed',
                        'appointmentId' => (string) $appointment->getId(),
                        'status' => AppointmentStatus::CONFIRMED,
                    ],
                );
            }

            $this->addFlash('success', 'Appointment confirmed.');
        }

        return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reject', name: 'app_appointment_reject', methods: ['POST'])]
    public function reject(Request $request, Appointment $appointment, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyCrudForRegularUsers();

        if ($this->isCsrfTokenValid('reject'.$appointment->getId(), $request->getPayload()->getString('_token'))) {
            $appointment->setStatus(AppointmentStatus::CANCELLED);
            $entityManager->flush();
            $logger->log(
                'appointment_rejected',
                sprintf('Appointment #%d rejected', $appointment->getId()),
                sprintf('Patient: %s', $appointment->getPatientName())
            );
            $this->addFlash('success', 'Appointment request rejected.');
        }

        return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_appointment_delete', methods: ['POST'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager, ActivityLogger $logger): Response
    {
        $this->denyCrudForRegularUsers();

        if ($this->isCsrfTokenValid('delete'.$appointment->getId(), $request->getPayload()->getString('_token'))) {
            $appointmentId = $appointment->getId();
            $patientName = $appointment->getPatientName();
            $targetData = sprintf('Appointment ID: %d, Patient: %s', $appointmentId, $patientName);
            $entityManager->remove($appointment);
            $entityManager->flush();
            $logger->log('appointment_deleted', sprintf('Appointment #%d deleted', $appointmentId), $targetData);
        }

        return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
    }

    private function denyCrudForRegularUsers(): void
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You only have view access.');
        }
    }
}
