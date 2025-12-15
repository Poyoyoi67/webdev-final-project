<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\AppointmentPayment;
use App\Form\AppointmentPaymentType;
use App\Repository\AppointmentPaymentRepository;
use App\Repository\AppointmentRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/transactions')]
final class TransactionController extends AbstractController
{
    #[Route(name: 'app_transactions_index', methods: ['GET'])]
    public function index(AppointmentPaymentRepository $paymentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $payments = $paymentRepository->findBy([], ['paidAt' => 'DESC']);

        return $this->render('transactions/index.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/appointment/{id}', name: 'app_transactions_manage', methods: ['GET', 'POST'])]
    public function manage(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $entityManager,
        ActivityLogger $logger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $payment = $appointment->getPayment() ?: new AppointmentPayment();
        $payment->setAppointment($appointment);

        $form = $this->createForm(AppointmentPaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payment->setPaidAt(new \DateTimeImmutable());
            $entityManager->persist($payment);
            $entityManager->flush();

            $targetData = sprintf('Payment ID: %d, Appointment ID: %d, Amount: %.2f, Change: %.2f, Method: %s', 
                $payment->getId(),
                $appointment->getId(),
                $payment->getAmount(),
                $payment->getChangeAmount() ?? 0,
                $payment->getPaymentMethod() ?? 'N/A'
            );
            $logger->log('payment_recorded', sprintf('Payment recorded for appointment #%d', $appointment->getId()), $targetData);

            return $this->redirectToRoute('app_transactions_index');
        }

        return $this->render('transactions/manage.html.twig', [
            'appointment' => $appointment,
            'form' => $form,
        ]);
    }
}


