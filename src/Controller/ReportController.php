<?php

namespace App\Controller;

use App\AppointmentStatus;
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
        $trackedStatuses = AppointmentStatus::TRACKED;

        $statusCounts = $appointmentRepository->countByStatuses($trackedStatuses);

        return $this->render('report/index.html.twig', [
            'statusCounts' => $statusCounts,
            'totalAppointments' => $appointmentRepository->countAll(),
            'trackedStatuses' => $trackedStatuses,
        ]);
    }
}

