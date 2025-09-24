<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RhamController extends AbstractController
{
    #[Route('/rham', name: 'app_rham')]
    public function index(): Response
    {
        return $this->render('rham/index.html.twig', [
            'controller_name' => 'RhamController',
        ]);
    }
}
