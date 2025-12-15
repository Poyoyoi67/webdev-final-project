<?php

namespace App\Controller;

use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reports')]
final class ReportController extends AbstractController
{
    #[Route(name: 'app_report_index', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        // Configure the statuses you want to track. Adjust as needed.
        $trackedStatuses = ['scheduled', 'finished', 'pending', 'confirmed', 'cancelled'];

        $statusCounts = $appointmentRepository->countByStatuses($trackedStatuses);

        return $this->render('report/index.html.twig', [
            'statusCounts' => $statusCounts,
            'totalAppointments' => $appointmentRepository->countAll(),
            'trackedStatuses' => $trackedStatuses,
        ]);
    }
}

