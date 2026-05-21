<?php

namespace App\Controller;

use App\AppointmentStatus;
use App\Repository\AppointmentPaymentRepository;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorAvailabilityRepository;
use App\Repository\DoctorRepository;
use App\Repository\ServiceRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    /** Patient clinic overview and staff analytics (role decides the view). */
    #[Route('/home', name: 'app_account_home', methods: ['GET'])]
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        DoctorRepository $doctorRepository,
        ServiceRepository $serviceRepository,
        AppointmentRepository $appointmentRepository,
        AppointmentPaymentRepository $paymentRepository,
        DoctorAvailabilityRepository $doctorAvailabilityRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $isStaffArea = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF');
        if (!$isStaffArea) {
            $today = new DateTimeImmutable('today');
            $services = $serviceRepository->findBy([], ['name' => 'ASC']);
            $doctors = $doctorRepository->findBy([], ['name' => 'ASC']);
            $availableDoctorIds = $doctorAvailabilityRepository->findAvailableDoctorIdsForDate($today);

            return $this->render('dashboard/patient.html.twig', [
                'services' => $services,
                'doctors' => $doctors,
                'availableDoctorIds' => $availableDoctorIds,
                'availabilityLabelDate' => $today,
            ]);
        }

        $trackedStatuses = AppointmentStatus::TRACKED;
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

