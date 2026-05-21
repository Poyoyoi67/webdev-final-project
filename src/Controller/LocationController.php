<?php

namespace App\Controller;

use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/location')]
final class LocationController extends AbstractController
{
    #[Route(name: 'app_location_index', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $clinicLocation = [
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'name' => 'Health Care Clinic',
            'address' => '123 Main Street, Manila, Philippines',
        ];

        $isPatient = !$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN');
        $canUseDirections = false;

        if ($isPatient && $this->getUser()) {
            $canUseDirections = $appointmentRepository->hasConfirmedBookingForPatient(
                $this->getUser()->getUserIdentifier()
            );
        }

        return $this->render('location/index.html.twig', [
            'clinicLocation' => $clinicLocation,
            'canUseDirections' => $canUseDirections,
            'isPatient' => $isPatient,
        ]);
    }
}
