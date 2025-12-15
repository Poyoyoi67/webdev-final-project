<?php

namespace App\Controller;

use App\Repository\AppointmentPaymentRepository;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorRepository;
use App\Repository\ServiceRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
final class DashboardController extends AbstractController
{
    #[Route(name: 'app_dashboard', methods: ['GET'])]
    public function index(
        DoctorRepository $doctorRepository,
        ServiceRepository $serviceRepository,
        AppointmentRepository $appointmentRepository,
        AppointmentPaymentRepository $paymentRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $trackedStatuses = ['scheduled', 'finished', 'pending', 'confirmed', 'cancelled'];
        $statusCounts = $appointmentRepository->countByStatuses($trackedStatuses);

        $totalAppointments = $appointmentRepository->countAll();
        $totalDoctors = $doctorRepository->count([]);
        $totalServices = $serviceRepository->count([]);
        $totalPayments = $paymentRepository->count([]);

        $upcoming = $appointmentRepository->createQueryBuilder('a')
            ->leftJoin('a.doctor', 'd')
            ->leftJoin('a.service', 's')
            ->addSelect('d', 's')
            ->andWhere('a.appointmentDate >= :today')
            ->setParameter('today', new DateTimeImmutable('today'))
            ->orderBy('a.appointmentDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('dashboard/index.html.twig', [
            'totalAppointments' => $totalAppointments,
            'totalDoctors' => $totalDoctors,
            'totalServices' => $totalServices,
            'totalPayments' => $totalPayments,
            'statusCounts' => $statusCounts,
            'trackedStatuses' => $trackedStatuses,
            'upcoming' => $upcoming,
        ]);
    }
}

