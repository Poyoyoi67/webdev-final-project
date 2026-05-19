<?php

namespace App\Controller;

use App\Repository\DoctorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_about')]
    public function index(DoctorRepository $doctorRepository): Response
    {
        return $this->render('about/index.html.twig', [
            'doctors' => $doctorRepository->findAll(),
        ]);

    }
}
