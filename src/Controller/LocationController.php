<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/location')]
final class LocationController extends AbstractController
{
    #[Route(name: 'app_location_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $clinicLocation = [
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'name' => 'Health Care Clinic',
            'address' => '123 Main Street, Manila, Philippines'
        ];

        return $this->render('location/index.html.twig', [
            'clinicLocation' => $clinicLocation,
        ]);
    }
}

